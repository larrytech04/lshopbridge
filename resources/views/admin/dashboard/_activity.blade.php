{{-- Recent Admin Activity — reuses the existing AuditLog system. --}}
<x-glass-card solid>
    <h3 class="font-semibold text-strong">Recent admin activity</h3>
    <div class="mt-3 max-h-80 space-y-2 overflow-y-auto">
        @forelse ($activity as $a)
            <div class="rounded-lg px-2 py-1.5 text-sm">
                <p class="text-body">{{ $a->description ?? $a->action }}</p>
                <p class="text-[11px] text-faint">{{ $a->user->name ?? 'System' }} · {{ $a->ip }} · {{ $a->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="text-xs text-faint">No admin actions recorded yet.</p>
        @endforelse
    </div>
</x-glass-card>
