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
        return view('shop.orders.index', [
            'orders' => $request->user()->load('shopOrders')->shopOrders()->latest()->paginate(12),
        ]);
    }

    public function show(Request $request, ShopOrder $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return view('shop.orders.show', ['order' => $order->load('items.product')]);
    }
}
