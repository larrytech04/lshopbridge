<?php

namespace App\Services\Shop;

use App\Enums\PaymentIntentStatus;
use App\Enums\ShopOrderStatus;
use App\Models\PaymentIntent;
use App\Models\PaymentMethod;
use App\Models\ShopCode;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopVariant;
use App\Models\User;
use App\Notifications\ShopOrderDelivered;
use App\Services\Audit\AuditLogger;
use App\Services\Payments\DTO\WebhookResult;
use App\Services\Payments\PaymentManager;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Digital storefront engine: builds orders from the cart, takes payment (wallet
 * or a provider charge settled by webhook) and delivers the digital goods —
 * pulling from pre-loaded code inventory or auto-generating realistic secrets.
 */
class ShopService
{
    public function __construct(
        private WalletService $wallet,
        private PaymentManager $payments,
        private AuditLogger $audit,
    ) {}

    /** Pay instantly from the wallet, then deliver. */
    public function checkoutFromWallet(User $user, Collection $lines, string $email): ShopOrder
    {
        $order = $this->createOrder($user, $lines, $email, 'wallet');

        DB::transaction(function () use ($user, $order) {
            $w = $user->primaryWallet($order->currency);
            $this->wallet->debit($w, (float) $order->total, 'shop', $order, "Shop order {$order->reference}");
            $this->markPaid($order);
        });

        $this->audit->log('shop.order.paid_wallet', "Shop order {$order->reference} paid from wallet", $order);

        return $order->fresh('items');
    }

    /**
     * Pay with a fresh provider charge; delivery happens on webhook confirmation.
     *
     * @return array{order: ShopOrder, intent: PaymentIntent, charge: \App\Services\Payments\DTO\ChargeResult}
     */
    public function checkoutWithDirectPayment(User $user, Collection $lines, string $email, PaymentMethod $method): array
    {
        $order = $this->createOrder($user, $lines, $email, 'direct');

        $intent = PaymentIntent::create([
            'reference' => reference('PB-INT'),
            'user_id' => $user->id,
            'provider_code' => $method->provider_code,
            'method_code' => $method->code,
            'purpose' => 'shop',
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => PaymentIntentStatus::Processing,
            'shop_order_id' => $order->id,
            'attempts' => 1,
        ]);

        $charge = $this->payments->driver($method->provider_code)->charge($intent, [
            'email' => $email, 'phone' => $user->phone,
        ]);

        $intent->update([
            'provider_reference' => $charge->providerReference,
            'redirect_url' => $charge->redirectUrl,
            'status' => $charge->failed() ? PaymentIntentStatus::Failed : PaymentIntentStatus::Processing,
            'last_error' => $charge->failed() ? $charge->message : null,
        ]);

        if ($charge->failed()) {
            $order->update(['status' => ShopOrderStatus::Failed]);
        }

        return ['order' => $order, 'intent' => $intent, 'charge' => $charge];
    }

    public function settleFromWebhook(PaymentIntent $intent, WebhookResult $result): void
    {
        $order = $intent->shopOrder;
        if (! $order) {
            return;
        }

        if ($result->succeeded()) {
            $intent->update(['status' => PaymentIntentStatus::Succeeded]);
            $this->markPaid($order);
        } else {
            $intent->update(['status' => PaymentIntentStatus::Failed]);
            $order->update(['status' => ShopOrderStatus::Failed]);
        }
    }

    public function markPaid(ShopOrder $order): void
    {
        if ($order->status !== ShopOrderStatus::Pending) {
            return;
        }
        $order->update(['status' => ShopOrderStatus::Paid, 'paid_at' => now()]);
        $this->fulfill($order);
    }

    /** Deliver every line item, then mark the order delivered. */
    public function fulfill(ShopOrder $order): void
    {
        $order->loadMissing('items.product', 'items.variant');

        foreach ($order->items as $item) {
            if ($item->status === 'fulfilled') {
                continue;
            }
            $this->deliverItem($item);
            $item->product?->increment('sales_count', $item->quantity);
        }

        $order->update(['status' => ShopOrderStatus::Fulfilled]);
        $this->audit->log('shop.order.fulfilled', "Shop order {$order->reference} delivered", $order);
        $order->user->notify(new ShopOrderDelivered($order));
    }

    public function refund(ShopOrder $order, ?User $admin = null, string $reason = 'Refunded'): ShopOrder
    {
        if ($order->status === ShopOrderStatus::Refunded) {
            return $order;
        }

        DB::transaction(function () use ($order, $reason) {
            $w = $order->user->primaryWallet($order->currency);
            $this->wallet->credit($w, (float) $order->total, 'refund', $order, "Refund shop order {$order->reference}: {$reason}");
            $order->update(['status' => ShopOrderStatus::Refunded]);
        });

        $this->audit->log('shop.order.refunded', "Shop order {$order->reference} refunded", $order, ['reason' => $reason], $admin?->id);

        return $order->fresh();
    }

    /* -------------------------------------------------- internals */

    private function createOrder(User $user, Collection $lines, string $email, string $source): ShopOrder
    {
        $subtotal = (float) $lines->sum('line_total');
        $fee = 0.0; // digital goods are priced inclusive
        $total = round($subtotal + $fee, 2);

        return DB::transaction(function () use ($user, $lines, $email, $source, $subtotal, $fee, $total) {
            $order = ShopOrder::create([
                'reference' => reference('PB-SHP'),
                'user_id' => $user->id,
                'status' => ShopOrderStatus::Pending,
                'subtotal' => $subtotal,
                'fee' => $fee,
                'total' => $total,
                'currency' => config('platform.base_currency', 'XAF'),
                'payment_source' => $source,
                'email' => $email,
            ]);

            foreach ($lines as $line) {
                /** @var ShopVariant $v */
                $v = $line['variant'];
                $order->items()->create([
                    'shop_product_id' => $v->shop_product_id,
                    'shop_variant_id' => $v->id,
                    'name' => $v->product->name.' — '.$v->name,
                    'type' => $v->product->type,
                    'unit_price' => $v->price,
                    'quantity' => $line['qty'],
                    'line_total' => $line['line_total'],
                    'status' => 'pending',
                ]);
            }

            return $order;
        });
    }

    private function deliverItem(ShopOrderItem $item): void
    {
        $delivered = [];

        for ($i = 0; $i < $item->quantity; $i++) {
            $code = $item->shop_variant_id
                ? ShopCode::where('shop_variant_id', $item->shop_variant_id)->where('is_used', false)->lockForUpdate()->first()
                : null;

            if ($code) {
                $code->update(['is_used' => true, 'shop_order_item_id' => $item->id, 'used_at' => now()]);
                $delivered[] = $code->secret;

                if ($item->variant && $item->variant->stock !== null) {
                    $item->variant->decrement('stock');
                }
            } else {
                $delivered[] = $this->generateSecret($item->type, $item);
            }
        }

        $item->update(['delivered' => $delivered, 'status' => 'fulfilled']);
    }

    /** Produce a realistic deliverable secret by product type (sandbox / fallback). */
    private function generateSecret(string $type, ShopOrderItem $item): string
    {
        $chunk = fn (int $n = 4) => strtoupper(Str::random($n));

        return match ($type) {
            'esim' => 'LPA:1$rsp.truphone.com$'.$chunk(8).$chunk(8),
            'vpn' => 'VPN-'.$chunk(4).'-'.$chunk(4).'-'.$chunk(4).'-'.$chunk(4),
            'data' => 'DATA PIN: '.random_int(100000, 999999).' '.random_int(100000, 999999),
            'gaming', 'giftcard', 'streaming', 'software' => $chunk(4).'-'.$chunk(4).'-'.$chunk(4).'-'.$chunk(4),
            default => $chunk(5).'-'.$chunk(5).'-'.$chunk(5),
        };
    }
}
