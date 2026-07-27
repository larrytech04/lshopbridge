@extends('layouts.admin')
@section('page-title', 'Shop Orders')

@php
    $tabs = [
        'all' => 'All', 'pending' => 'Pending', 'awaiting_payment' => 'Awaiting Payment', 'paid' => 'Paid',
        'processing' => 'Processing', 'partially_fulfilled' => 'Partially Fulfilled', 'fulfilled' => 'Fulfilled',
        'shipped' => 'Shipped', 'delivered' => 'Delivered', 'failed' => 'Failed', 'cancelled' => 'Cancelled',
        'refund_requested' => 'Refund Requested', 'refunded' => 'Refunded', 'disputed' => 'Disputed',
    ];
    $summaryCards = [
        ['Total orders', $summary['total'], 'list', 'slate'],
        ['Orders today', $summary['today'], 'clock', 'sky'],
        ['Awaiting payment', $summary['awaiting_payment'], 'clock', 'amber'],
        ['Paid', $summary['paid'], 'check-circle', 'sky'],
        ['Processing', $summary['processing'], 'refresh', 'sky'],
        ['Awaiting fulfilment', $summary['awaiting_fulfilment'], 'bag', 'amber'],
        ['Shipped', $summary['shipped'], 'truck', 'sky'],
        ['Delivered', $summary['delivered'], 'check-circle', 'emerald'],
        ['Failed', $summary['failed'], 'alert', 'rose'],
        ['Cancelled', $summary['cancelled'], 'ban', 'gray'],
        ['Refund requested', $summary['refund_requested'], 'alert', 'amber'],
        ['Refunded', $summary['refunded'], 'swap', 'violet'],
        ['Sales today', money($summary['sales_today'], config('platform.base_currency')), 'gauge', 'emerald'],
    ];
@endphp

@section('content')
<div x-data="ordersConsole()" x-init="init()" class="space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Shop Orders</h1>
            <p class="text-sm text-muted">Monitor payments, fulfilment, digital delivery, shipping, refunds, and customer order activity.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.shop.orders.export') }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export Orders</a>
            <button type="button" class="qa-btn" @click="window.location.reload()"><x-icon name="refresh" class="h-3.5 w-3.5" /> Refresh</button>
        </div>
    </div>

    {{-- ============ SUMMARY CARDS ============ --}}
    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-4 xl:grid-cols-7">
        @foreach ($summaryCards as [$label, $value, $icon, $tint])
            <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-{{ $tint }}-500/15 text-{{ $tint }}-600"><x-icon :name="$icon" class="h-4 w-4" /></span>
                    <p class="truncate text-[11px] text-faint">{{ $label }}</p>
                </div>
                <p class="mt-2 text-lg font-bold text-strong">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- ============ STATUS TABS ============ --}}
    <div class="no-scrollbar flex gap-1.5 overflow-x-auto rounded-2xl border border-app p-1.5">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.shop.orders.index', array_filter(['tab' => $key === 'all' ? null : $key, 'q' => $q])) }}"
               class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-medium {{ $activeTab === $key ? 'bg-brand-500 text-white' : 'text-muted hover:surface-2' }}">
                {{ $label }} <span class="opacity-70">({{ $tabCounts[$key] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    {{-- ============ SEARCH + FILTERS ============ --}}
    <div class="card-solid space-y-4 rounded-3xl border border-app p-5 shadow-sm">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                <input x-ref="searchInput" name="q" value="{{ $q }}" placeholder="Search reference, customer, email…" class="field !rounded-full pl-11 pr-4" @input.debounce.500ms="$el.form.requestSubmit()">
            </div>
            <label class="flex items-center gap-2 whitespace-nowrap rounded-full border border-app px-3 py-2 text-xs text-body"><input type="checkbox" name="risk" value="1" @checked(request('risk')==='1') class="rounded" onchange="this.form.requestSubmit()"> Risk-flagged only</label>
            <a href="{{ route('admin.shop.orders.index') }}" class="qa-btn">Clear filters</a>
        </form>
    </div>

    {{-- ============ TABLE ============ --}}
    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[1200px] text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-3">Reference</th>
                    <th class="px-3 py-3 font-medium">Customer</th>
                    <th class="px-3 py-3 font-medium">Items</th>
                    <th class="px-3 py-3 font-medium">Total</th>
                    <th class="px-3 py-3 font-medium">Payment</th>
                    <th class="px-3 py-3 font-medium">Status</th>
                    <th class="px-3 py-3 font-medium">Risk</th>
                    <th class="px-3 py-3 font-medium">Created</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @forelse ($orders as $order)
                    <tr class="hover:surface cursor-pointer" :class="{ 'surface-2': highlighted === {{ $loop->index }} }" @click="highlighted = {{ $loop->index }}; openDrawer({{ $order->id }})">
                        <td class="px-3 py-3 font-mono text-xs text-muted">{{ $order->reference }}</td>
                        <td class="px-3 py-3 text-body">{{ $order->user->name }}</td>
                        <td class="px-3 py-3 text-body">{{ $order->items_count }}</td>
                        <td class="px-3 py-3 font-semibold text-strong">{{ money($order->total, $order->currency) }}</td>
                        <td class="px-3 py-3 text-xs capitalize text-body">{{ str_replace('_',' ',$order->payment_source) }}</td>
                        <td class="px-3 py-3"><x-status-badge :status="$order->status" class="text-[10px]" /></td>
                        <td class="px-3 py-3">@if($order->risk_flagged)<span class="pill bg-rose-500/15 text-rose-600 text-[10px]">Flagged</span>@else<span class="text-faint">—</span>@endif</td>
                        <td class="px-3 py-3 text-xs text-faint">{{ $order->created_at->diffForHumans() }}</td>
                        <td class="px-3 py-3 text-right" @click.stop>
                            <a href="{{ route('admin.shop.orders.show', $order) }}" class="text-brand-500 text-sm">Open →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-0">
                        @if ($q || request('status') || request('tab', 'all') !== 'all')
                            <x-empty icon="bag" title="No shop orders found" message="No orders match the selected status, date range, or filters.">
                                <x-slot:action>
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('admin.shop.orders.index') }}" class="qa-btn">Clear filters</a>
                                        <button type="button" class="qa-btn" @click="window.location.reload()">Refresh orders</button>
                                    </div>
                                </x-slot:action>
                            </x-empty>
                        @else
                            <x-empty icon="bag" title="Your first shop order will appear here" message="Orders from physical products, eSIMs, airtime, bill payments, gift cards, and digital services will be managed from this page." />
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $orders->links() }}</div>

    @include('admin.shop.orders.partials.modals')
</div>
@endsection

@push('scripts')
<script>
function ordersConsole() {
    return {
        drawerOpen: false, drawer: null,
        cancelModal: false, refundModal: false, shipModal: false, assignModal: false, noteModal: false,
        orderIds: @json($orders->pluck('id')),
        highlighted: -1,
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('orders-search', () => this.$refs.searchInput?.focus());
                window.ShortcutManager.registerAction('orders-next', () => this.moveHighlight(1));
                window.ShortcutManager.registerAction('orders-prev', () => this.moveHighlight(-1));
                window.ShortcutManager.registerAction('orders-open', () => { if (this.highlighted >= 0) this.openDrawer(this.orderIds[this.highlighted]); });
                window.ShortcutManager.registerAction('orders-refresh', () => window.location.reload());
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; this.cancelModal = false; this.refundModal = false; this.shipModal = false; this.assignModal = false; this.noteModal = false; });
        },
        moveHighlight(delta) {
            if (this.orderIds.length === 0) return;
            this.highlighted = (this.highlighted + delta + this.orderIds.length) % this.orderIds.length;
        },
        async openDrawer(id) {
            this.drawerOpen = true;
            this.drawer = null;
            const res = await fetch(`/admin/shop/orders/${id}/row-detail`);
            this.drawer = await res.json();
        },
    };
}
</script>
@endpush
