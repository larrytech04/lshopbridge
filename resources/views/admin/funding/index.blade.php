@extends('layouts.admin')
@section('page-title', 'China Wallet Funding')

@section('content')
@php
    $tabs = [
        'all' => ['All', $counts['all']],
        'pending' => ['Pending', $counts['payment_pending'] + $counts['payment_successful']],
        'under_review' => ['Under review', $counts['manual_review']],
        'processing' => ['Processing', $counts['funding_processing']],
        'completed' => ['Completed', $counts['funding_successful']],
        'funding_failed' => ['Failed', $counts['funding_failed']],
        'cancelled' => ['Cancelled', $counts['cancelled']],
        'refunded' => ['Refunded', $counts['refunded']],
    ];
    $summaryCards = [
        ['Total requests', $summary['total'], null, 'wallet', 'slate', 'all'],
        ['Requested today', $summary['today'], null, 'clock', 'sky', null],
        ['Processing', $summary['processing'], null, 'refresh', 'sky', 'processing'],
        ['Under review', $summary['under_review'], null, 'eye', 'amber', 'under_review'],
        ['Completed', $summary['completed'], null, 'check-circle', 'emerald', 'completed'],
        ['Failed', $summary['failed'], null, 'ban', 'rose', 'funding_failed'],
        ['Cancelled', $summary['cancelled'], null, 'x', 'slate', 'cancelled'],
        ['Refunded', $summary['refunded'], null, 'refresh', 'teal', 'refunded'],
        ['CNY delivered today', number_format($summary['delivered_today'], 2).' CNY', $summary['delivered_today_change'], 'chart', 'emerald', null],
        ['Pending CNY value', number_format($summary['pending_value'], 2).' CNY', null, 'clock', 'amber', null],
    ];
@endphp

<div x-data="fundingConsole()" x-init="init()" class="space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">China Wallet Funding</h1>
            <p class="text-sm text-muted">Monitor and manage customer funding requests sent to approved China wallet accounts.</p>
            <p class="mt-1 text-xs text-faint">Last refreshed <span x-text="lastRefreshed"></span> · Live updates: off (manual refresh) · Showing <span x-text="'{{ $tab }}'"></span></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="qa-btn" @click="window.location.reload()"><x-icon name="refresh" class="h-3.5 w-3.5" /> Refresh</button>
            <a href="{{ route('admin.funding.export', request()->query()) }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export</a>
            <a href="{{ route('admin.settings.index') }}" class="qa-btn"><x-icon name="cog" class="h-3.5 w-3.5" /> Funding settings</a>
            <a href="{{ route('admin.dashboard') }}#providers" class="qa-btn"><x-icon name="signal" class="h-3.5 w-3.5" /> Provider status</a>
            <a href="{{ route('admin.dashboard') }}#reconciliation" class="qa-btn"><x-icon name="chart" class="h-3.5 w-3.5" /> Reconciliation</a>
        </div>
    </div>

    {{-- ============ SUMMARY CARDS ============ --}}
    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9.5rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-5 xl:grid-cols-10">
        @foreach ($summaryCards as [$label, $value, $change, $icon, $tint, $tabTarget])
            <a href="{{ $tabTarget ? route('admin.funding.index', ['tab' => $tabTarget]) : route('admin.funding.index') }}" class="card-solid rounded-2xl border border-app p-3.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
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
            <a href="{{ route('admin.funding.index', ['tab' => $key]) }}" class="mu-tab {{ $tab === $key ? 'mu-tab-active' : '' }} whitespace-nowrap">
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
                    <input x-ref="searchInput" name="q" value="{{ $q }}" placeholder="Search reference, recipient, customer, email, phone, provider/deposit reference…"
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
                <select name="app_type" class="field"><option value="">Any China wallet type</option>@foreach (\App\Enums\AppType::cases() as $t)<option value="{{ $t->value }}" @selected(request('app_type')===$t->value)>{{ $t->label() }}</option>@endforeach</select>
                <select name="funding_source" class="field"><option value="">Any funding method</option><option value="wallet" @selected(request('funding_source')==='wallet')>Wallet balance</option><option value="direct_payment" @selected(request('funding_source')==='direct_payment')>Direct payment</option></select>
                <select name="country_id" class="field"><option value="">Any country</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(request('country_id') == $c->id)>{{ $c->name }}</option>@endforeach</select>
                <select name="currency" class="field"><option value="">Any currency</option><option value="CNY" @selected(request('currency')==='CNY')>CNY</option></select>

                <div class="flex gap-2"><input type="number" name="amount_min" value="{{ request('amount_min') }}" placeholder="Min amount" class="field"><input type="number" name="amount_max" value="{{ request('amount_max') }}" placeholder="Max amount" class="field"></div>
                <div class="flex gap-2"><input type="date" name="from" value="{{ request('from') }}" class="field" title="From"><input type="date" name="to" value="{{ request('to') }}" class="field" title="To"></div>
                <select name="automation" class="field"><option value="">Automated or manual</option><option value="automated" @selected(request('automation')==='automated')>Automated</option><option value="manual" @selected(request('automation')==='manual')>Manual</option></select>
                <select name="risk" class="field"><option value="">Any risk level</option><option value="flagged" @selected(request('risk')==='flagged')>Flagged</option><option value="clear" @selected(request('risk')==='clear')>Not flagged</option></select>

                <select name="reconciliation_status" class="field">
                    <option value="">Any reconciliation status</option>
                    @foreach (['matched'=>'Matched','unmatched'=>'Unmatched','amount_mismatch'=>'Amount mismatch','recipient_mismatch'=>'Recipient mismatch','provider_pending'=>'Provider pending','manually_reconciled'=>'Manually reconciled','requires_investigation'=>'Requires investigation'] as $val=>$lbl)
                        <option value="{{ $val }}" @selected(request('reconciliation_status')===$val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <select name="assigned_to" class="field"><option value="">Any reviewer</option>@foreach ($reviewers as $r)<option value="{{ $r->id }}" @selected(request('assigned_to') == $r->id)>{{ $r->name }}</option>@endforeach</select>
                <div class="flex gap-2 sm:col-span-2">
                    <button class="btn btn-primary flex-1 text-sm">Apply filters</button>
                    <a href="{{ route('admin.funding.index', ['tab' => $tab]) }}" class="btn btn-ghost flex-1 text-sm">Reset</a>
                </div>
            </div>
        </form>

        {{-- Bulk bar — no bulk completion/deduction/refund/cancel/reversal. Each request is handled individually. --}}
        <div x-show="selected.length > 0" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
            <span class="text-xs font-semibold text-strong" x-text="selected.length + ' selected'"></span>
            <select class="field !w-auto text-xs" x-model="bulkReviewer"><option value="">Assign to…</option>@foreach ($reviewers as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select>
            <button type="button" class="qa-btn" @click="runBulk('assign')"><x-icon name="user" class="h-3.5 w-3.5" /> Assign</button>
            <button type="button" class="qa-btn qa-btn-warn" @click="runBulk('investigate')"><x-icon name="alert" class="h-3.5 w-3.5" /> Mark for investigation</button>
            <button type="button" class="qa-btn" @click="runBulk('requery')"><x-icon name="refresh" class="h-3.5 w-3.5" /> Requery</button>
            <button type="button" class="qa-btn" @click="runBulk('reconciliation_batch')"><x-icon name="doc" class="h-3.5 w-3.5" /> Add to reconciliation batch</button>
        </div>
    </div>

    {{-- ============ TABLE ============ --}}
    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[1600px] text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded"></th>
                    <th class="px-3 py-3 font-medium">Reference</th>
                    <th class="px-3 py-3 font-medium">Customer</th>
                    <th class="px-3 py-3 font-medium">Recipient</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('wallet_app')">Wallet app</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('source')">Source amount</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('rate')">Rate</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('fees')">Fees</th>
                    <th class="px-3 py-3 font-medium">CNY delivered</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('type')">Processing type</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('provider')">Provider</th>
                    <th class="px-3 py-3 font-medium">Status</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('reconciliation')">Reconciliation</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('created')">Created</th>
                    <th class="px-3 py-3 font-medium" x-show="cols.includes('completed')">Completed</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @php $svc = app(\App\Services\Funding\FundingService::class); @endphp
                @forelse ($items as $f)
                    @php
                        $reconciliation = $f->reconciliation_status ?? $svc->computeReconciliationStatus($f);
                        $reconColor = match($reconciliation) { 'matched','manually_reconciled' => 'emerald', 'amount_mismatch','recipient_mismatch','requires_investigation','unmatched' => 'rose', default => 'slate' };
                        $recipientMasked = $f->recipient_account ? (strlen($f->recipient_account) <= 4 ? str_repeat('*', strlen($f->recipient_account)) : substr($f->recipient_account,0,3).str_repeat('*', max(2, strlen($f->recipient_account)-5)).substr($f->recipient_account,-2)) : '—';
                        $automationType = $f->status->value === 'funding_successful' ? ($f->processed_by ? 'Manual' : 'Automated') : 'N/A';
                    @endphp
                    <tr class="hover:surface cursor-pointer {{ $f->risk_flagged ? 'bg-amber-500/[0.03]' : '' }}" @click="openDrawer({{ $f->id }})">
                        <td class="px-3 py-3" @click.stop><input type="checkbox" value="{{ $f->id }}" x-model="selected" class="rounded"></td>
                        <td class="px-3 py-3 font-mono text-xs text-body">{{ $f->reference }}</td>
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ strtoupper(substr($f->user?->name ?? '?', 0, 2)) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-strong">{{ $f->user?->name ?? '—' }}</p>
                                    <p class="truncate text-xs text-faint">{{ $f->user?->email }} · #{{ $f->user_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            <p class="text-body">{{ $f->recipient_name }}</p>
                            <p class="text-xs font-mono text-faint">{{ $recipientMasked }}</p>
                        </td>
                        <td class="px-3 py-3 text-xs text-body" x-show="cols.includes('wallet_app')">{{ $f->app_type->label() }}</td>
                        <td class="px-3 py-3 text-xs text-body" x-show="cols.includes('source')">{{ money($f->source_amount, $f->source_currency) }}</td>
                        <td class="px-3 py-3 text-xs text-faint" x-show="cols.includes('rate')">{{ number_format((float) $f->exchange_rate, 6) }}</td>
                        <td class="px-3 py-3 text-xs text-faint" x-show="cols.includes('fees')">{{ money($f->fee, $f->source_currency) }}</td>
                        <td class="px-3 py-3 font-semibold text-strong">{{ number_format((float) $f->target_amount, 2) }} {{ $f->target_currency }}</td>
                        <td class="px-3 py-3" x-show="cols.includes('type')"><span class="pill bg-slate-400/15 text-slate-600 text-[10px]">{{ $automationType }}</span></td>
                        <td class="px-3 py-3 text-xs text-body" x-show="cols.includes('provider')">{{ $f->provider_code ?? '—' }}</td>
                        <td class="px-3 py-3"><x-status-badge :status="$f->status" class="text-[10px]" /></td>
                        <td class="px-3 py-3" x-show="cols.includes('reconciliation')"><span class="pill bg-{{ $reconColor }}-500/15 text-{{ $reconColor }}-600 text-[10px]">{{ ucfirst(str_replace('_',' ',$reconciliation)) }}</span></td>
                        <td class="px-3 py-3 text-xs text-faint" x-show="cols.includes('created')">{{ $f->created_at->diffForHumans() }}</td>
                        <td class="px-3 py-3 text-xs text-faint" x-show="cols.includes('completed')">{{ $f->processed_at?->diffForHumans() ?? '—' }}</td>
                        <td class="px-3 py-3 text-right" @click.stop>
                            <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="chevron-down" class="h-4 w-4" /></button>
                                <div x-show="open" x-cloak x-transition class="card-solid absolute right-0 z-20 mt-1 w-56 rounded-xl border border-app p-1.5 text-left shadow-lg">
                                    <button type="button" @click="openDrawer({{ $f->id }}); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="eye" class="h-4 w-4" /> View funding</button>
                                    @if ($f->user)<a href="{{ route('admin.users.show', $f->user) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="user-circle" class="h-4 w-4" /> Open customer</a>@endif
                                    @if (! $f->status->isTerminal())
                                        <button type="button" @click="completeTarget={{ $f->id }}; completeModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="check" class="h-4 w-4" /> Mark completed</button>
                                        <form method="POST" action="{{ route('admin.funding.retry', $f) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="refresh" class="h-4 w-4" /> Retry</button></form>
                                        <button type="button" @click="failTarget={{ $f->id }}; failModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="x" class="h-4 w-4" /> Mark failed</button>
                                        <button type="button" @click="cancelTarget={{ $f->id }}; cancelModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="ban" class="h-4 w-4" /> Cancel request</button>
                                    @endif
                                    @if ($f->status->canBeRefunded())
                                        <button type="button" @click="refundTarget={{ $f->id }}; refundModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-teal-600 hover:surface"><x-icon name="refresh" class="h-4 w-4" /> Refund</button>
                                    @endif
                                    <button type="button" @click="noteTarget={{ $f->id }}; noteModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="doc" class="h-4 w-4" /> Add note</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="16" class="p-0">
                        @if ($tab === 'pending' || $tab === 'under_review')
                            <x-empty icon="check-circle" title="No funding requests awaiting processing" message="All submitted funding requests have been processed." />
                        @else
                            <x-empty icon="wallet" title="No funding requests found" message="No China wallet funding requests match the selected filters.">
                                <x-slot:action>
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('admin.funding.index') }}" class="qa-btn">Clear filters</a>
                                        <button type="button" class="qa-btn" @click="window.location.reload()">Refresh requests</button>
                                        <a href="{{ route('admin.funding.index', ['tab' => 'all']) }}" class="qa-btn">View all funding</a>
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

    {{-- ============ BULK / FAIL / CANCEL / REFUND / NOTE FORMS ============ --}}
    <form :action="'{{ route('admin.funding.bulk-action') }}'" method="POST" x-ref="bulkForm" class="hidden">
        @csrf
        <input type="hidden" name="action" x-bind:value="bulkActionType">
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <input type="hidden" name="reviewer_id" x-bind:value="bulkReviewer">
    </form>

    <form method="POST" enctype="multipart/form-data" :action="`/admin/funding/${completeTarget}/complete`" x-show="completeModal" x-cloak @click.self="completeModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-emerald-600">Mark funding completed</h3>
            <p class="mt-1 text-xs text-muted">Manual completion requires evidence — a screenshot alone doesn't automatically prove delivery when a provider verification exists, so attach what you have and confirm you've checked it.</p>
            <label class="label mt-3">Proof of delivery (receipt, provider confirmation, screenshot)</label>
            <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="field">
            <textarea name="note" rows="2" class="field mt-2" placeholder="Reviewer note — what you verified"></textarea>
            <label class="mt-2 flex items-center gap-2 text-xs text-body"><input type="checkbox" required class="rounded"> I have verified the recipient, amount, and delivery evidence for this request.</label>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="completeModal=false">Cancel</button><button class="btn btn-primary flex-1">Confirm completion</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/funding/${failTarget}/mark-failed`" x-show="failModal" x-cloak @click.self="failModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-rose-600">Mark funding failed</h3>
            <p class="mt-1 text-xs text-muted">The customer's wallet debit is refunded automatically.</p>
            <textarea name="reason" required rows="3" class="field mt-3" placeholder="Failure reason"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="failModal=false">Cancel</button><button class="btn btn-danger flex-1">Confirm</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/funding/${cancelTarget}/cancel`" x-show="cancelModal" x-cloak @click.self="cancelModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-strong">Cancel funding request</h3>
            <p class="mt-1 text-xs text-muted">The customer's wallet debit is refunded automatically.</p>
            <textarea name="reason" required rows="3" class="field mt-3" placeholder="Cancellation reason"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="cancelModal=false">Back</button><button class="btn btn-danger flex-1">Confirm cancellation</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/funding/${refundTarget}/refund`" x-show="refundModal" x-cloak @click.self="refundModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-teal-600">Refund funding request</h3>
            <textarea name="reason" required rows="3" class="field mt-3" placeholder="Refund reason"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="refundModal=false">Cancel</button><button class="btn btn-danger flex-1">Confirm refund</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/funding/${noteTarget}/notes`" x-show="noteModal" x-cloak @click.self="noteModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-strong">Add internal note</h3>
            <textarea name="note" required rows="3" class="field mt-3" placeholder="Private, never shown to the customer"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="noteModal=false">Cancel</button><button class="btn btn-primary flex-1">Save note</button></div>
        </div>
    </form>

    @include('admin.funding.partials.drawer')
</div>
@endsection

@push('scripts')
<script>
function fundingConsole() {
    return {
        filtersOpen: false,
        selected: [],
        lastRefreshed: 'just now',
        drawerOpen: false, drawer: null,
        failModal: false, failTarget: null,
        cancelModal: false, cancelTarget: null,
        refundModal: false, refundTarget: null,
        noteModal: false, noteTarget: null,
        requestInfoModal: false, requestInfoTarget: null,
        escalateModal: false, escalateTarget: null,
        completeModal: false, completeTarget: null,
        bulkActionType: '', bulkReviewer: '',
        cols: JSON.parse(localStorage.getItem('admin-funding-cols') || 'null') || ['wallet_app', 'source', 'rate', 'fees', 'type', 'provider', 'reconciliation', 'created', 'completed'],
        colOptions: [
            { key: 'wallet_app', label: 'Wallet app' }, { key: 'source', label: 'Source amount' }, { key: 'rate', label: 'Exchange rate' },
            { key: 'fees', label: 'Fees' }, { key: 'type', label: 'Processing type' }, { key: 'provider', label: 'Provider' },
            { key: 'reconciliation', label: 'Reconciliation' }, { key: 'created', label: 'Created' }, { key: 'completed', label: 'Completed' },
        ],
        init() {
            this.$watch('cols', (v) => localStorage.setItem('admin-funding-cols', JSON.stringify(v)), { deep: true });
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('funding-search', () => this.$refs.searchInput?.focus());
                window.ShortcutManager.registerAction('funding-filters', () => { this.filtersOpen = !this.filtersOpen; });
                window.ShortcutManager.registerAction('funding-refresh', () => window.location.reload());
                window.ShortcutManager.registerAction('funding-note', () => { if (this.drawer) { this.noteTarget = this.drawer.funding.id; this.noteModal = true; } });
                window.ShortcutManager.registerAction('funding-investigate', () => { if (this.drawer) { this.investigate(this.drawer.funding.id); } });
                window.ShortcutManager.registerAction('funding-process', () => { if (this.drawer) { this.retry(this.drawer.funding.id); } });
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; this.failModal = false; this.cancelModal = false; this.refundModal = false; this.noteModal = false; this.requestInfoModal = false; this.escalateModal = false; this.completeModal = false; });
        },
        toggleCol(key) { this.cols = this.cols.includes(key) ? this.cols.filter((c) => c !== key) : [...this.cols, key]; },
        activeFilterCount() {
            const p = new URLSearchParams(window.location.search);
            return ['app_type', 'funding_source', 'country_id', 'currency', 'amount_min', 'amount_max', 'from', 'to', 'automation', 'risk', 'reconciliation_status', 'assigned_to'].filter((k) => p.get(k)).length;
        },
        clearFilters() { window.location = '{{ route('admin.funding.index', ['tab' => $tab]) }}'; },
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
                const res = await fetch(`/admin/funding/${id}/row-detail`);
                this.drawer = await res.json();
            } catch (e) { this.drawerOpen = false; }
        },
        submitTo(id, path) {
            const f = document.createElement('form');
            f.method = 'POST'; f.action = `/admin/funding/${id}/${path}`;
            f.innerHTML = '@csrf';
            document.body.appendChild(f); f.submit();
        },
        retry(id) { this.submitTo(id, 'retry'); },
        requery(id) { this.submitTo(id, 'requery'); },
        investigate(id) { this.submitTo(id, 'investigate'); },
        placeUnderReview(id) { this.submitTo(id, 'under-review'); },
    };
}
</script>
@endpush
