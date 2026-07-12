<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopOrder;
use App\Services\Shop\ShopService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = ShopOrder::with('user')->withCount('items');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($q = $request->query('q')) {
            $query->where('reference', 'like', "%{$q}%");
        }

        return view('admin.shop.orders.index', [
            'orders' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only('status', 'q'),
        ]);
    }

    public function show(ShopOrder $order): View
    {
        return view('admin.shop.orders.show', ['order' => $order->load('user', 'items.product')]);
    }

    public function fulfill(ShopOrder $order, ShopService $shop)
    {
        $order->status->value === 'pending' ? $shop->markPaid($order) : $shop->fulfill($order);

        return back()->with('success', 'Order fulfilled / re-delivered.');
    }

    public function refund(Request $request, ShopOrder $order, ShopService $shop)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $shop->refund($order, $request->user(), $data['reason'] ?? 'Refunded by admin');

        return back()->with('success', 'Order refunded to wallet.');
    }
}
