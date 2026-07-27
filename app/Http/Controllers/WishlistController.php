<?php

namespace App\Http\Controllers;

use App\Models\ShopProduct;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $wishlists = $request->user()->wishlists()
            ->with(['product.category', 'product.variants'])
            ->latest()
            ->paginate(12);

        return view('wishlist.index', ['wishlists' => $wishlists]);
    }

    public function store(Request $request, ShopProduct $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'shop_product_id' => $product->id,
        ]);

        return back()->with('success', __('Added to your wishlist.'));
    }

    public function destroy(Request $request, ShopProduct $product): RedirectResponse
    {
        $request->user()->wishlists()->where('shop_product_id', $product->id)->delete();

        return back()->with('success', __('Removed from your wishlist.'));
    }
}
