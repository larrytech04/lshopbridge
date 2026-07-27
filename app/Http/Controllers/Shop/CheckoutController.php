<?php

namespace App\Http\Controllers\Shop;

use App\Enums\ShopOrderStatus;
use App\Enums\ShopProductType;
use App\Exceptions\InsufficientFundsException;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\Payments\SandboxSimulator;
use App\Services\Shop\CartService;
use App\Services\Shop\ShopService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private ShopService $shop,
        private SandboxSimulator $simulator,
    ) {}

    public function show(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('shop.index')->with('warning', 'Your cart is empty.');
        }

        return view('shop.checkout', [
            'lines' => $lines,
            'subtotal' => $this->cart->subtotal(),
            'wallet' => $request->user()->primaryWallet(),
            'methods' => PaymentMethod::active()->where('is_automated', true)->where('marketplace_enabled', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('shop.index')->with('warning', 'Your cart is empty.');
        }

        $hasEsim = $lines->contains(fn ($l) => $l['variant']->product->type === ShopProductType::Esim);

        $data = $request->validate([
            'payment_source' => ['required', 'in:wallet,direct'],
            'payment_method_id' => ['required_if:payment_source,direct', 'nullable', 'exists:payment_methods,id'],
            'email' => ['required', 'email'],
            'esim_device_confirmed' => [$hasEsim ? 'accepted' : 'nullable'],
        ]);

        $user = $request->user();

        try {
            if ($data['payment_source'] === 'wallet') {
                $order = $this->shop->checkoutFromWallet($user, $lines, $data['email']);
            } else {
                $method = PaymentMethod::active()->where('marketplace_enabled', true)->findOrFail($data['payment_method_id']);
                $result = $this->shop->checkoutWithDirectPayment($user, $lines, $data['email'], $method);

                if ($result['charge']->redirectUrl) {
                    return redirect()->away($result['charge']->redirectUrl);
                }
                $this->simulator->replay($method->provider_code, $result['charge']);
                $order = $result['order']->fresh();
            }
        } catch (InsufficientFundsException $e) {
            return back()->with('error', 'Insufficient wallet balance. Top up or pay directly.');
        }

        $this->cart->clear();

        $message = in_array($order->status, [ShopOrderStatus::Processing, ShopOrderStatus::PartiallyFulfilled], true)
            ? 'Payment received! Most items are ready now; anything still preparing will be emailed to you shortly.'
            : 'Order complete, your digital products are ready!';

        return redirect()->route('shop.orders.show', $order)->with('success', $message);
    }
}
