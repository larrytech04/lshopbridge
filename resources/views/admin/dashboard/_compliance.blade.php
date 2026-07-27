{{-- Compliance & Risk Center. Note: the risk-flagging engine exists structurally
     (risk_flags table + severity/status columns) but no code path in this app has
     ever created a row yet, so counts below will genuinely read 0 until it's wired
     up — that's an accurate reading, not a placeholder. --}}
<x-glass-card solid>
    <h3 class="font-semibold text-strong">Compliance & risk</h3>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div><p class="text-xs text-faint">Pending KYC</p><p class="text-lg font-bold text-strong">{{ $compliance['pendingKyc'] }}</p></div>
        <div><p class="text-xs text-faint">Approved KYC</p><p class="text-lg font-bold text-strong">{{ $compliance['approvedKyc'] }}</p></div>
        <div><p class="text-xs text-faint">Rejected KYC</p><p class="text-lg font-bold text-strong">{{ $compliance['rejectedKyc'] }}</p></div>
        <div><p class="text-xs text-faint">Manual reviews</p><p class="text-lg font-bold text-strong">{{ $compliance['manualReviews'] }}</p></div>
        <div><p class="text-xs text-faint">Open risk flags</p><p class="text-lg font-bold text-strong">{{ $compliance['openFlags'] }}</p></div>
        <div><p class="text-xs text-faint">Flagged transactions</p><p class="text-lg font-bold text-strong">{{ $compliance['flaggedDeposits'] + $compliance['flaggedFunding'] }}</p></div>
    </div>

    <div class="mt-4 border-t border-app pt-3">
        <p class="mb-2 text-xs font-semibold uppercase text-faint">Risk flags (14 days)</p>
        @php $maxR = max(1, $compliance['trend']->max('count')); @endphp
        <div class="flex h-16 items-end gap-1">
            @foreach ($compliance['trend'] as $d)
                <div class="flex-1 rounded-t bg-rose-500/70" style="height: {{ max(2, ($d['count']/$maxR)*100) }}%" title="{{ $d['label'] }}: {{ $d['count'] }}"></div>
            @endforeach
        </div>
    </div>

    <div class="mt-4 border-t border-app pt-3">
        <p class="mb-2 text-xs font-semibold uppercase text-faint">Risk alert queue</p>
        <div class="space-y-1.5">
            @forelse ($compliance['alerts'] as $a)
                <a href="{{ route('admin.risk.index') }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm hover:surface">
                    <span class="text-body">{{ $a->rule_code }} · {{ $a->user->name ?? 'Unknown' }}</span>
                    <span class="pill bg-{{ $a->severity === 'high' ? 'rose' : ($a->severity === 'medium' ? 'amber' : 'sky') }}-500/15 text-{{ $a->severity === 'high' ? 'rose' : ($a->severity === 'medium' ? 'amber' : 'sky') }}-600 text-[10px]">{{ ucfirst($a->severity) }}</span>
                </a>
            @empty
                <p class="text-xs text-faint">No risk alerts recorded — the automated risk engine isn't actively flagging transactions yet, so this reflects real (zero) activity, not missing data.</p>
            @endforelse
        </div>
    </div>
</x-glass-card>
