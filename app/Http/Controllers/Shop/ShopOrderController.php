<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->shopOrders()->with('items.product')->latest();

        if ($request->filled('q')) {
            $query->where('reference', 'like', '%'.$request->string('q').'%');
        }

        // "Expired orders" = orders that never completed (failed/refunded), hidden by default.
        if (! $request->boolean('expired')) {
            $query->whereNotIn('status', ['failed', 'refunded']);
        }

        return view('shop.orders.index', [
            'orders' => $query->paginate(12)->withQueryString(),
        ]);
    }

    public function show(Request $request, ShopOrder $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return view('shop.orders.show', ['order' => $order->load('items.product', 'items.variant')]);
    }
}
