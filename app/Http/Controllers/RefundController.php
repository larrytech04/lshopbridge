<?php

namespace App\Http\Controllers;

use App\Models\ShopOrder;
use App\Services\Admin\ShopOrderAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $eligibleOrders = $user->shopOrders()
            ->whereNotNull('paid_at')
            ->latest()
            ->get()
            ->filter(fn (ShopOrder $o) => $o->isRefundEligibleByCustomer())
            ->values();

        $requests = \App\Models\ShopRefund::whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->with('order')
            ->latest()
            ->paginate(10);

        return view('refunds.index', [
            'eligibleOrders' => $eligibleOrders,
            'requests' => $requests,
            'windowDays' => (int) setting('refund_window_days', 14),
        ]);
    }

    public function store(Request $request, ShopOrderAdminService $svc): RedirectResponse
    {
        $data = $request->validate([
            'shop_order_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $order = ShopOrder::findOrFail($data['shop_order_id']);
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($order->isRefundEligibleByCustomer(), 422);

        \App\Models\ShopRefund::create([
            'shop_order_id' => $order->id,
            'amount' => $order->refundableAmount(),
            'currency' => $order->currency,
            'reason' => $data['reason'],
            'requested_by' => $request->user()->id,
            'status' => 'requested',
        ]);

        $svc->requestRefund($order, $data['reason'], $request->user());

        return back()->with('success', __('Refund request submitted. Our team will review it shortly.'));
    }
}
