<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ShopVariant;
use App\Services\Shop\CartService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(): View
    {
        return view('shop.cart', [
            'lines' => $this->cart->lines(),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'exists:shop_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $variant = ShopVariant::where('is_active', true)->findOrFail($data['variant_id']);
        if (! $variant->inStock($data['quantity'] ?? 1)) {
            return back()->with('error', 'Sorry, that item is out of stock.');
        }

        $this->cart->add($variant->id, $data['quantity'] ?? 1);

        if ($request->boolean('buy_now')) {
            return redirect()->route('shop.checkout');
        }

        return redirect()->route('cart.index')->with('success', 'Added to cart.');
    }

    public function update(Request $request, int $variant)
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:20']]);
        $this->cart->update($variant, (int) $request->quantity);

        return back();
    }

    public function remove(int $variant)
    {
        $this->cart->remove($variant);

        return back()->with('success', 'Item removed.');
    }
}
