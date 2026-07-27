{{-- Payment Provider Health, derived from real webhook history. No API-response-time or
     uptime metric is fabricated — the schema doesn't track that, so status is derived
     honestly from actual webhook success/failure counts in the last 24h. --}}
<x-glass-card solid id="providers">
    <h3 class="font-semibold text-strong">Payment provider health</h3>
    <div class="mt-3 space-y-2">
        @forelse ($providers as $ph)
            @php
                $color = match ($ph['status']) { 'Operational' => 'emerald', 'Degraded' => 'amber', 'Partial outage' => 'rose', 'Offline' => 'slate', default => 'sky' };
            @endphp
            <div class="flex items-center justify-between rounded-xl surface-2 p-3">
                <div>
                    <p class="text-sm font-semibold text-strong">{{ $ph['provider']->name }}</p>
                    <p class="text-[11px] text-faint">{{ $ph['total24h'] }} webhooks (24h) @if($ph['successRate'] !== null) · {{ $ph['successRate'] }}% success @endif</p>
                </div>
                <span class="pill bg-{{ $color }}-500/15 text-{{ $color }}-600 text-[10px] uppercase">{{ $ph['status'] }}</span>
            </div>
        @empty
            <p class="text-xs text-faint">No payment providers configured.</p>
        @endforelse
    </div>
    <a href="{{ route('admin.webhooks.index') }}" class="mt-3 inline-block text-xs font-semibold text-brand-600 hover:text-brand-700">View webhook log →</a>
</x-glass-card>
