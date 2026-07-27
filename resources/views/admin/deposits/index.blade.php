@extends('layouts.admin')
@section('page-title', 'Deposit Management')

@section('content')
@php
    $tabs = [
        'all' => ['All', $counts['all']],
        'pending' => ['Pending', $counts['pending']],
        'processing' => ['Processing', $counts['processing']],
        'under_review' => ['Under review', $counts['under_review']],
        'confirmed' => ['Confirmed', $counts['confirmed']],
        'rejected' => ['Rejected', $counts['rejected']],
        'failed' => ['Failed', $counts['failed']],
        'reversed' => ['Reversed', $counts['reversed']],
        'refunded' => ['Refunded', $counts['refunded']],
        'cancelled' => ['Cancelled', $counts['cancelled']],
    ];
    $currency = config('platform.base_currency');
    $summaryCards = [
        ['Total deposits', $summary['total'], null, 'wallet', 'slate', 'all'],
        ['Confirmed', $summary['confirmed'], null, 'check-circle', 'emerald', 'confirmed'],
        ['Pending', $summary['pending'], null, 'clock', 'amber', 'pending'],
        ['Under review', $summary['under_review'], null, 'eye', 'amber', 'under_review'],
        ['Failed', $summary['failed'], null, 'ban', 'rose', 'failed'],
        ['Reversed', $summary['reversed'], null, 'refresh', 'purple', 'reversed'],
        ['Refunded', $summary['refunded'], null, 'refresh', 'teal', 'refunded'],
        ['Deposit value today', money($summary['value_today'], $currency), $summary['value_today_change'], 'chart', 'sky', null],
        ['Pending deposit value', money($summary['pending_value'], $currency), null, 'clock', 'amber', null],
        ['Average deposit', money($summary['average_amount'], $currency), null, 'gauge', 'slate', null],
    ];
@endphp

<div x-data="depositsConsole()" x-init="init()" class="space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Deposit Management</h1>
            <p class="text-sm text-muted">Monitor customer deposits, review manual payments, investigate failures, and track payment-provider confirmations.</p>
            <p class="mt-1 text-xs text-faint">Last refreshed <span x-text="lastRefreshed"></span> · Live updates: off (manual refresh) · Showing <span x-text="'{{ $tab }}'"></span></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="qa-btn" @click="window.location.reload()"><x-icon name="refresh" class="h-3.5 w-3.5" /> Refresh</button>
            <a href="{{ route('admin.deposits.export', request()->query()) }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export</a>
            <a href="{{ route('admin.dashboard') }}#reconciliation" class="qa-btn"><x-icon name="chart" class="h-3.5 w-3.5" /> Reconciliation</a>
            <a href="{{ route('admin.dashboard') }}#providers" class="qa-btn"><x-icon name="signal" class="h-3.5 w-3.5" /> Provider status</a>
            <a href="{{ route('admin.methods.index') }}" class="qa-btn"><x-icon name="cog" class="h-3.5 w-3.5" /> Deposit settings</a>
        </div>
    </div>

    {{-- ============ SUMMARY CARDS ============ --}}
    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9.5rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-5 xl:grid-cols-10">
        @foreach ($summaryCards as [$label, $value, $change, $icon, $tint, $tabTarget])
            <a href="{{ $tabTarget ? route('admin.deposits.index', ['tab' => $tabTarget]) : route('admin.deposits.index') }}" class="card-solid rounded-2xl border border-app p-3.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-{{ $tint }}-500/15 text-{{ $tint }}-600"><x-icon :name="$icon" class="h-4 w-4" /></span>
                    <p class="truncate text-[11px] text-faint">{{ $label }}</p>
                </div>
                <p class="mt-2 text-base font-bold text-strong">{{ $value }}</p>
                @if ($change !== null)
                    <p class="text-[10px] {{ $change >= 0 ? 'text-emerald-600' : 'text-rose-500' }}">{{ $change >= 0 ? '+' : '' }}{{ $change }}% vs yesterday</p>
                @endif
            </a>
        @endforeach
    </div>

    {{-- ============ TABS ============ --}}
    <div class="no-scrollbar flex gap-1.5 overflow-x-auto rounded-2xl border border-app p-1.5" style="background: var(--surface-1);">
        @foreach ($tabs as $key => [$label, $count])
            <a href="{{ route('admin.deposits.index', ['tab' => $key]) }}" class="mu-tab {{ $tab === $key ? 'mu-tab-active' : '' }} whitespace-nowrap">
                {{ $label }} <span class="ml-1 text-[10px] opacity-70">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- ============ SEARCH + FILTERS + BULK BAR ============ --}}
    <div class="card-solid space-y-4 rounded-3xl border border-app p-5 shadow-sm">
        <form method="GET" id="filter-form" class="space-y-4">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                    <input x-ref="searchInput" name="q" value="{{ $q }}" placeholder="Search reference, provider reference, customer, email, phone, user ID…"
                           class="field !rounded-full pl-11 pr-4" @input.debounce.500ms="$el.form.requestSubmit()">
                </div>
                <select name="sort" class="field !w-auto" onchange="this.form.requestSubmit()">
                    <option value="newest" @selected(request('sort','newest')==='newest')>Newest first</option>
                    <option value="oldest" @selected(request('sort')==='oldest')>Oldest first</option>
                    <option value="amount_desc" @selected(request('sort')==='amount_desc')>Largest amount</option>
                    <option value="amount_asc" @selected(request('sort')==='amount_asc')>Smallest amount</option>
                </select>
                <button type="button" class="qa-btn" @click="filtersOpen = !filtersOpen"><x-icon name="filter" class="h-3.5 w-3.5" /> Filters <span x-show="activeFilterCount() > 0" x-text="'(' + activeFilterCount() + ')'"></span></button>
                <button type="button" class="qa-btn" @click="clearFilters()">Clear filters</button>
                <div class="relative" x-data="{ open: false }" @click.outside="open=false">
                    <button type="button" @click="open=!open" class="qa-btn"><x-icon name="cog" class="h-3.5 w-3.5" /> Columns</button>
                    <div x-show="open" x-cloak class="card-solid absolute right-0 z-30 mt-2 w-48 rounded-xl border border-app p-2 text-left shadow-lg">
                        <template x-for="c in colOptions" :key="c.key">
                            <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs text-body hover:surface"><input type="checkbox" :checked="cols.includes(c.key)" @change="toggleCol(c.key)" class="rounded"> <span x-text="c.label"></span></label>
                        </template>
                    </div>
                </div>
            </div>

            <div x-show="filtersOpen" x-collapse x-cloak class="grid gap-3 border-t border-app pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <select name="payment_method_id" class="field"><option value="">Any payment method</option>@foreach ($methods as $m)<option value="{{ $m->id }}" @selected(request('payment_method_id') == $m->id)>{{ $m->name }}</option>@endforeach</select>
                <select name="currency" class="field"><option value="">Any currency</option>@foreach (['XAF','NGN','GHS','USD','CNY'] as $cur)<option value="{{ $cur }}" @selected(request('currency')===$cur)>{{ $cur }}</option>@endforeach</select>
                <select name="country_id" class="field"><option value="">Any country</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(request('country_id') == $c->id)>{{ $c->name }}</option>@endforeach</select>
                <select name="automation" class="field"><option value="">Automated or manual</option><option value="automated" @selected(request('automation')==='automated')>Automated</option><option value="manual" @selected(request('automation')==='manual')>Manual</option></select>

                <div class="flex gap-2"><input type="number" name="amount_min" value="{{ request('amount_min') }}" placeholder="Min amount" class="field"><input type="number" name="amount_max" value="{{ request('amount_max') }}" placeholder="Max amount" class="field"></div>
                <div class="flex gap-2"><input type="date" name="from" value="{{ request('from') }}" class="field" title="From"><input type="date" name="to" value="{{ request('to') }}" class="field" title="To"></div>
                <select name="risk" class="field"><option value="">Any risk level</option><option value="flagged" @selected(request('risk')==='flagged')>Flagged</option><option value="clear" @selected(request('risk')==='clear')>Not flagged</option></select>
                <select name="reconciliation_status" class="field">
                    <option value="">Any reconciliation status</option>
                    @foreach (['matched'=>'Matched','unmatched'=>'Unmatched','amount_mismatch'=>'Amount mismatch','provider_pending'=>'Provider pending','manually_reconciled'=>'Manually reconciled','requires_investigation'=>'Requires investigation'] as $val=>$lbl)
                        <option value="{{ $val }}" @selected(request('reconciliation_status')===$val)>{{ $lbl }}</option>
                    @endforeach
                </select>

                <select name="assigned_to" class="field"><option value="">Any reviewer</option>@foreach ($reviewers as $r)<option value="{{ $r->id }}" @selected(request('assigned_to') == $r->id)>{{ $r->name }}</option>@endforeach</select>
                <div class="flex gap-2 sm:col-span-2">
                    <button class="btn btn-primary flex-1 text-sm">Apply filters</button>
                    <a href="{{ route('admin.deposits.index', ['tab' => $tab]) }}" class="btn btn-ghost flex-1 text-sm">Reset</a>
                </div>
            </div>
        </form>

        {{-- Bulk bar — no bulk confirm/credit/refund/reversal. Financial decisions are individual. --}}
        <div x-show="selected.length > 0" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
            <span class="text-xs font-semibold text-strong" x-text="selected.length + ' selected'"></span>
            <select class="field !w-auto text-xs" x-model="bulkReviewer"><option value="">Assign to…</option>@foreach ($reviewers as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select>
            <button type="button" class="qa-btn" @click="runBulk('assign')"><x-icon name="user" class="h-3.5 w-3.5" /> Assign</button>
            <button type="button" class="qa-btn qa-btn-warn" @click="runBulk('investigate')"><x-icon name="alert" class="h-3.5 w-3.5" /> Mark for investigation</button>
            <button type="button" class="qa-btn" @click="runBulk('requery')"><x-icon name="refresh" class="h-3.5 w-3.5" /> Requery</button>
            <button type="button" class="qa-btn" @click="runBulk('reconciliation_batch')"><x-icon name="doc" class="h-3.5 w-3.5" /> Add to reconciliation batch</button>
            <span class="text-[11px] text-faint">Confirmation, refunds, and reversals are always handled per deposit.</span>
        </div>
    </div>

    {{-- ============ TABLE ============ --}}
    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[1500px] text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded"></th>
                    <th class="px-3 py-3 font-medium">Reference</th>
                    <th class="px-3 py-3 font-medium">Customer</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('country')">Country</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('method')">Method</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('provider')">Provider</th>
                    <th class="px-3 py-3 font-medium">Amount</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('fees')">Fees</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('net')">Net</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('automation')">Automation</th>
                    <th class="px-3 py-3 font-medium">Status</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('risk')">Risk</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('reconciliation')">Reconciliation</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('created')">Created</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('updated')">Updated</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @php $svc = app(\App\Services\Deposit\DepositService::class); @endphp
                @forelse ($items as $d)
                    @php
                        $reconciliation = $d->reconciliation_status ?? $svc->computeReconciliationStatus($d);
                        $reconColor = match($reconciliation) { 'matched','manually_reconciled' => 'emerald', 'amount_mismatch','requires_investigation','unmatched' => 'rose', default => 'slate' };
                    @endphp
                    <tr class="hover:surface cursor-pointer" @click="openDrawer({{ $d->id }})">
                        <td class="px-3 py-3" @click.stop><input type="checkbox" value="{{ $d->id }}" x-model="selected" class="rounded"></td>
                        <td class="px-3 py-3 font-mono text-xs text-body">{{ $d->reference }}</td>
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ strtoupper(substr($d->user?->name ?? '?', 0, 2)) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-strong">{{ $d->user?->name ?? '—' }}</p>
                                    <p class="truncate text-xs text-faint">{{ $d->user?->email }} · #{{ $d->user_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-xs text-body" x-show="cols.includes('country')">
                            @if ($d->user?->country)<span class="inline-flex items-center gap-1.5"><x-flag :iso="$d->user->country->iso2" class="h-3 w-4.5" /> {{ $d->user->country->name }}</span>@else—@endif
                        </td>
                        <td class="px-3 py-3 text-xs text-body" x-show="cols.includes('method')">{{ $d->paymentMethod?->name ?? '—' }}</td>
                        <td class="px-3 py-3 text-xs text-body" x-show="cols.includes('provider')">{{ $d->provider_code ?? '—' }}</td>
                        <td class="px-3 py-3 font-semibold text-strong">{{ money($d->amount, $d->currency) }}</td>
                        <td class="px-3 py-3 text-xs text-faint" x-show="cols.includes('fees')">{{ money($d->fee, $d->currency) }}</td>
                        <td class="px-3 py-3 text-xs text-body" x-show="cols.includes('net')">{{ money($d->net_amount, $d->currency) }}</td>
                        <td class="px-3 py-3" x-show="cols.includes('automation')">
                            <span class="pill {{ $d->is_automated ? 'bg-sky-500/15 text-sky-600' : 'bg-slate-400/15 text-slate-600' }} text-[10px]">{{ $d->is_automated ? 'Auto' : 'Manual' }}</span>
                        </td>
                        <td class="px-3 py-3"><x-status-badge :status="$d->status" class="text-[10px]" /></td>
                        <td class="px-3 py-3" x-show="cols.includes('risk')">
                            @if ($d->risk_flagged)<span class="pill bg-rose-500/15 text-rose-600 text-[10px]">Flagged</span>@else<span class="text-xs text-faint">—</span>@endif
                        </td>
                        <td class="px-3 py-3" x-show="cols.includes('reconciliation')"><span class="pill bg-{{ $reconColor }}-500/15 text-{{ $reconColor }}-600 text-[10px]">{{ ucfirst(str_replace('_',' ',$reconciliation)) }}</span></td>
                        <td class="px-3 py-3 text-xs text-faint" x-show="cols.includes('created')">{{ $d->created_at->diffForHumans() }}</td>
                        <td class="px-3 py-3 text-xs text-faint" x-show="cols.includes('updated')">{{ $d->updated_at->diffForHumans() }}</td>
                        <td class="px-3 py-3 text-right" @click.stop>
                            <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="chevron-down" class="h-4 w-4" /></button>
                                <div x-show="open" x-cloak x-transition class="card-solid absolute right-0 z-20 mt-1 w-56 rounded-xl border border-app p-1.5 text-left shadow-lg">
                                    <button type="button" @click="openDrawer({{ $d->id }}); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="eye" class="h-4 w-4" /> View deposit</button>
                                    @if ($d->user)<a href="{{ route('admin.users.show', $d->user) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="user-circle" class="h-4 w-4" /> Open customer</a>@endif
                                    @if (! $d->status->isSettled())
                                        <form method="POST" action="{{ route('admin.deposits.confirm', $d) }}" onsubmit="return confirm('Confirm this deposit and credit the wallet?')">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="check" class="h-4 w-4" /> Confirm</button></form>
                                        <button type="button" @click="rejectTarget={{ $d->id }}; rejectModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="x" class="h-4 w-4" /> Reject</button>
                                    @endif
                                    @if ($d->status->canBeRefundedOrReversed())
                                        <button type="button" @click="refundTarget={{ $d->id }}; refundModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-teal-600 hover:surface"><x-icon name="refresh" class="h-4 w-4" /> Refund</button>
                                        <button type="button" @click="reverseTarget={{ $d->id }}; reverseModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-purple-600 hover:surface"><x-icon name="refresh" class="h-4 w-4" /> Reverse</button>
                                    @endif
                                    <button type="button" @click="noteTarget={{ $d->id }}; noteModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="doc" class="h-4 w-4" /> Add note</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="16" class="p-0">
                        @if ($tab === 'under_review' || $tab === 'pending')
                            <x-empty icon="check-circle" title="No deposits awaiting review" message="All deposits requiring manual attention have been processed." />
                        @else
                            <x-empty icon="wallet" title="No deposits found" message="No deposit records match the selected filters.">
                                <x-slot:action>
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('admin.deposits.index') }}" class="qa-btn">Clear filters</a>
                                        <button type="button" class="qa-btn" @click="window.location.reload()">Refresh deposits</button>
                                        <a href="{{ route('admin.deposits.index', ['tab' => 'all']) }}" class="qa-btn">View all deposits</a>
                                    </div>
                                </x-slot:action>
                            </x-empty>
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $items->links() }}</div>

    {{-- ============ BULK / REJECT / REFUND / REVERSE / NOTE FORMS ============ --}}
    <form :action="'{{ route('admin.deposits.bulk-action') }}'" method="POST" x-ref="bulkForm" class="hidden">
        @csrf
        <input type="hidden" name="action" x-bind:value="bulkActionType">
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <input type="hidden" name="reviewer_id" x-bind:value="bulkReviewer">
    </form>

    <form method="POST" :action="`/admin/deposits/${rejectTarget}/reject`" x-show="rejectModal" x-cloak @click.self="rejectModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-rose-600">Reject deposit</h3>
            <textarea name="reason" required rows="3" class="field mt-3" placeholder="Reason shown to the customer"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="rejectModal=false">Cancel</button><button class="btn btn-danger flex-1">Reject</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/deposits/${refundTarget}/refund`" x-show="refundModal" x-cloak @click.self="refundModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-teal-600">Refund deposit</h3>
            <p class="mt-1 text-xs text-muted">Money is returned through the original provider; the wallet credit is taken back.</p>
            <input name="provider_refund_reference" class="field mt-3" placeholder="Provider refund reference (optional)">
            <textarea name="reason" required rows="2" class="field mt-2" placeholder="Refund reason"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="refundModal=false">Cancel</button><button class="btn btn-danger flex-1">Confirm refund</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/deposits/${reverseTarget}/reverse`" x-show="reverseModal" x-cloak @click.self="reverseModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-purple-600">Reverse deposit</h3>
            <p class="mt-1 text-xs text-muted">Use this when the deposit itself was invalid (chargeback, processing error) — the wallet credit is undone.</p>
            <textarea name="reason" required rows="3" class="field mt-3" placeholder="Reversal reason"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="reverseModal=false">Cancel</button><button class="btn btn-danger flex-1">Confirm reversal</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/deposits/${noteTarget}/notes`" x-show="noteModal" x-cloak @click.self="noteModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-strong">Add internal note</h3>
            <textarea name="note" required rows="3" class="field mt-3" placeholder="Private, never shown to the customer"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="noteModal=false">Cancel</button><button class="btn btn-primary flex-1">Save note</button></div>
        </div>
    </form>

    @include('admin.deposits.partials.drawer')
</div>
@endsection

@push('scripts')
<script>
function depositsConsole() {
    return {
        filtersOpen: false,
        selected: [],
        lastRefreshed: 'just now',
        drawerOpen: false, drawer: null,
        rejectModal: false, rejectTarget: null,
        refundModal: false, refundTarget: null,
        reverseModal: false, reverseTarget: null,
        noteModal: false, noteTarget: null,
        requestInfoModal: false, requestInfoTarget: null,
        escalateModal: false, escalateTarget: null,
        bulkActionType: '', bulkReviewer: '',
        cols: JSON.parse(localStorage.getItem('admin-deposits-cols') || 'null') || ['country', 'method', 'provider', 'fees', 'net', 'automation', 'risk', 'reconciliation', 'created'],
        colOptions: [
            { key: 'country', label: 'Country' }, { key: 'method', label: 'Method' }, { key: 'provider', label: 'Provider' },
            { key: 'fees', label: 'Fees' }, { key: 'net', label: 'Net amount' }, { key: 'automation', label: 'Automation' },
            { key: 'risk', label: 'Risk' }, { key: 'reconciliation', label: 'Reconciliation' }, { key: 'created', label: 'Created' }, { key: 'updated', label: 'Updated' },
        ],
        init() {
            this.$watch('cols', (v) => localStorage.setItem('admin-deposits-cols', JSON.stringify(v)), { deep: true });
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('deposits-search', () => this.$refs.searchInput?.focus());
                window.ShortcutManager.registerAction('deposits-filters', () => { this.filtersOpen = !this.filtersOpen; });
                window.ShortcutManager.registerAction('deposits-refresh', () => window.location.reload());
                window.ShortcutManager.registerAction('deposits-note', () => { if (this.drawer) { this.noteTarget = this.drawer.deposit.id; this.noteModal = true; } });
                window.ShortcutManager.registerAction('deposits-investigate', () => { if (this.drawer) { this.investigate(this.drawer.deposit.id); } });
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; this.rejectModal = false; this.refundModal = false; this.reverseModal = false; this.noteModal = false; this.requestInfoModal = false; this.escalateModal = false; });
        },
        submitTo(id, path) {
            const f = document.createElement('form');
            f.method = 'POST'; f.action = `/admin/deposits/${id}/${path}`;
            f.innerHTML = '@csrf';
            document.body.appendChild(f); f.submit();
        },
        approve(id) { if (confirm('Confirm this deposit and credit the wallet?')) this.submitTo(id, 'confirm'); },
        placeUnderReview(id) { this.submitTo(id, 'under-review'); },
        requery(id) { this.submitTo(id, 'requery'); },
        toggleCol(key) { this.cols = this.cols.includes(key) ? this.cols.filter((c) => c !== key) : [...this.cols, key]; },
        activeFilterCount() {
            const p = new URLSearchParams(window.location.search);
            return ['payment_method_id', 'currency', 'country_id', 'automation', 'amount_min', 'amount_max', 'from', 'to', 'risk', 'reconciliation_status', 'assigned_to'].filter((k) => p.get(k)).length;
        },
        clearFilters() { window.location = '{{ route('admin.deposits.index', ['tab' => $tab]) }}'; },
        toggleAll(checked) { this.selected = checked ? @json($items->pluck('id')) : []; },
        runBulk(action) {
            if (this.selected.length === 0) return;
            this.bulkActionType = action;
            this.$nextTick(() => this.$refs.bulkForm.submit());
        },
        async openDrawer(id) {
            this.drawerOpen = true;
            this.drawer = null;
            try {
                const res = await fetch(`/admin/deposits/${id}/row-detail`);
                this.drawer = await res.json();
            } catch (e) { this.drawerOpen = false; }
        },
        investigate(id) {
            const f = document.createElement('form');
            f.method = 'POST'; f.action = `/admin/deposits/${id}/investigate`;
            f.innerHTML = '@csrf';
            document.body.appendChild(f); f.submit();
        },
    };
}
</script>
@endpush
