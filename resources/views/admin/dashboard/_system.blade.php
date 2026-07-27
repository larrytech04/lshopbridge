{{-- Platform System Health. Only metrics PHP/Laravel can safely and accurately report —
     no CPU/memory, no WebSocket/email/SMS delivery status, since none of that is
     instrumented in this app (would need new packages/services, not fabricated numbers). --}}
<x-glass-card solid>
    <h3 class="font-semibold text-strong">System health</h3>
    <div class="mt-3 grid grid-cols-2 gap-3">
        <div class="flex items-center gap-2 rounded-xl surface-2 p-3"><span class="h-2.5 w-2.5 rounded-full {{ $system['database'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span><span class="text-sm text-body">Database {{ $system['database'] ? 'connected' : 'unreachable' }}</span></div>
        <div class="flex items-center gap-2 rounded-xl surface-2 p-3"><span class="h-2.5 w-2.5 rounded-full {{ $system['cache'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span><span class="text-sm text-body">Cache {{ $system['cache'] ? 'working' : 'unreachable' }}</span></div>
    </div>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div><p class="text-xs text-faint">Queue backlog</p><p class="text-lg font-bold {{ $system['queueBacklog'] > 100 ? 'text-amber-600' : 'text-strong' }}">{{ $system['queueBacklog'] }}</p></div>
        <div><p class="text-xs text-faint">Failed jobs</p><p class="text-lg font-bold {{ $system['failedJobs'] > 0 ? 'text-rose-600' : 'text-strong' }}">{{ $system['failedJobs'] }}</p></div>
        <div><p class="text-xs text-faint">Active sessions</p><p class="text-lg font-bold text-strong">{{ $system['activeSessions'] }}</p></div>
        <div><p class="text-xs text-faint">Disk used</p><p class="text-lg font-bold {{ ($system['diskUsedPct'] ?? 0) > 90 ? 'text-rose-600' : 'text-strong' }}">{{ $system['diskUsedPct'] !== null ? $system['diskUsedPct'].'%' : 'Data unavailable' }}</p></div>
    </div>
    @if ($system['failedJobs'] > 0)
        <p class="mt-3 rounded-lg bg-rose-500/10 px-3 py-2 text-xs text-rose-600">{{ $system['failedJobs'] }} background job{{ $system['failedJobs']===1?'':'s' }} failed and need review.</p>
    @endif
</x-glass-card>
