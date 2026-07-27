{{-- Marketplace Operations. Real ShopOrderStatus values only: pending → paid → fulfilled,
     with failed/refunded as terminal exception states (there is no separate
     "processing"/"shipped" status in this app's order model). --}}
<x-glass-card solid>
    <h3 class="font-semibold text-strong">Marketplace operations · {{ $period['label'] }}</h3>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div><p class="text-xs text-faint">Gross sales</p><p class="text-lg font-bold text-strong">{{ money($marketplace['gross'], $currency) }}</p></div>
        <div><p class="text-xs text-faint">Net sales</p><p class="text-lg font-bold text-strong">{{ money($marketplace['net'], $currency) }}</p></div>
        <div><p class="text-xs text-faint">Orders</p><p class="text-lg font-bold text-strong">{{ $marketplace['orderCount'] }}</p></div>
        <div><p class="text-xs text-faint">Avg order value</p><p class="text-lg font-bold text-strong">{{ money($marketplace['aov'], $currency) }}</p></div>
    </div>

    <div class="mt-4 border-t border-app pt-3">
        <p class="mb-2 text-xs font-semibold uppercase text-faint">Order pipeline (Pending → Paid → Fulfilled)</p>
        <div class="flex items-center gap-2 text-xs">
            @foreach (['pending' => 'Pending', 'paid' => 'Paid', 'fulfilled' => 'Fulfilled', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $st => $lbl)
                @php $row = $marketplace['byStatus']->get($st); @endphp
                <a href="{{ route('admin.shop.orders.index', ['status' => $st]) }}" class="flex-1 rounded-xl surface-2 p-2 text-center hover:surface">
                    <p class="font-bold text-strong">{{ $row->n ?? 0 }}</p>
                    <p class="text-faint">{{ $lbl }}</p>
                </a>
            @endforeach
        </div>
    </div>

    <div class="mt-4 border-t border-app pt-3">
        <p class="mb-2 text-xs font-semibold uppercase text-faint">Top products</p>
        <div class="space-y-1.5">
            @forelse ($marketplace['topProducts'] as $item)
                <div class="flex items-center justify-between text-sm"><span class="truncate text-body">{{ $item->name }} <span class="text-faint">× {{ $item->qty }}</span></span><span class="font-semibold text-strong">{{ money($item->total, $currency) }}</span></div>
            @empty
                <p class="text-xs text-faint">No paid orders in this period.</p>
            @endforelse
        </div>
    </div>
</x-glass-card>
