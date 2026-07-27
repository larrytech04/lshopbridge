<?php

namespace App\Services\Admin;

use App\Enums\ShopOrderStatus;
use App\Models\ShopOrder;
use App\Models\ShopOrderEvent;
use App\Models\ShopRefund;
use App\Models\User;
use App\Notifications\ShopOrderDelivered;
use App\Services\Audit\AuditLogger;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShopOrderAdminService
{
    public function __construct(
        private AuditLogger $audit,
        private WalletService $wallet,
    ) {}

    public function startProcessing(ShopOrder $order, User $admin): ShopOrder
    {
        return $this->transition($order, ShopOrderStatus::Processing, $admin, 'processing_started');
    }

    public function assign(ShopOrder $order, ?User $staff, User $admin): ShopOrder
    {
        $order->update(['assigned_to' => $staff?->id]);
        $this->recordEvent($order, 'staff_assigned', $order->status, $order->status, $admin, $staff?->name ?? 'Unassigned');
        $this->audit->log('shop.order.assigned', "Assigned {$order->reference} to ".($staff?->name ?? 'nobody'), $order, [], $admin->id);

        return $order->fresh();
    }

    public function markShipped(ShopOrder $order, string $tracking, ?string $carrier, User $admin): ShopOrder
    {
        $order->update([
            'tracking_number' => $tracking,
            'carrier' => $carrier,
            'shipped_at' => now(),
        ]);

        return $this->transition($order, ShopOrderStatus::Shipped, $admin, 'shipped', "Tracking: {$tracking}".($carrier ? " ({$carrier})" : ''));
    }

    public function markDelivered(ShopOrder $order, User $admin): ShopOrder
    {
        $order->update(['delivered_at' => now()]);

        return $this->transition($order, ShopOrderStatus::Delivered, $admin, 'delivered');
    }

    /** Re-sends already-delivered digital codes — never regenerates or issues new ones. */
    public function resendDigitalDelivery(ShopOrder $order, User $admin): void
    {
        $order->user->notify(new ShopOrderDelivered($order));
        $this->recordEvent($order, 'digital_delivery_resent', $order->status, $order->status, $admin);
        $this->audit->log('shop.order.delivery_resent', "Resent digital delivery for {$order->reference}", $order, [], $admin->id);
    }

    public function cancel(ShopOrder $order, string $reason, User $admin): ShopOrder
    {
        if ($order->status->isSettled()) {
            throw ValidationException::withMessages(['status' => 'This order has already settled and cannot be cancelled.']);
        }

        return DB::transaction(function () use ($order, $reason, $admin) {
            $fromStatus = $order->status->value;

            if ($order->status !== ShopOrderStatus::Pending && $order->total > 0) {
                $wallet = $order->user->primaryWallet($order->currency);
                $this->wallet->credit($wallet, (float) $order->total, 'refund', $order, "Cancelled order {$order->reference}: {$reason}");
            }

            $order->update(['status' => ShopOrderStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => $reason]);
            $this->recordEvent($order, 'cancelled', $fromStatus, ShopOrderStatus::Cancelled->value, $admin, $reason);
            $this->audit->log('shop.order.cancelled', "Cancelled {$order->reference}: {$reason}", $order, ['reason' => $reason], $admin->id);

            return $order->fresh();
        });
    }

    public function requestRefund(ShopOrder $order, string $reason, User $admin): ShopOrder
    {
        // Capture the pre-transition status before update() — save() re-syncs
        // Eloquent's "original" snapshot on success, so getOriginal() called
        // afterwards would just return the new value, not the old one.
        $fromStatus = $order->status;
        $order->update(['status' => ShopOrderStatus::RefundRequested]);
        $this->recordEvent($order, 'refund_requested', $fromStatus, ShopOrderStatus::RefundRequested->value, $admin, $reason);
        $this->audit->log('shop.order.refund_requested', "Refund requested for {$order->reference}: {$reason}", $order, [], $admin->id);

        return $order->fresh();
    }

    /**
     * Full or partial refund. Guards against ever refunding more than what's
     * left on the order — the running total across all completed refunds is
     * checked, so the same amount can never be refunded twice.
     */
    public function refund(ShopOrder $order, float $amount, string $reason, User $admin, ?array $items = null): ShopRefund
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Refund amount must be greater than zero.']);
        }

        $refundable = $order->refundableAmount();
        if ($amount > $refundable) {
            throw ValidationException::withMessages(['amount' => "Refund amount exceeds the refundable balance ({$refundable} {$order->currency})."]);
        }

        return DB::transaction(function () use ($order, $amount, $reason, $admin, $items) {
            $wallet = $order->user->primaryWallet($order->currency);
            $this->wallet->credit($wallet, $amount, 'refund', $order, "Refund {$order->reference}: {$reason}");

            // A customer-initiated request (status "requested") already recorded who
            // asked and why — approving it updates that same row instead of leaving
            // it stranded while a second, disconnected row gets created here.
            $pending = $order->refunds()->where('status', 'requested')->oldest()->first();

            if ($pending) {
                $pending->update([
                    'amount' => $amount,
                    'items' => $items,
                    'approved_by' => $admin->id,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                $refund = $pending;
            } else {
                $refund = ShopRefund::create([
                    'shop_order_id' => $order->id,
                    'amount' => $amount,
                    'currency' => $order->currency,
                    'reason' => $reason,
                    'items' => $items,
                    'requested_by' => $admin->id,
                    'approved_by' => $admin->id,
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            $fromStatus = $order->status->value;
            $isFullRefund = $order->refundableAmount() <= 0;
            $order->update(['status' => $isFullRefund ? ShopOrderStatus::Refunded : ShopOrderStatus::PartiallyRefunded]);
            $this->recordEvent($order, 'refund_completed', $fromStatus, $order->status->value, $admin, $reason);
            $this->audit->log('shop.order.refunded', "Refunded {$amount} {$order->currency} for {$order->reference}: {$reason}", $order, ['refund_id' => $refund->id], $admin->id);

            return $refund;
        });
    }

    /** Declines a customer-initiated refund request without moving any money. */
    public function rejectRefund(ShopOrder $order, ShopRefund $refund, string $reason, User $admin): ShopOrder
    {
        abort_unless($refund->shop_order_id === $order->id && $refund->status === 'requested', 422);

        $refund->update(['approved_by' => $admin->id, 'status' => 'rejected']);

        // Restore whatever status the order was in right before this request was
        // made (recorded on the refund_requested event) rather than guessing —
        // an order can reach "refund requested" from several different states.
        $restoreTo = $order->events()->where('event', 'refund_requested')->latest()->value('from_status');
        $fromStatus = $order->status->value;
        $order->update(['status' => ShopOrderStatus::tryFrom($restoreTo ?? '') ?? ShopOrderStatus::Paid]);
        $this->recordEvent($order, 'refund_rejected', $fromStatus, $order->status->value, $admin, $reason);
        $this->audit->log('shop.order.refund_rejected', "Refund request declined for {$order->reference}: {$reason}", $order, ['refund_id' => $refund->id], $admin->id);

        return $order->fresh();
    }

    public function addNote(ShopOrder $order, string $note, User $admin): ShopOrder
    {
        $order->update(['admin_notes' => trim(($order->admin_notes ? $order->admin_notes."\n\n" : '').'['.now()->format('M j, Y g:ia').' · '.$admin->name.'] '.$note)]);
        $this->recordEvent($order, 'note_added', $order->status, $order->status, $admin, $note);

        return $order->fresh();
    }

    private function transition(ShopOrder $order, ShopOrderStatus $to, User $admin, string $event, ?string $note = null): ShopOrder
    {
        $from = $order->status;
        $order->update(['status' => $to]);
        $this->recordEvent($order, $event, $from->value, $to->value, $admin, $note);
        $this->audit->log("shop.order.{$event}", "{$order->reference}: {$from->label()} → {$to->label()}", $order, [], $admin->id);

        return $order->fresh();
    }

    /**
     * $from/$to accept the ShopOrderStatus enum too — several call sites pass
     * `$order->status` or `getOriginal('status')` directly, both of which
     * return the cast enum instance rather than a string.
     */
    private function recordEvent(ShopOrder $order, string $event, string|ShopOrderStatus|null $from, string|ShopOrderStatus|null $to, ?User $actor, ?string $reason = null): void
    {
        ShopOrderEvent::create([
            'shop_order_id' => $order->id,
            'event' => $event,
            'from_status' => $from instanceof ShopOrderStatus ? $from->value : $from,
            'to_status' => $to instanceof ShopOrderStatus ? $to->value : $to,
            'actor_id' => $actor?->id,
            'reason' => $reason,
        ]);
    }

    public function summary(): array
    {
        $orders = ShopOrder::all();
        $today = now()->startOfDay();

        return [
            'total' => $orders->count(),
            'today' => $orders->where('created_at', '>=', $today)->count(),
            'awaiting_payment' => $orders->where('status', ShopOrderStatus::Pending)->count(),
            'paid' => $orders->where('status', ShopOrderStatus::Paid)->count(),
            'processing' => $orders->where('status', ShopOrderStatus::Processing)->count(),
            'awaiting_fulfilment' => $orders->whereIn('status', [ShopOrderStatus::Paid, ShopOrderStatus::Processing])->count(),
            'shipped' => $orders->where('status', ShopOrderStatus::Shipped)->count(),
            'delivered' => $orders->whereIn('status', [ShopOrderStatus::Fulfilled, ShopOrderStatus::Delivered])->count(),
            'failed' => $orders->where('status', ShopOrderStatus::Failed)->count(),
            'cancelled' => $orders->where('status', ShopOrderStatus::Cancelled)->count(),
            'refund_requested' => $orders->where('status', ShopOrderStatus::RefundRequested)->count(),
            'refunded' => $orders->whereIn('status', [ShopOrderStatus::Refunded, ShopOrderStatus::PartiallyRefunded])->count(),
            'sales_today' => (float) $orders->where('created_at', '>=', $today)->where('status', '!=', ShopOrderStatus::Failed)->sum('total'),
        ];
    }

    /** @return array<string,int> */
    public function tabCounts(): array
    {
        $orders = ShopOrder::all();

        return [
            'all' => $orders->count(),
            'pending' => $orders->where('status', ShopOrderStatus::Pending)->count(),
            'awaiting_payment' => $orders->where('status', ShopOrderStatus::Pending)->count(),
            'paid' => $orders->where('status', ShopOrderStatus::Paid)->count(),
            'processing' => $orders->where('status', ShopOrderStatus::Processing)->count(),
            'partially_fulfilled' => $orders->where('status', ShopOrderStatus::PartiallyFulfilled)->count(),
            'fulfilled' => $orders->where('status', ShopOrderStatus::Fulfilled)->count(),
            'shipped' => $orders->where('status', ShopOrderStatus::Shipped)->count(),
            'delivered' => $orders->where('status', ShopOrderStatus::Delivered)->count(),
            'failed' => $orders->where('status', ShopOrderStatus::Failed)->count(),
            'cancelled' => $orders->where('status', ShopOrderStatus::Cancelled)->count(),
            'refund_requested' => $orders->where('status', ShopOrderStatus::RefundRequested)->count(),
            'refunded' => $orders->whereIn('status', [ShopOrderStatus::Refunded, ShopOrderStatus::PartiallyRefunded])->count(),
            'disputed' => $orders->where('status', ShopOrderStatus::Disputed)->count(),
        ];
    }
}
