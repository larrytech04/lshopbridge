<?php

namespace App\Services\Shop;

use App\Models\ShopVariant;
use Illuminate\Support\Collection;

/**
 * Session-backed shopping cart. Keyed by variant id => quantity. Lightweight by
 * design — the durable record is the ShopOrder created at checkout.
 */
class CartService
{
    private const KEY = 'shop_cart';

    public function add(int $variantId, int $qty = 1): void
    {
        $cart = $this->raw();
        $cart[$variantId] = max(1, ($cart[$variantId] ?? 0) + $qty);
        session()->put(self::KEY, $cart);
    }

    public function update(int $variantId, int $qty): void
    {
        $cart = $this->raw();
        if ($qty <= 0) {
            unset($cart[$variantId]);
        } else {
            $cart[$variantId] = $qty;
        }
        session()->put(self::KEY, $cart);
    }

    public function remove(int $variantId): void
    {
        $cart = $this->raw();
        unset($cart[$variantId]);
        session()->put(self::KEY, $cart);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function raw(): array
    {
        return session()->get(self::KEY, []);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /** @return Collection<int, array{variant: ShopVariant, qty: int, line_total: float}> */
    public function lines(): Collection
    {
        $cart = $this->raw();
        if (empty($cart)) {
            return collect();
        }

        return ShopVariant::with('product.category')
            ->whereIn('id', array_keys($cart))
            ->where('is_active', true)
            ->get()
            ->map(fn (ShopVariant $v) => [
                'variant' => $v,
                'qty' => (int) $cart[$v->id],
                'line_total' => round((float) $v->price * (int) $cart[$v->id], 2),
            ])
            ->values();
    }

    public function subtotal(): float
    {
        return (float) $this->lines()->sum('line_total');
    }
}
