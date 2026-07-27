{{-- Command Center header: title, live clock/connection, date-range + comparison, refresh/export/customize. --}}
{{-- Sticky only from lg up: on mobile this card's content wraps across many
     rows and pinning it would eat the whole viewport while scrolling. --}}
<div class="card-solid lg:sticky lg:top-16 z-20 space-y-4 rounded-3xl border border-app p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Admin Command Center</h1>
            <p class="text-sm text-muted">Real-time visibility across payments, wallets, customers, agents, marketplace operations, compliance, and system health.</p>
        </div>
        <div class="flex items-center gap-3 text-xs text-faint" x-data="{ now: new Date(), online: navigator.onLine }"
             x-init="setInterval(() => now = new Date(), 1000); window.addEventListener('online', () => online = true); window.addEventListener('offline', () => online = false)">
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" :class="online ? 'bg-emerald-500' : 'bg-rose-500'"></span><span x-text="online ? 'Live' : 'Offline'"></span></span>
            <span x-text="now.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'medium' })"></span>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap items-center gap-2 border-t border-app pt-4">
        <select name="period" x-ref="periodSelect" onchange="this.form.requestSubmit()" class="field max-w-[10rem] !py-1.5 text-xs">
            @foreach (['today'=>'Today','yesterday'=>'Yesterday','7d'=>'Last 7 days','14d'=>'Last 14 days','30d'=>'Last 30 days','this_month'=>'This month','last_month'=>'Previous month','quarter'=>'This quarter','year'=>'This year'] as $val => $lbl)
                <option value="{{ $val }}" @selected($period['key'] === $val)>{{ $lbl }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-1.5 text-xs text-body"><input type="checkbox" name="compare" value="1" @checked($period['compare']) onchange="this.form.requestSubmit()" class="rounded"> Compare with previous period</label>
        <span class="pill surface text-[11px] text-faint">{{ $period['from']->format('M j, Y') }} – {{ $period['to']->format('M j, Y') }}</span>
        @if ($period['compare'])
            <span class="pill surface text-[11px] text-faint">vs {{ $period['prevFrom']->format('M j') }} – {{ $period['prevTo']->format('M j, Y') }}</span>
        @endif

        <div class="ml-auto flex flex-wrap items-center gap-2">
            <button type="button" class="qa-btn" @click="document.getElementById('attention')?.scrollIntoView({behavior:'smooth'})"><x-icon name="bell" class="h-3.5 w-3.5" /> {{ count($attention) }} need{{ count($attention) === 1 ? 's' : '' }} attention</button>
            <button type="button" class="qa-btn" @click="window.location.reload()"><x-icon name="refresh" class="h-3.5 w-3.5" /> Refresh</button>
            <a href="{{ route('admin.dashboard.export', request()->query()) }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export report</a>
            <button type="button" class="qa-btn" @click="customizeOpen = true"><x-icon name="cog" class="h-3.5 w-3.5" /> Customize</button>
        </div>
    </form>
</div>
