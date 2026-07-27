<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShopOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ShopOrder;
use App\Models\User;
use App\Services\Admin\ShopOrderAdminService;
use App\Services\Audit\AuditLogger;
use App\Services\Shop\ShopService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShopOrderController extends Controller
{
    public function index(Request $request, ShopOrderAdminService $svc): View
    {
        $query = ShopOrder::with('user')->withCount('items');

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('reference', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($request->query('risk') === '1') {
            $query->where('risk_flagged', true);
        }

        $tab = $request->query('tab', 'all');
        if ($tab !== 'all') {
            $tabMap = [
                'pending' => ['pending'], 'awaiting_payment' => ['pending'], 'paid' => ['paid'],
                'processing' => ['processing'], 'partially_fulfilled' => ['partially_fulfilled'],
                'fulfilled' => ['fulfilled'], 'shipped' => ['shipped'], 'delivered' => ['delivered'],
                'failed' => ['failed'], 'cancelled' => ['cancelled'], 'refund_requested' => ['refund_requested'],
                'refunded' => ['refunded', 'partially_refunded'], 'disputed' => ['disputed'],
            ];
            if (isset($tabMap[$tab])) {
                $query->whereIn('status', $tabMap[$tab]);
            }
        }

        return view('admin.shop.orders.index', [
            'orders' => $query->latest()->paginate(20)->withQueryString(),
            'q' => $request->query('q', ''),
            'summary' => $svc->summary(),
            'tabCounts' => $svc->tabCounts(),
            'activeTab' => $tab,
            'staff' => User::whereIn('role', ['admin', 'super_admin'])->orderBy('name')->get(),
        ]);
    }

    public function rowDetail(ShopOrder $order, AuditLogger $audit)
    {
        $order->load(['user', 'items.product', 'items.variant', 'events.actor', 'refunds', 'assignedTo']);
        $audit->log('shop.order.viewed', "Viewed order {$order->reference}", $order);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'reference' => $order->reference,
                'customer' => $order->user->name,
                'email' => $order->user->email,
                'user_id' => $order->user_id,
                'subtotal' => (float) $order->subtotal,
                'fee' => (float) $order->fee,
                'total' => (float) $order->total,
                'currency' => $order->currency,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'payment_source' => $order->payment_source,
                'paid_at' => $order->paid_at?->format('M j, Y g:ia'),
                'tracking_number' => $order->tracking_number,
                'carrier' => $order->carrier,
                'shipped_at' => $order->shipped_at?->format('M j, Y g:ia'),
                'delivered_at' => $order->delivered_at?->format('M j, Y g:ia'),
                'risk_flagged' => $order->risk_flagged,
                'manual_review_reason' => $order->manual_review_reason,
                'admin_notes' => $order->admin_notes,
                'assigned_to' => $order->assigned_to,
                'assigned_to_name' => $order->assignedTo?->name,
                'refundable_amount' => $order->refundableAmount(),
                'total_refunded' => $order->totalRefunded(),
                'created' => $order->created_at->format('M j, Y g:ia'),
                'can_cancel' => ! $order->status->isSettled(),
                'can_refund' => $order->refundableAmount() > 0 && in_array($order->status->value, ['paid', 'processing', 'fulfilled', 'shipped', 'delivered', 'refund_requested', 'partially_refunded'], true),
            ],
            'items' => $order->items->map(fn ($i) => [
                'id' => $i->id, 'name' => $i->name, 'type' => $i->type,
                'quantity' => $i->quantity, 'unit_price' => (float) $i->unit_price, 'line_total' => (float) $i->line_total,
                'status' => $i->status->value, 'delivered' => $i->delivered,
            ]),
            'events' => $order->events->map(fn ($e) => [
                'event' => $e->event, 'from_status' => $e->from_status, 'to_status' => $e->to_status,
                'actor' => $e->actor?->name ?? 'System', 'reason' => $e->reason, 'at' => $e->created_at->format('M j, Y g:ia'),
            ]),
            'refunds' => $order->refunds->map(fn ($r) => [
                'amount' => (float) $r->amount, 'reason' => $r->reason, 'status' => $r->status, 'at' => $r->completed_at?->format('M j, Y g:ia'),
            ]),
        ]);
    }

    public function show(ShopOrder $order): View
    {
        return view('admin.shop.orders.show', ['order' => $order->load('user', 'items.product', 'events.actor', 'refunds')]);
    }

    public function fulfill(ShopOrder $order, ShopService $shop)
    {
        $order->status->value === 'pending' ? $shop->markPaid($order) : $shop->fulfill($order);

        return back()->with('success', 'Order fulfilled / re-delivered.');
    }

    public function startProcessing(Request $request, ShopOrder $order, ShopOrderAdminService $svc)
    {
        $svc->startProcessing($order, $request->user());

        return back()->with('success', 'Order marked as processing.');
    }

    public function assign(Request $request, ShopOrder $order, ShopOrderAdminService $svc)
    {
        $data = $request->validate(['staff_id' => ['nullable', 'exists:users,id']]);
        $svc->assign($order, $data['staff_id'] ? User::find($data['staff_id']) : null, $request->user());

        return back()->with('success', 'Assignment updated.');
    }

    public function markShipped(Request $request, ShopOrder $order, ShopOrderAdminService $svc)
    {
        $data = $request->validate(['tracking_number' => ['required', 'string', 'max:120'], 'carrier' => ['nullable', 'string', 'max:80']]);
        $svc->markShipped($order, $data['tracking_number'], $data['carrier'] ?? null, $request->user());

        return back()->with('success', 'Tracking added and order marked shipped.');
    }

    public function markDelivered(Request $request, ShopOrder $order, ShopOrderAdminService $svc)
    {
        $svc->markDelivered($order, $request->user());

        return back()->with('success', 'Order marked delivered.');
    }

    public function resendDelivery(Request $request, ShopOrder $order, ShopOrderAdminService $svc)
    {
        $svc->resendDigitalDelivery($order, $request->user());

        return back()->with('success', 'Delivery notification resent.');
    }

    public function cancel(Request $request, ShopOrder $order, ShopOrderAdminService $svc)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $svc->cancel($order, $data['reason'], $request->user());

        return back()->with('success', 'Order cancelled.');
    }

    public function requestRefund(Request $request, ShopOrder $order, ShopOrderAdminService $svc)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $svc->requestRefund($order, $data['reason'], $request->user());

        return back()->with('success', 'Refund requested.');
    }

    public function refund(Request $request, ShopOrder $order, ShopOrderAdminService $svc)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $svc->refund($order, (float) $data['amount'], $data['reason'], $request->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Refund completed.');
    }

    public function rejectRefund(Request $request, ShopOrder $order, \App\Models\ShopRefund $refund, ShopOrderAdminService $svc)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $svc->rejectRefund($order, $refund, $data['reason'], $request->user());

        return back()->with('success', 'Refund request declined.');
    }

    public function addNote(Request $request, ShopOrder $order, ShopOrderAdminService $svc)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $svc->addNote($order, $data['note'], $request->user());

        return back()->with('success', 'Note added.');
    }

    public function bulkAction(Request $request, ShopOrderAdminService $svc)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['start_processing', 'export'])],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $orders = ShopOrder::whereIn('id', $data['ids'])->get();
        foreach ($orders as $order) {
            match ($data['action']) {
                'start_processing' => $order->status === ShopOrderStatus::Paid ? $svc->startProcessing($order, $request->user()) : null,
                default => null,
            };
        }

        return back()->with('success', ucfirst(str_replace('_', ' ', $data['action'])).' applied to '.$orders->count().' order(s).');
    }

    public function exportCsv(AuditLogger $audit): StreamedResponse
    {
        $orders = ShopOrder::with('user')->withCount('items')->get();
        $audit->log('shop.order.exported', 'Exported '.$orders->count().' order(s) to CSV');

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Customer', 'Items', 'Total', 'Currency', 'Status', 'Created']);
            foreach ($orders as $o) {
                fputcsv($out, [$o->reference, $o->user->name, $o->items_count, $o->total, $o->currency, $o->status->label(), $o->created_at->toDateTimeString()]);
            }
            fclose($out);
        }, 'orders-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
