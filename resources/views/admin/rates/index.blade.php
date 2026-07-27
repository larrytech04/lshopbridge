@extends('layouts.admin')
@section('page-title', 'Exchange Rates')

@section('content')
@php
    $summaryCards = [
        ['Active pairs', $summary['active_pairs'], 'check-circle', 'emerald'],
        ['Inactive pairs', $summary['inactive_pairs'], 'ban', 'gray'],
        ['Automatic rates', $summary['automatic'], 'refresh', 'sky'],
        ['Manually managed', $summary['manual'], 'cog', 'slate'],
        ['Updated today', $summary['updated_today'], 'clock', 'sky'],
        ['Requires attention', $summary['requires_attention'], 'alert', 'amber'],
    ];
    $lastUpdate = $rates->sortByDesc('updated_at')->first()?->updated_at;
@endphp

<div x-data="ratesConsole()" x-init="init()" class="space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Exchange Rates</h1>
            <p class="text-sm text-muted">Manage base rates, platform margins, effective customer rates, and currency-pair availability.</p>
            <p class="mt-1 text-xs text-faint">
                Reporting currency: {{ config('platform.base_currency') }} · Last update: {{ $lastUpdate?->diffForHumans() ?? 'never' }} ·
                Rate source: manual (no automatic FX provider connected) · Auto-update: off
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="qa-btn qa-btn-good" @click="openAdd()"><x-icon name="plus" class="h-3.5 w-3.5" /> Add rate</button>
            <button type="button" class="qa-btn" @click="window.location.reload()"><x-icon name="refresh" class="h-3.5 w-3.5" /> Refresh</button>
            <button type="button" class="qa-btn" @click="calcOpen = true"><x-icon name="gauge" class="h-3.5 w-3.5" /> Rate calculator</button>
            <a href="{{ route('admin.rates.export') }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export</a>
            <a href="{{ route('admin.settings.index') }}" class="qa-btn"><x-icon name="cog" class="h-3.5 w-3.5" /> Rate settings</a>
        </div>
    </div>

    {{-- ============ SUMMARY CARDS ============ --}}
    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-6">
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

    {{-- ============ SEARCH + FILTERS ============ --}}
    <div class="card-solid space-y-4 rounded-3xl border border-app p-5 shadow-sm">
        <form method="GET" id="filter-form" class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                    <input x-ref="searchInput" name="q" value="{{ $q }}" placeholder="Search source currency, destination currency, pair, or rate source…"
                           class="field !rounded-full pl-11 pr-4" @input.debounce.500ms="$el.form.requestSubmit()">
                </div>
                <button type="button" class="qa-btn" @click="filtersOpen = !filtersOpen"><x-icon name="filter" class="h-3.5 w-3.5" /> Filters <span x-show="activeFilterCount() > 0" x-text="'(' + activeFilterCount() + ')'"></span></button>
                <a href="{{ route('admin.rates.index') }}" class="qa-btn">Clear filters</a>
            </div>

            <div x-show="filtersOpen" x-collapse x-cloak class="grid gap-3 border-t border-app pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <select name="active" class="field"><option value="">Any status</option><option value="1" @selected(request('active')==='1')>Active</option><option value="0" @selected(request('active')==='0')>Inactive</option></select>
                <select name="rate_source" class="field"><option value="">Any update method</option>@foreach (\App\Enums\ExchangeRateSource::cases() as $s)<option value="{{ $s->value }}" @selected(request('rate_source')===$s->value)>{{ $s->label() }}</option>@endforeach</select>
                <select name="quote_currency" class="field"><option value="">Any destination currency</option>@foreach ($currencies as $c)<option value="{{ $c->code }}" @selected(request('quote_currency')===$c->code)>{{ $c->code }}</option>@endforeach</select>
                <input type="date" name="updated_since" value="{{ request('updated_since') }}" class="field" title="Updated since">
                <div class="sm:col-span-4"><button class="btn btn-primary text-sm">Apply filters</button></div>
            </div>
        </form>

        <div x-show="selected.length > 0" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
            <span class="text-xs font-semibold text-strong" x-text="selected.length + ' selected'"></span>
            <button type="button" class="qa-btn qa-btn-good" @click="runBulk('activate')">Activate</button>
            <button type="button" class="qa-btn qa-btn-warn" @click="runBulk('deactivate')">Deactivate</button>
            <button type="button" class="qa-btn" @click="runBulk('review')">Mark for review</button>
            <span class="text-[11px] text-faint">Bulk editing of rate values or margins is disabled by design — review each pair individually.</span>
        </div>
    </div>

    {{-- ============ TABLE ============ --}}
    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[1200px] text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded"></th>
                    <th class="px-3 py-3 font-medium">Currency pair</th>
                    <th class="px-3 py-3 font-medium">Base rate</th>
                    <th class="px-3 py-3 font-medium">Margin</th>
                    <th class="px-3 py-3 font-medium">Effective rate</th>
                    <th class="px-3 py-3 font-medium">Source</th>
                    <th class="px-3 py-3 font-medium">Update method</th>
                    <th class="px-3 py-3 font-medium">Last updated</th>
                    <th class="px-3 py-3 font-medium">Next update</th>
                    <th class="px-3 py-3 font-medium">Status</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @php $rateSvc = app(\App\Services\Admin\ExchangeRateAdminService::class); $fxSvc = app(\App\Services\Funding\RateService::class); @endphp
                @forelse ($rates as $rate)
                    @php
                        $status = $rateSvc->computeStatus($rate);
                        $upcoming = $fxSvc->upcomingSchedule($rate->base_currency, $rate->quote_currency);
                    @endphp
                    <tr class="hover:surface cursor-pointer" :class="{ 'surface-2': highlighted === {{ $loop->index }} }" @click="highlighted = {{ $loop->index }}; openDrawer({{ $rate->id }})">
                        <td class="px-3 py-3" @click.stop><input type="checkbox" value="{{ $rate->id }}" x-model="selected" class="rounded"></td>
                        <td class="px-3 py-3 font-semibold text-strong">{{ $rate->pair() }}</td>
                        <td class="px-3 py-3 font-mono text-xs text-body">{{ rtrim(rtrim(number_format((float) $rate->rate, 8), '0'), '.') }}</td>
                        <td class="px-3 py-3 text-xs text-body">
                            @if ($rate->margin_type->value === 'percentage'){{ number_format((float) $rate->margin_percent, 4) }}%
                            @elseif ($rate->margin_type->value === 'fixed')−{{ rtrim(rtrim(number_format((float) $rate->margin_fixed, 8), '0'), '.') }}
                            @else custom @endif
                        </td>
                        <td class="px-3 py-3 font-mono text-xs font-semibold text-strong">{{ rtrim(rtrim(number_format($rate->effectiveRate(), 8), '0'), '.') }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $rate->rate_source->label() }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $rate->margin_type->label() }}</td>
                        <td class="px-3 py-3 text-xs text-faint">{{ $rate->updated_at->diffForHumans() }}</td>
                        <td class="px-3 py-3 text-xs text-faint">{{ $upcoming ? $upcoming->effective_from->format('M j, Y') : '—' }}</td>
                        <td class="px-3 py-3"><span class="pill {{ $status->color() }} text-[10px]">{{ $status->label() }}</span></td>
                        <td class="px-3 py-3 text-right" @click.stop>
                            <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="chevron-down" class="h-4 w-4" /></button>
                                <div x-show="open" x-cloak x-transition class="card-solid absolute right-0 z-20 mt-1 w-52 rounded-xl border border-app p-1.5 text-left shadow-lg">
                                    <button type="button" @click="openDrawer({{ $rate->id }}); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="eye" class="h-4 w-4" /> View details</button>
                                    <button type="button" @click="openEdit({{ $rate->id }}, @js($rate->only(['id','base_currency','quote_currency','rate','margin_type','margin_percent','margin_fixed','custom_effective_rate','rate_source','min_amount','max_amount','notes','is_active']))); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="cog" class="h-4 w-4" /> Edit rate</button>
                                    <button type="button" @click="calcBase='{{ $rate->base_currency }}'; calcQuote='{{ $rate->quote_currency }}'; calcOpen=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="gauge" class="h-4 w-4" /> Open calculator</button>
                                    <button type="button" @click="scheduleTarget={{ $rate->id }}; scheduleBase='{{ $rate->base_currency }}'; scheduleQuote='{{ $rate->quote_currency }}'; scheduleModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="clock" class="h-4 w-4" /> Schedule change</button>
                                    <form method="POST" action="{{ route('admin.rates.toggle-active', $rate) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="{{ $rate->is_active ? 'ban' : 'check' }}" class="h-4 w-4" /> {{ $rate->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                                    <form method="POST" action="{{ route('admin.rates.destroy', $rate) }}" onsubmit="return confirm('Archive this rate? Historical transactions already kept their own rate snapshot and are unaffected.')">@csrf @method('DELETE')<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="trash" class="h-4 w-4" /> Archive</button></form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="p-0">
                        <x-empty icon="wallet" title="No exchange rates configured" message="Add a currency pair to begin calculating China wallet funding and marketplace conversions.">
                            <x-slot:action>
                                <div class="flex justify-center gap-2">
                                    <button type="button" class="qa-btn qa-btn-good" @click="openAdd()">Add rate</button>
                                    <a href="{{ route('admin.settings.index') }}" class="qa-btn">Rate settings</a>
                                </div>
                            </x-slot:action>
                        </x-empty>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.rates.partials.modals')
</div>
@endsection

@push('scripts')
<script>
function ratesConsole() {
    return {
        filtersOpen: false,
        selected: [],
        drawerOpen: false, drawer: null,
        formOpen: false, formMode: 'add', form: {}, formErrors: null, formWarnings: null,
        calcOpen: false, calcBase: '{{ config('platform.base_currency') }}', calcQuote: '{{ config('platform.target_currency') }}',
        calcAmount: 100000, calcFee: 0, calcResult: null,
        scheduleModal: false, scheduleTarget: null, scheduleBase: '', scheduleQuote: '',
        rateIds: @json($rates->pluck('id')),
        highlighted: -1,
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('rates-search', () => this.$refs.searchInput?.focus());
                window.ShortcutManager.registerAction('rates-filters', () => { this.filtersOpen = !this.filtersOpen; });
                window.ShortcutManager.registerAction('rates-add', () => this.openAdd());
                window.ShortcutManager.registerAction('rates-refresh', () => window.location.reload());
                window.ShortcutManager.registerAction('rates-calculator', () => { this.calcOpen = true; });
                window.ShortcutManager.registerAction('rates-next', () => this.moveHighlight(1));
                window.ShortcutManager.registerAction('rates-prev', () => this.moveHighlight(-1));
                window.ShortcutManager.registerAction('rates-open', () => { if (this.highlighted >= 0) this.openDrawer(this.rateIds[this.highlighted]); });
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; this.formOpen = false; this.calcOpen = false; this.scheduleModal = false; });
            this.$watch('calcOpen', (v) => { if (v) this.runCalculator(); });
            this.$watch('calcAmount', () => { if (this.calcOpen) this.runCalculator(); });
            this.$watch('calcBase', () => { if (this.calcOpen) this.runCalculator(); });
            this.$watch('calcQuote', () => { if (this.calcOpen) this.runCalculator(); });
            this.$watch('calcFee', () => { if (this.calcOpen) this.runCalculator(); });
        },
        activeFilterCount() {
            const p = new URLSearchParams(window.location.search);
            return ['active', 'rate_source', 'quote_currency', 'updated_since'].filter((k) => p.get(k)).length;
        },
        toggleAll(checked) { this.selected = checked ? @json($rates->pluck('id')) : []; },
        moveHighlight(delta) {
            if (this.rateIds.length === 0) return;
            this.highlighted = (this.highlighted + delta + this.rateIds.length) % this.rateIds.length;
        },
        runBulk(action) {
            if (this.selected.length === 0) return;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = '{{ route('admin.rates.bulk-action') }}';
            let html = '@csrf' + `<input type="hidden" name="action" value="${action}">`;
            this.selected.forEach((id) => { html += `<input type="hidden" name="ids[]" value="${id}">`; });
            f.innerHTML = html;
            document.body.appendChild(f); f.submit();
        },
        async openDrawer(id) {
            this.drawerOpen = true;
            this.drawer = null;
            try {
                const res = await fetch(`/admin/rates/${id}/row-detail`);
                this.drawer = await res.json();
            } catch (e) { this.drawerOpen = false; }
        },
        openAdd() {
            this.formMode = 'add';
            this.form = { base_currency: '{{ config('platform.base_currency') }}', quote_currency: '{{ config('platform.target_currency') }}', rate: '', margin_type: 'percentage', margin_percent: 1.5, margin_fixed: '', custom_effective_rate: '', rate_source: 'manual', min_amount: '', max_amount: '', notes: '', is_active: true };
            this.formErrors = null; this.formWarnings = null;
            this.formOpen = true;
        },
        openEdit(id, data) {
            this.formMode = 'edit';
            this.form = { ...data };
            this.formErrors = null; this.formWarnings = null;
            this.formOpen = true;
        },
        async runCalculator() {
            const body = new URLSearchParams({ amount: this.calcAmount, base_currency: this.calcBase, quote_currency: this.calcQuote, additional_fee: this.calcFee || 0 });
            const res = await fetch('{{ route('admin.rates.calculate') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body,
            });
            this.calcResult = res.ok ? await res.json() : null;
        },
        formAction() {
            return this.formMode === 'add' ? '{{ route('admin.rates.store') }}' : `/admin/rates/${this.form.id}`;
        },
    };
}
</script>
@endpush
