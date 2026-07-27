@extends('layouts.admin')
@section('page-title', 'Fees & Charges')

@section('content')
@php
    $summaryCards = [
        ['Total fee rules', $summary['total_rules'], 'list', 'slate'],
        ['Active fees', $summary['active'], 'check-circle', 'emerald'],
        ['Inactive fees', $summary['inactive'], 'ban', 'gray'],
        ['Scheduled fees', $summary['scheduled'], 'clock', 'sky'],
        ['Percentage fees', $summary['percentage'], 'chart', 'sky'],
        ['Fixed fees', $summary['fixed'], 'wallet', 'slate'],
        ['Requiring review', $summary['requiring_review'], 'alert', 'amber'],
        ['Fee revenue this month', money($summary['revenue_this_month'], config('platform.base_currency')), 'gauge', 'emerald'],
    ];
    $lastUpdate = $fees->sortByDesc('updated_at')->first()?->updated_at;
@endphp

<div x-data="feesConsole()" x-init="init()" class="space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Fees & Charges</h1>
            <p class="text-sm text-muted">Manage platform fees, processing charges, limits, exemptions, and customer pricing rules.</p>
            <p class="mt-1 text-xs text-faint">
                Active fees: {{ $summary['active'] }} · Last update: {{ $lastUpdate?->diffForHumans() ?? 'never' }} ·
                Default currency: {{ config('platform.base_currency') }} · Fee calculation: centralized (FeeCalculationService)
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="qa-btn qa-btn-good" @click="openAdd()"><x-icon name="plus" class="h-3.5 w-3.5" /> Add fee</button>
            <button type="button" class="qa-btn" @click="calcOpen = true"><x-icon name="gauge" class="h-3.5 w-3.5" /> Fee calculator</button>
            <a href="{{ route('admin.fees.export') }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export</a>
            <button type="button" class="qa-btn" @click="window.location.reload()"><x-icon name="refresh" class="h-3.5 w-3.5" /> Refresh</button>
            <a href="{{ route('admin.settings.index') }}" class="qa-btn"><x-icon name="cog" class="h-3.5 w-3.5" /> Fee settings</a>
        </div>
    </div>

    {{-- ============ SUMMARY CARDS ============ --}}
    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-4 xl:grid-cols-8">
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
                    <input x-ref="searchInput" name="q" value="{{ $q }}" placeholder="Search fee name, code, service, method, provider, currency, or country…"
                           class="field !rounded-full pl-11 pr-4" @input.debounce.500ms="$el.form.requestSubmit()">
                </div>
                <button type="button" class="qa-btn" @click="filtersOpen = !filtersOpen"><x-icon name="filter" class="h-3.5 w-3.5" /> Filters <span x-show="activeFilterCount() > 0" x-text="'(' + activeFilterCount() + ')'"></span></button>
                <a href="{{ route('admin.fees.index') }}" class="qa-btn">Clear filters</a>
            </div>

            <div x-show="filtersOpen" x-collapse x-cloak class="grid gap-3 border-t border-app pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <select name="active" class="field"><option value="">Any status</option><option value="1" @selected(request('active')==='1')>Active</option><option value="0" @selected(request('active')==='0')>Inactive</option></select>
                <select name="applies_to" class="field"><option value="">Any category</option>@foreach ($categories as $key => $label)<option value="{{ $key }}" @selected(request('applies_to')===$key)>{{ $label }}</option>@endforeach<option value="all" @selected(request('applies_to')==='all')>All categories</option></select>
                <select name="type" class="field"><option value="">Any fee type</option>@foreach ($feeTypes as $t)<option value="{{ $t->value }}" @selected(request('type')===$t->value)>{{ $t->label() }}</option>@endforeach</select>
                <select name="currency" class="field"><option value="">Any currency</option>@foreach ($currencies as $c)<option value="{{ $c->code }}" @selected(request('currency')===$c->code)>{{ $c->code }}</option>@endforeach</select>
                <select name="country" class="field"><option value="">Any country</option>@foreach ($countries as $c)<option value="{{ $c->iso2 }}" @selected(request('country')===$c->iso2)>{{ $c->name }}</option>@endforeach</select>
                <input name="payment_provider" value="{{ request('payment_provider') }}" placeholder="Payment provider" class="field">
                <select name="customer_role" class="field"><option value="">Any customer role</option>@foreach (\App\Enums\UserRole::cases() as $r)<option value="{{ $r->value }}" @selected(request('customer_role')===$r->value)>{{ ucfirst($r->value) }}</option>@endforeach</select>
                <input type="date" name="updated_since" value="{{ request('updated_since') }}" class="field" title="Effective / updated since">
                <select name="automatic" class="field"><option value="">Automatic or manual</option><option value="1" @selected(request('automatic')==='1')>Automatic (provider-passed)</option><option value="0" @selected(request('automatic')==='0')>Manual</option></select>
                <div class="sm:col-span-2 lg:col-span-4"><button class="btn btn-primary text-sm">Apply filters</button></div>
            </div>
        </form>

        <div x-show="selected.length > 0" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
            <span class="text-xs font-semibold text-strong" x-text="selected.length + ' selected'"></span>
            <button type="button" class="qa-btn qa-btn-good" @click="runBulk('activate')">Activate</button>
            <button type="button" class="qa-btn qa-btn-warn" @click="runBulk('deactivate')">Deactivate</button>
            <button type="button" class="qa-btn" @click="runBulk('review')">Mark for review</button>
            <button type="button" class="qa-btn qa-btn-danger" @click="runBulk('archive')">Archive</button>
            <span class="text-[11px] text-faint">Bulk editing of fee values is disabled by design — review each rule individually.</span>
        </div>
    </div>

    {{-- ============ TABLE ============ --}}
    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[1200px] text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded"></th>
                    <th class="px-3 py-3 font-medium">Fee</th>
                    <th class="px-3 py-3 font-medium">Applies to</th>
                    <th class="px-3 py-3 font-medium">Type</th>
                    <th class="px-3 py-3 font-medium">Value</th>
                    <th class="px-3 py-3 font-medium">Limits</th>
                    <th class="px-3 py-3 font-medium">Currency</th>
                    <th class="px-3 py-3 font-medium">Country</th>
                    <th class="px-3 py-3 font-medium">Payment method</th>
                    <th class="px-3 py-3 font-medium">Effective date</th>
                    <th class="px-3 py-3 font-medium">Last updated</th>
                    <th class="px-3 py-3 font-medium">Status</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @php $feeSvc = app(\App\Services\Admin\FeeAdminService::class); @endphp
                @forelse ($fees as $fee)
                    @php $status = $feeSvc->computeStatus($fee); @endphp
                    <tr class="hover:surface cursor-pointer" :class="{ 'surface-2': highlighted === {{ $loop->index }} }" @click="highlighted = {{ $loop->index }}; openDrawer({{ $fee->id }})">
                        <td class="px-3 py-3" @click.stop><input type="checkbox" value="{{ $fee->id }}" x-model="selected" class="rounded"></td>
                        <td class="px-3 py-3">
                            <p class="font-semibold text-strong">{{ $fee->name }}</p>
                            <p class="text-[11px] text-faint">{{ $fee->code ?? '—' }}</p>
                        </td>
                        <td class="px-3 py-3 text-xs text-body">{{ $categories[$fee->applies_to] ?? ucfirst($fee->applies_to) }}@if($fee->scope)<br><span class="text-faint">{{ $fee->scope }}</span>@endif</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $fee->type->label() }}</td>
                        <td class="px-3 py-3 font-mono text-xs text-body">
                            @if ($fee->type->value === 'percent'){{ rtrim(rtrim(number_format((float) $fee->value, 4), '0'), '.') }}%
                            @elseif ($fee->type->value === 'fixed'){{ money($fee->value, $fee->currency ?? config('platform.base_currency')) }}
                            @elseif ($fee->type->value === 'fixed_plus_percent'){{ rtrim(rtrim(number_format((float) $fee->value, 4), '0'), '.') }}% + {{ money($fee->fixed_value ?? 0, $fee->currency ?? config('platform.base_currency')) }}
                            @elseif ($fee->type->value === 'tiered')<span class="text-faint">{{ $fee->tiers->count() }} tier(s)</span>
                            @else {{ rtrim(rtrim(number_format((float) $fee->value, 4), '0'), '.') }}% + markup
                            @endif
                        </td>
                        <td class="px-3 py-3 text-xs text-faint">{{ money($fee->min_fee, $fee->currency ?? config('platform.base_currency')) }} – {{ $fee->max_fee !== null ? money($fee->max_fee, $fee->currency ?? config('platform.base_currency')) : 'no max' }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $fee->currency ?? '—' }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $fee->country ?? '—' }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $fee->payment_provider ?? '—' }}</td>
                        <td class="px-3 py-3 text-xs text-faint">{{ $fee->effective_start_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-3 py-3 text-xs text-faint">{{ $fee->updated_at->diffForHumans() }}</td>
                        <td class="px-3 py-3"><span class="pill {{ $status->color() }} text-[10px]">{{ $status->label() }}</span></td>
                        <td class="px-3 py-3 text-right" @click.stop>
                            <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="chevron-down" class="h-4 w-4" /></button>
                                <div x-show="open" x-cloak x-transition class="card-solid absolute right-0 z-20 mt-1 w-52 rounded-xl border border-app p-1.5 text-left shadow-lg">
                                    <button type="button" @click="openDrawer({{ $fee->id }}); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="eye" class="h-4 w-4" /> View details</button>
                                    <button type="button" @click="openEdit({{ $fee->id }}); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="cog" class="h-4 w-4" /> Edit fee</button>
                                    <button type="button" @click="testTarget={{ $fee->id }}; testOpen=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="gauge" class="h-4 w-4" /> Test fee</button>
                                    <form method="POST" action="{{ route('admin.fees.duplicate', $fee) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="copy" class="h-4 w-4" /> Duplicate fee</button></form>
                                    <button type="button" @click="scheduleTarget={{ $fee->id }}; scheduleModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="clock" class="h-4 w-4" /> Schedule change</button>
                                    <form method="POST" action="{{ route('admin.fees.toggle-active', $fee) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="{{ $fee->is_active ? 'ban' : 'check' }}" class="h-4 w-4" /> {{ $fee->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                                    <form method="POST" action="{{ route('admin.fees.destroy', $fee) }}" onsubmit="return confirm('Archive this fee? Historical transactions already kept their own fee snapshot and are unaffected.')">@csrf @method('DELETE')<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="trash" class="h-4 w-4" /> Archive</button></form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="p-0">
                        <x-empty icon="wallet" title="No fee rules found" message="Create a fee rule to begin charging for deposits, funding, withdrawals, marketplace services, or other platform operations.">
                            <x-slot:action>
                                <div class="flex justify-center gap-2">
                                    <button type="button" class="qa-btn qa-btn-good" @click="openAdd()">Add fee</button>
                                    <a href="{{ route('admin.fees.index') }}" class="qa-btn">Clear filters</a>
                                    <a href="{{ route('admin.settings.index') }}" class="qa-btn">Open fee settings</a>
                                </div>
                            </x-slot:action>
                        </x-empty>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.fees.partials.modals')
</div>
@endsection

@push('scripts')
<script>
function feesConsole() {
    return {
        filtersOpen: false,
        selected: [],
        drawerOpen: false, drawer: null,
        formOpen: false, formMode: 'add', form: {}, tiers: [], formErrors: null, formWarnings: null,
        calcOpen: false, calcAmount: 100000, calcAppliesTo: 'funding', calcScope: '', calcCountry: '', calcResult: null,
        scheduleModal: false, scheduleTarget: null,
        testOpen: false, testTarget: null, testAmount: 100000, testResult: null,
        exemptionModal: false,
        feeIds: @json($fees->pluck('id')),
        highlighted: -1,
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('fees-search', () => this.$refs.searchInput?.focus());
                window.ShortcutManager.registerAction('fees-add', () => this.openAdd());
                window.ShortcutManager.registerAction('fees-filters', () => { this.filtersOpen = !this.filtersOpen; });
                window.ShortcutManager.registerAction('fees-calculator', () => { this.calcOpen = true; });
                window.ShortcutManager.registerAction('fees-refresh', () => window.location.reload());
                window.ShortcutManager.registerAction('fees-next', () => this.moveHighlight(1));
                window.ShortcutManager.registerAction('fees-prev', () => this.moveHighlight(-1));
                window.ShortcutManager.registerAction('fees-open', () => { if (this.highlighted >= 0) this.openDrawer(this.feeIds[this.highlighted]); });
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; this.formOpen = false; this.calcOpen = false; this.scheduleModal = false; this.testOpen = false; this.exemptionModal = false; });
            this.$watch('calcOpen', (v) => { if (v) this.runCalculator(); });
            ['calcAmount', 'calcAppliesTo', 'calcScope', 'calcCountry'].forEach((k) => this.$watch(k, () => { if (this.calcOpen) this.runCalculator(); }));
            this.$watch('testOpen', (v) => { if (v) this.runTest(); });
            this.$watch('testAmount', () => { if (this.testOpen) this.runTest(); });
        },
        activeFilterCount() {
            const p = new URLSearchParams(window.location.search);
            return ['active', 'applies_to', 'type', 'currency', 'country', 'payment_provider', 'customer_role', 'updated_since', 'automatic'].filter((k) => p.get(k)).length;
        },
        moveHighlight(delta) {
            if (this.feeIds.length === 0) return;
            this.highlighted = (this.highlighted + delta + this.feeIds.length) % this.feeIds.length;
        },
        toggleAll(checked) { this.selected = checked ? @json($fees->pluck('id')) : []; },
        runBulk(action) {
            if (this.selected.length === 0) return;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = '{{ route('admin.fees.bulk-action') }}';
            let html = '@csrf' + `<input type="hidden" name="action" value="${action}">`;
            this.selected.forEach((id) => { html += `<input type="hidden" name="ids[]" value="${id}">`; });
            f.innerHTML = html;
            document.body.appendChild(f); f.submit();
        },
        async openDrawer(id) {
            this.drawerOpen = true;
            this.drawer = null;
            try {
                const res = await fetch(`/admin/fees/${id}/row-detail`);
                this.drawer = await res.json();
            } catch (e) { this.drawerOpen = false; }
        },
        openAdd() {
            this.formMode = 'add';
            this.form = { applies_to: 'funding', type: 'percent', value: '', fixed_value: '', min_fee: 0, max_fee: '', min_amount: '', max_amount: '', currency: '', country: '', scope: '', payment_provider: '', china_wallet_type: '', customer_role: '', kyc_level: '', fee_payer: 'customer', taxable: false, is_active: true, notes: '' };
            this.tiers = [{ min_amount: 0, max_amount: '', percent: 0, fixed: 0 }];
            this.formErrors = null; this.formWarnings = null;
            this.formOpen = true;
        },
        async openEdit(id) {
            const res = await fetch(`/admin/fees/${id}/row-detail`);
            const data = await res.json();
            this.formMode = 'edit';
            this.form = { ...data.fee };
            this.tiers = data.tiers.length ? data.tiers : [{ min_amount: 0, max_amount: '', percent: 0, fixed: 0 }];
            this.formErrors = null; this.formWarnings = null;
            this.formOpen = true;
        },
        addTier() { this.tiers.push({ min_amount: '', max_amount: '', percent: 0, fixed: 0 }); },
        removeTier(i) { this.tiers.splice(i, 1); },
        formAction() {
            return this.formMode === 'add' ? '{{ route('admin.fees.store') }}' : `/admin/fees/${this.form.id}`;
        },
        async runCalculator() {
            const body = new URLSearchParams({ amount: this.calcAmount, applies_to: this.calcAppliesTo, scope: this.calcScope, country: this.calcCountry });
            const res = await fetch('{{ route('admin.fees.calculate') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body,
            });
            this.calcResult = res.ok ? await res.json() : null;
        },
        async runTest() {
            if (!this.testTarget) return;
            const body = new URLSearchParams({ amount: this.testAmount });
            const res = await fetch(`/admin/fees/${this.testTarget}/test`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body,
            });
            this.testResult = res.ok ? await res.json() : null;
        },
    };
}
</script>
@endpush
