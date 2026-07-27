{{-- Agent Network Overview. Note: no commission/payout concept exists anywhere in this
     app yet — omitted rather than fabricated. --}}
<x-glass-card solid>
    <h3 class="font-semibold text-strong">Agent network</h3>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div><p class="text-xs text-faint">Total agents</p><p class="text-lg font-bold text-strong">{{ $agents['total'] }}</p></div>
        <div><p class="text-xs text-faint">Approved</p><p class="text-lg font-bold text-strong">{{ $agents['approved'] }}</p></div>
        <div><p class="text-xs text-faint">Pending</p><p class="text-lg font-bold text-strong">{{ $agents['pending'] }}</p></div>
        <div><p class="text-xs text-faint">Avg rating</p><p class="text-lg font-bold text-strong">★ {{ $agents['avgRating'] }}</p></div>
    </div>

    <div class="mt-4 border-t border-app pt-3">
        <p class="mb-2 text-xs font-semibold uppercase text-faint">Top-performing agents</p>
        <div class="space-y-1.5">
            @forelse ($agents['topAgents'] as $a)
                <a href="{{ route('admin.agents.show', $a) }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm hover:surface">
                    <span class="text-body">{{ $a->business_name }}</span>
                    <span class="text-faint">★ {{ number_format($a->rating, 1) }} · {{ $a->completed_orders }} orders</span>
                </a>
            @empty
                <p class="text-xs text-faint">No approved agents yet.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-4 border-t border-app pt-3">
        <p class="mb-2 text-xs font-semibold uppercase text-faint">Agents by warehouse country</p>
        <div class="flex flex-wrap gap-2">
            @forelse ($agents['byCountry'] as $row)
                <span class="pill surface text-xs text-body">{{ $row->warehouseCountry->name ?? 'Unknown' }}: {{ $row->n }}</span>
            @empty
                <p class="text-xs text-faint">No agent data yet.</p>
            @endforelse
        </div>
    </div>
</x-glass-card>
