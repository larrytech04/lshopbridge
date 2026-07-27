@extends('layouts.admin')
@section('page-title', 'KYC review')

@section('content')
@php
    $tabs = [
        'open' => ['All open', $counts['all_open']],
        'pending' => ['Pending', $counts['pending']],
        'in_review' => ['In review', $counts['in_review']],
        'more_info_requested' => ['More info requested', $counts['more_info_requested']],
        'returned_for_correction' => ['Returned for correction', $counts['returned_for_correction']],
        'escalated' => ['Escalated', $counts['escalated']],
        'on_hold' => ['On hold', $counts['on_hold']],
        'unassigned' => ['Unassigned', $counts['unassigned']],
        'mine' => ['My cases', null],
        'approved' => ['Approved', $counts['approved']],
        'approved_limited' => ['Approved (limited)', $counts['approved_limited']],
        'rejected' => ['Rejected', $counts['rejected']],
        'all' => ['All cases', $counts['all']],
    ];
    $summary = [
        ['Pending', $counts['pending'], 'clock', 'slate'],
        ['In review', $counts['in_review'], 'eye', 'sky'],
        ['More info requested', $counts['more_info_requested'], 'alert', 'amber'],
        ['Returned for correction', $counts['returned_for_correction'], 'refresh', 'amber'],
        ['Escalated', $counts['escalated'], 'flag', 'purple'],
        ['On hold', $counts['on_hold'], 'lock', 'slate'],
        ['Unassigned', $counts['unassigned'], 'user', 'rose'],
        ['SLA breaches', $counts['sla_breaches'], 'alert', 'rose'],
        ['Approved today', $counts['approved_today'], 'check-circle', 'emerald'],
        ['Rejected today', $counts['rejected_today'], 'ban', 'rose'],
        ['Docs expiring (30d)', $counts['expiring_soon'], 'doc', 'amber'],
        ['Avg review time', $counts['avg_review_hours'] !== null ? $counts['avg_review_hours'].'h' : '—', 'gauge', 'sky'],
    ];
@endphp

<div x-data="kycQueue()" x-init="init()" class="space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">KYC review</h1>
            <p class="text-sm text-muted">Identity verification, compliance and risk review workspace.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.kyc.index', ['view' => 'queue']) }}" class="qa-btn {{ $view === 'queue' ? 'qa-btn-good' : '' }}"><x-icon name="list" class="h-3.5 w-3.5" /> Queue</a>
            <a href="{{ route('admin.kyc.index', ['view' => 'analytics']) }}" class="qa-btn {{ $view === 'analytics' ? 'qa-btn-good' : '' }}"><x-icon name="chart" class="h-3.5 w-3.5" /> Analytics &amp; reporting</a>
        </div>
    </div>

    {{-- ============ SUMMARY CARDS ============ --}}
    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-4 xl:grid-cols-6">
        @foreach ($summary as [$label, $value, $icon, $tint])
            <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-{{ $tint }}-500/15 text-{{ $tint }}-600"><x-icon :name="$icon" class="h-4 w-4" /></span>
                    <p class="truncate text-[11px] text-faint">{{ $label }}</p>
                </div>
                <p class="mt-2 text-lg font-bold text-strong">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    @if ($view === 'analytics')
        @include('admin.kyc.partials.analytics')
    @else

    {{-- ============ QUEUE TABS ============ --}}
    <div class="no-scrollbar flex gap-1.5 overflow-x-auto rounded-2xl border border-app p-1.5" style="background: var(--surface-1);">
        @foreach ($tabs as $key => [$label, $count])
            <a href="{{ route('admin.kyc.index', ['status' => $key]) }}" class="mu-tab {{ $status === $key ? 'mu-tab-active' : '' }} whitespace-nowrap">
                {{ $label }} @if ($count !== null)<span class="ml-1 text-[10px] opacity-70">{{ $count }}</span>@endif
            </a>
        @endforeach
    </div>

    {{-- ============ SEARCH + FILTERS + BULK BAR ============ --}}
    <div class="card-solid space-y-4 rounded-3xl border border-app p-5 shadow-sm">
        <form method="GET" id="filter-form" class="space-y-4">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                    <input name="q" value="{{ $q }}" placeholder="Search applicant name, email, phone, document number, country…"
                           class="field !rounded-full pl-11 pr-4" @input.debounce.500ms="$el.form.requestSubmit()">
                </div>
                <button type="button" class="qa-btn" @click="filtersOpen = !filtersOpen"><x-icon name="filter" class="h-3.5 w-3.5" /> Filters</button>
                <a href="{{ route('admin.kyc.export', request()->query()) }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export CSV</a>
            </div>

            <div x-show="filtersOpen" x-collapse x-cloak class="grid gap-3 border-t border-app pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <select name="country_id" class="field"><option value="">Any country</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(request('country_id') == $c->id)>{{ $c->name }}</option>@endforeach</select>
                <select name="document_type" class="field"><option value="">Any document type</option>@foreach (['national_id' => 'National ID', 'passport' => 'Passport', 'drivers_license' => "Driver's license"] as $val => $lbl)<option value="{{ $val }}" @selected(request('document_type')===$val)>{{ $lbl }}</option>@endforeach</select>
                <select name="priority" class="field"><option value="">Any priority</option>@foreach (\App\Enums\KycPriority::cases() as $p)<option value="{{ $p->value }}" @selected(request('priority')===$p->value)>{{ $p->label() }}</option>@endforeach</select>
                <select name="target_level" class="field"><option value="">Any target level</option>@for ($i=1;$i<=3;$i++)<option value="{{ $i }}" @selected(request('target_level') == $i)>Level {{ $i }}</option>@endfor</select>

                <label class="field flex items-center gap-2 !py-2.5"><input type="checkbox" name="is_pep" value="1" @checked(request('is_pep')) class="rounded"> Self-declared PEP</label>
                <label class="field flex items-center gap-2 !py-2.5"><input type="checkbox" name="unassigned_only" value="1" @checked(request('unassigned_only')) class="rounded"> Unassigned only</label>
                <label class="field flex items-center gap-2 !py-2.5"><input type="checkbox" name="has_risk_flag" value="1" @checked(request('has_risk_flag')) class="rounded"> Has open risk flag</label>
                <select name="sort" class="field">
                    <option value="oldest" @selected(request('sort','oldest')==='oldest')>Oldest first</option>
                    <option value="newest" @selected(request('sort')==='newest')>Newest first</option>
                    <option value="priority" @selected(request('sort')==='priority')>Priority first</option>
                </select>

                <div class="flex gap-2 sm:col-span-2"><input type="date" name="from" value="{{ request('from') }}" class="field" title="Submitted from"><input type="date" name="to" value="{{ request('to') }}" class="field" title="Submitted to"></div>
                <div class="flex gap-2 sm:col-span-2">
                    <button class="btn btn-primary flex-1 text-sm">Apply filters</button>
                    <a href="{{ route('admin.kyc.index', ['status' => $status]) }}" class="btn btn-ghost flex-1 text-sm">Reset</a>
                </div>
            </div>
        </form>

        {{-- Bulk bar — assignment/priority/export only. Final identity decisions are never bulk. --}}
        <div x-show="selected.length > 0" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
            <span class="text-xs font-semibold text-strong" x-text="selected.length + ' selected'"></span>
            <select class="field !w-auto text-xs" x-model="bulkAssignee">
                <option value="">Assign to…</option>
                @foreach (\App\Models\User::whereIn('role', ['admin', 'super_admin'])->orderBy('name')->get() as $r)
                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                @endforeach
            </select>
            <button type="button" class="qa-btn" @click="bulkAssign()"><x-icon name="user" class="h-3.5 w-3.5" /> Assign</button>
            <select class="field !w-auto text-xs" x-model="bulkPriorityValue">
                <option value="">Set priority…</option>
                @foreach (\App\Enums\KycPriority::cases() as $p)<option value="{{ $p->value }}">{{ $p->label() }}</option>@endforeach
            </select>
            <button type="button" class="qa-btn" @click="bulkPriority()"><x-icon name="flag" class="h-3.5 w-3.5" /> Set priority</button>
            <span class="text-[11px] text-faint">Bulk approve/reject is disabled by design — final identity decisions are always reviewed individually.</span>
        </div>
    </div>

    {{-- ============ REVIEW TABLE ============ --}}
    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[1200px] text-left text-sm">
            <thead class="border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded"></th>
                    <th class="px-3 py-3 font-medium">Case / applicant</th>
                    <th class="px-3 py-3 font-medium">Country</th>
                    <th class="px-3 py-3 font-medium">Document</th>
                    <th class="px-3 py-3 font-medium">Priority</th>
                    <th class="px-3 py-3 font-medium">Waiting / SLA</th>
                    <th class="px-3 py-3 font-medium">Risk</th>
                    <th class="px-3 py-3 font-medium">Assigned to</th>
                    <th class="px-3 py-3 font-medium">Status</th>
                    <th class="px-3 py-3 font-medium">Submitted</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @php $svc = app(\App\Services\Admin\KycReviewService::class); @endphp
                @forelse ($items as $kyc)
                    @php
                        $priority = $svc->effectivePriority($kyc);
                        $breached = $svc->slaBreached($kyc);
                        $waiting = $svc->waitingHours($kyc);
                        $openFlags = $kyc->riskFlags()->where('status', 'open')->count();
                    @endphp
                    <tr class="hover:surface cursor-pointer" onclick="window.location='{{ route('admin.kyc.show', $kyc) }}'">
                        <td class="px-3 py-3" onclick="event.stopPropagation()"><input type="checkbox" value="{{ $kyc->id }}" x-model="selected" class="rounded"></td>
                        <td class="px-3 py-3">
                            <p class="font-medium text-strong">{{ $kyc->user->name ?? $kyc->full_name }}</p>
                            <p class="truncate text-xs text-faint">{{ $kyc->user->email ?? '—' }} · #{{ $kyc->id }}</p>
                        </td>
                        <td class="px-3 py-3 text-xs text-body">
                            @if ($kyc->country)<span class="inline-flex items-center gap-1.5"><x-flag :iso="$kyc->country->iso2" class="h-3 w-4.5" /> {{ $kyc->country->name }}</span>@else—@endif
                        </td>
                        <td class="px-3 py-3 text-xs text-body">{{ ucfirst(str_replace('_', ' ', $kyc->document_type)) }}</td>
                        <td class="px-3 py-3"><x-status-badge :status="$priority" class="text-[10px]" /></td>
                        <td class="px-3 py-3 text-xs {{ $breached ? 'font-semibold text-rose-500' : 'text-faint' }}">
                            {{ $waiting }}h @if ($breached)<span class="pill bg-rose-500/15 text-rose-600 text-[9px] ml-1">SLA breach</span>@endif
                        </td>
                        <td class="px-3 py-3">
                            @if ($openFlags > 0)<span class="pill bg-rose-500/15 text-rose-600 text-[10px]">{{ $openFlags }} flag{{ $openFlags > 1 ? 's' : '' }}</span>
                            @elseif ($kyc->is_pep)<span class="pill bg-amber-500/15 text-amber-600 text-[10px]">PEP declared</span>
                            @else<span class="pill bg-slate-400/15 text-slate-500 text-[10px]">None</span>@endif
                        </td>
                        <td class="px-3 py-3 text-xs text-body">{{ $kyc->assignedTo->name ?? '—' }}</td>
                        <td class="px-3 py-3"><x-status-badge :status="$kyc->status" class="text-[10px]" /></td>
                        <td class="px-3 py-3 text-xs text-faint">{{ $kyc->created_at->diffForHumans() }}</td>
                        <td class="px-3 py-3 text-right" onclick="event.stopPropagation()">
                            <a href="{{ route('admin.kyc.show', $kyc) }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Review →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="p-0">
                        <x-empty icon="shield" title="Nothing to review right now" message="New KYC submissions will appear here as applicants complete verification. Summary cards and analytics above still reflect the full case history." />
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $items->links() }}</div>

    <form :action="bulkTarget" method="POST" x-ref="bulkForm" class="hidden">
        @csrf
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <input type="hidden" name="assignee_id" x-bind:value="bulkAssignee">
        <input type="hidden" name="priority" x-bind:value="bulkPriorityValue">
    </form>

    @endif
</div>
@endsection

@push('scripts')
<script>
function kycQueue() {
    return {
        filtersOpen: false,
        selected: [],
        bulkAssignee: '',
        bulkPriorityValue: '',
        bulkTarget: '{{ route('admin.kyc.bulk-assign') }}',
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('kyc-search', () => document.querySelector('#filter-form input[name="q"]')?.focus());
                window.ShortcutManager.registerAction('kyc-filters', () => { this.filtersOpen = !this.filtersOpen; });
                window.ShortcutManager.registerAction('kyc-export', () => { window.location = '{{ route('admin.kyc.export', request()->query()) }}'; });
            }
        },
        toggleAll(checked) {
            this.selected = checked ? @json($items->pluck('id')) : [];
        },
        bulkAssign() {
            if (this.selected.length === 0 || !this.bulkAssignee) return;
            this.bulkTarget = '{{ route('admin.kyc.bulk-assign') }}';
            this.$nextTick(() => this.$refs.bulkForm.submit());
        },
        bulkPriority() {
            if (this.selected.length === 0 || !this.bulkPriorityValue) return;
            this.bulkTarget = '{{ route('admin.kyc.bulk-priority') }}';
            this.$nextTick(() => this.$refs.bulkForm.submit());
        },
    };
}
</script>
@endpush
