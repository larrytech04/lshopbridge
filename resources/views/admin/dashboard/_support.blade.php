{{-- Support Operations. --}}
<x-glass-card solid>
    <h3 class="font-semibold text-strong">Support operations</h3>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div><p class="text-xs text-faint">Open tickets</p><p class="text-lg font-bold text-strong">{{ $support['open'] }}</p></div>
        <div><p class="text-xs text-faint">Urgent</p><p class="text-lg font-bold text-strong">{{ $support['urgent'] }}</p></div>
        <div><p class="text-xs text-faint">Unassigned</p><p class="text-lg font-bold text-strong">{{ $support['unassigned'] }}</p></div>
        <div><p class="text-xs text-faint">Resolved today</p><p class="text-lg font-bold text-strong">{{ $support['resolvedToday'] }}</p></div>
        <div><p class="text-xs text-faint">Avg first response</p><p class="text-lg font-bold text-strong">{{ $support['avgFirstResponseMin'] !== null ? $support['avgFirstResponseMin'].'m' : '—' }}</p></div>
        <div><p class="text-xs text-faint">Avg resolution</p><p class="text-lg font-bold text-strong">{{ $support['avgResolutionHrs'] !== null ? $support['avgResolutionHrs'].'h' : '—' }}</p></div>
    </div>

    <div class="mt-4 border-t border-app pt-3">
        <p class="mb-2 text-xs font-semibold uppercase text-faint">Latest tickets</p>
        <div class="space-y-1.5">
            @forelse ($support['latest'] as $d)
                <a href="{{ route('admin.disputes.index') }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm hover:surface">
                    <span class="truncate text-body">{{ $d->subject }}</span>
                    <x-status-badge :status="$d->status" class="text-[10px]" />
                </a>
            @empty
                <p class="text-xs text-faint">No support tickets yet.</p>
            @endforelse
        </div>
    </div>
</x-glass-card>
