@extends('layouts.admin')
@section('page-title', 'China Wallet Accounts')

@section('content')
@php
    $tabs = [
        'all' => ['All', $counts['all']],
        'pending' => ['Pending', $counts['pending']],
        'approved' => ['Approved', $counts['approved']],
        'rejected' => ['Rejected', $counts['rejected']],
        'suspended' => ['Suspended', $counts['suspended']],
    ];
    $summary = [
        ['Total wallet accounts', $counts['all'], 'wallet', 'slate', null],
        ['Pending review', $counts['pending'], 'clock', 'amber', 'pending'],
        ['Approved', $counts['approved'], 'check-circle', 'emerald', 'approved'],
        ['Rejected', $counts['rejected'], 'ban', 'rose', 'rejected'],
        ['Suspended', $counts['suspended'], 'lock', 'rose', 'suspended'],
        ['Needs update', $counts['needs_update'], 'alert', 'amber', null],
    ];
@endphp

<div x-data="walletsConsole()" x-init="init()" class="space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">China Wallet Accounts</h1>
            <p class="text-sm text-muted">Review and manage customer China wallet accounts used for wallet funding.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="qa-btn" @click="window.location.reload()"><x-icon name="refresh" class="h-3.5 w-3.5" /> Refresh</button>
            <a href="{{ route('admin.beneficiaries.export', request()->query()) }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export</a>
            <a href="{{ route('admin.settings.index') }}" class="qa-btn"><x-icon name="cog" class="h-3.5 w-3.5" /> Wallet settings</a>
        </div>
    </div>

    {{-- ============ SUMMARY CARDS ============ --}}
    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($summary as [$label, $value, $icon, $tint, $tabTarget])
            <a href="{{ $tabTarget ? route('admin.beneficiaries.index', ['tab' => $tabTarget]) : route('admin.beneficiaries.index') }}" class="card-solid rounded-2xl border border-app p-3.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-{{ $tint }}-500/15 text-{{ $tint }}-600"><x-icon :name="$icon" class="h-4 w-4" /></span>
                    <p class="truncate text-[11px] text-faint">{{ $label }}</p>
                </div>
                <p class="mt-2 text-lg font-bold text-strong">{{ $value }}</p>
            </a>
        @endforeach
    </div>

    {{-- ============ TABS ============ --}}
    <div class="no-scrollbar flex gap-1.5 overflow-x-auto rounded-2xl border border-app p-1.5" style="background: var(--surface-1);">
        @foreach ($tabs as $key => [$label, $count])
            <a href="{{ route('admin.beneficiaries.index', ['tab' => $key]) }}" class="mu-tab {{ $tab === $key ? 'mu-tab-active' : '' }} whitespace-nowrap">
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
                    <input x-ref="searchInput" name="q" value="{{ $q }}" placeholder="Search customer, email, phone, user ID, wallet name/number…"
                           class="field !rounded-full pl-11 pr-4" @input.debounce.500ms="$el.form.requestSubmit()">
                </div>
                <button type="button" class="qa-btn" @click="filtersOpen = !filtersOpen"><x-icon name="filter" class="h-3.5 w-3.5" /> Filters <span x-show="activeFilterCount() > 0" x-text="'(' + activeFilterCount() + ')'"></span></button>
                <button type="button" class="qa-btn" @click="clearFilters()">Clear filters</button>
            </div>

            <div x-show="filtersOpen" x-collapse x-cloak class="grid gap-3 border-t border-app pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <select name="app_type" class="field"><option value="">Any wallet type</option>@foreach ($appTypes as $val => $lbl)<option value="{{ $val }}" @selected(request('app_type')===$val)>{{ $lbl }}</option>@endforeach</select>
                <select name="country_id" class="field"><option value="">Any country</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(request('country_id') == $c->id)>{{ $c->name }}</option>@endforeach</select>
                <select name="kyc_level" class="field"><option value="">Any KYC level</option>@for ($i=0;$i<=3;$i++)<option value="{{ $i }}" @selected(request('kyc_level') == $i)>Level {{ $i }}</option>@endfor</select>
                <div class="flex gap-2"><input type="date" name="from" value="{{ request('from') }}" class="field" title="Submitted from"><input type="date" name="to" value="{{ request('to') }}" class="field" title="Submitted to"></div>
                <div class="flex gap-2 sm:col-span-2">
                    <button class="btn btn-primary flex-1 text-sm">Apply filters</button>
                    <a href="{{ route('admin.beneficiaries.index', ['tab' => $tab]) }}" class="btn btn-ghost flex-1 text-sm">Reset</a>
                </div>
            </div>
        </form>

        {{-- Bulk bar — assignment/suspend/restore/archive/export only. No bulk approve/reject. --}}
        <div x-show="selected.length > 0" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
            <span class="text-xs font-semibold text-strong" x-text="selected.length + ' selected'"></span>
            <button type="button" class="qa-btn" @click="runBulk('assign')"><x-icon name="eye" class="h-3.5 w-3.5" /> Assign for review</button>
            <button type="button" class="qa-btn qa-btn-warn" @click="bulkModal = 'suspend'"><x-icon name="ban" class="h-3.5 w-3.5" /> Suspend</button>
            <button type="button" class="qa-btn qa-btn-good" @click="runBulk('restore')"><x-icon name="refresh" class="h-3.5 w-3.5" /> Restore</button>
            <button type="button" class="qa-btn qa-btn-danger" @click="bulkModal = 'archive'"><x-icon name="trash" class="h-3.5 w-3.5" /> Archive</button>
            <span class="text-[11px] text-faint">Bulk approve/reject is disabled by design — each wallet account is reviewed individually.</span>
        </div>
    </div>

    {{-- ============ TABLE ============ --}}
    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[1200px] text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded"></th>
                    <th class="px-3 py-3 font-medium">User</th>
                    <th class="px-3 py-3 font-medium">Wallet app</th>
                    <th class="px-3 py-3 font-medium">Account name</th>
                    <th class="px-3 py-3 font-medium">Account identifier</th>
                    <th class="px-3 py-3 font-medium">Country</th>
                    <th class="px-3 py-3 font-medium">QR</th>
                    <th class="px-3 py-3 font-medium">Ownership match</th>
                    <th class="px-3 py-3 font-medium">Submitted</th>
                    <th class="px-3 py-3 font-medium">Status</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @forelse ($items as $b)
                    @php
                        $masked = $b->account_id ? (strlen($b->account_id) <= 4 ? str_repeat('*', strlen($b->account_id)) : substr($b->account_id,0,2).str_repeat('*', max(2, strlen($b->account_id)-4)).substr($b->account_id,-2)) : '—';
                        $match = $b->nameMatch();
                        $matchColor = match($match) { 'match' => 'emerald', 'partial' => 'amber', 'mismatch' => 'rose', default => 'slate' };
                    @endphp
                    <tr class="hover:surface cursor-pointer" @click="openDrawer({{ $b->id }})">
                        <td class="px-3 py-3" @click.stop><input type="checkbox" value="{{ $b->id }}" x-model="selected" class="rounded"></td>
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ strtoupper(substr($b->user?->name ?? '?', 0, 2)) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-strong">{{ $b->user?->name ?? '—' }}</p>
                                    <p class="truncate text-xs text-faint">{{ $b->user?->email }} · #{{ $b->user_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-xs text-body">{{ $b->app_type->label() }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $b->account_name }}</td>
                        <td class="px-3 py-3 text-xs text-body font-mono">{{ $masked }}</td>
                        <td class="px-3 py-3 text-xs text-body">
                            @if ($b->user?->country)<span class="inline-flex items-center gap-1.5"><x-flag :iso="$b->user->country->iso2" class="h-3 w-4.5" /> {{ $b->user->country->name }}</span>@else—@endif
                        </td>
                        <td class="px-3 py-3">@if ($b->qr_path)<span class="pill bg-sky-500/15 text-sky-600 text-[10px]">Submitted</span>@else<span class="text-xs text-faint">None</span>@endif</td>
                        <td class="px-3 py-3"><span class="pill bg-{{ $matchColor }}-500/15 text-{{ $matchColor }}-600 text-[10px]">{{ ucfirst($match) }}</span></td>
                        <td class="px-3 py-3 text-xs text-faint">{{ $b->created_at->diffForHumans() }}</td>
                        <td class="px-3 py-3"><x-status-badge :status="$b->status" class="text-[10px]" /></td>
                        <td class="px-3 py-3 text-right" @click.stop>
                            <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="chevron-down" class="h-4 w-4" /></button>
                                <div x-show="open" x-cloak x-transition class="card-solid absolute right-0 z-20 mt-1 w-52 rounded-xl border border-app p-1.5 text-left shadow-lg">
                                    <button type="button" @click="openDrawer({{ $b->id }}); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="eye" class="h-4 w-4" /> Review account</button>
                                    @if (in_array($b->status->value, ['pending','in_review','more_info_requested'], true))
                                        <form method="POST" action="{{ route('admin.beneficiaries.approve', $b) }}" onsubmit="return confirm('Approve this wallet account?')">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="check" class="h-4 w-4" /> Approve</button></form>
                                        <button type="button" @click="requestInfoTarget={{ $b->id }}; requestInfoModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="alert" class="h-4 w-4" /> Request information</button>
                                        <button type="button" @click="rejectTarget={{ $b->id }}; rejectModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="x" class="h-4 w-4" /> Reject</button>
                                    @endif
                                    @if ($b->status->value !== 'suspended')
                                        <button type="button" @click="suspendTarget={{ $b->id }}; suspendModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-amber-600 hover:surface"><x-icon name="ban" class="h-4 w-4" /> Suspend</button>
                                    @else
                                        <form method="POST" action="{{ route('admin.beneficiaries.restore', $b) }}" onsubmit="return confirm('Restore this wallet account?')">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="refresh" class="h-4 w-4" /> Restore</button></form>
                                    @endif
                                    @if ($b->user)<a href="{{ route('admin.users.show', $b->user) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="user-circle" class="h-4 w-4" /> Open customer</a>@endif
                                    <button type="button" @click="deleteTarget={{ $b->id }}; deleteModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="trash" class="h-4 w-4" /> Archive</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="p-0">
                        @if ($tab === 'pending')
                            <x-empty icon="check-circle" title="No accounts awaiting review" message="All submitted China wallet accounts have been reviewed." />
                        @else
                            <x-empty icon="wallet" title="No wallet accounts found" message="There are no China wallet accounts matching the selected status or filters.">
                                <x-slot:action>
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('admin.beneficiaries.index') }}" class="qa-btn">Clear filters</a>
                                        <button type="button" class="qa-btn" @click="window.location.reload()">Refresh</button>
                                        <a href="{{ route('admin.beneficiaries.index', ['tab' => 'all']) }}" class="qa-btn">View all accounts</a>
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

    {{-- ============ BULK / REJECT / SUSPEND / REQUEST-INFO / DELETE FORMS ============ --}}
    <form :action="'{{ route('admin.beneficiaries.bulk-action') }}'" method="POST" x-ref="bulkForm" class="hidden">
        @csrf
        <input type="hidden" name="action" x-bind:value="bulkActionType">
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <input type="hidden" name="reason" x-bind:value="bulkReason">
    </form>

    <div x-show="bulkModal" x-cloak @click.self="bulkModal=null" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6">
            <h3 class="font-semibold text-strong" x-text="'Bulk ' + bulkModal + ' — ' + selected.length + ' accounts'"></h3>
            <div class="mt-4 space-y-3">
                <div><label class="label">Reason</label><textarea x-model="bulkReason" rows="2" class="field" required></textarea></div>
                <div class="flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="bulkModal=null">Cancel</button><button type="button" class="btn btn-primary flex-1" @click="runBulk(bulkModal)">Apply</button></div>
            </div>
        </div>
    </div>

    <form method="POST" :action="`/admin/beneficiaries/${rejectTarget}/reject`" x-show="rejectModal" x-cloak @click.self="rejectModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-rose-600">Reject wallet account</h3>
            <select name="category" class="field mt-3">
                <option value="">Rejection category…</option>
                <option value="unclear_qr">Unclear QR code</option>
                <option value="name_mismatch">Name mismatch</option>
                <option value="duplicate">Duplicate account</option>
                <option value="invalid_details">Invalid details</option>
                <option value="other">Other</option>
            </select>
            <textarea name="reason" required rows="2" class="field mt-2" placeholder="Reason shown to the customer"></textarea>
            <label class="mt-2 flex items-center gap-2 text-xs text-body"><input type="hidden" name="resubmission_allowed" value="0"><input type="checkbox" name="resubmission_allowed" value="1" checked class="rounded"> Allow the customer to resubmit</label>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="rejectModal=false">Cancel</button><button class="btn btn-danger flex-1">Reject</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/beneficiaries/${suspendTarget}/suspend`" x-show="suspendModal" x-cloak @click.self="suspendModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-amber-600">Suspend wallet account</h3>
            <textarea name="reason" required rows="3" class="field mt-3" placeholder="Reason (shown to the customer)"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="suspendModal=false">Cancel</button><button class="btn btn-danger flex-1">Suspend</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/beneficiaries/${requestInfoTarget}/request-info`" x-show="requestInfoModal" x-cloak @click.self="requestInfoModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-strong">Request more information</h3>
            <select name="reason_key" required class="field mt-3">
                <option value="name_missing">Account name missing</option>
                <option value="identifier_missing">Account identifier missing</option>
                <option value="qr_unclear">QR code unclear</option>
                <option value="wrong_app">Wrong wallet app selected</option>
                <option value="name_mismatch">Name does not match KYC</option>
                <option value="duplicate">Duplicate account detected</option>
                <option value="screenshot_required">Additional screenshot required</option>
                <option value="custom">Custom reason</option>
            </select>
            <textarea name="message" rows="2" class="field mt-2" placeholder="Custom message (optional)"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="requestInfoModal=false">Cancel</button><button class="btn btn-primary flex-1">Send request</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/beneficiaries/${deleteTarget}`" x-show="deleteModal" x-cloak @click.self="deleteModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf @method('DELETE')
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-rose-600">Archive wallet account</h3>
            <p class="mt-1 text-xs text-muted">This removes the account from active review. Funding history tied to it is preserved.</p>
            <textarea name="reason" required rows="2" class="field mt-3" placeholder="Reason for archiving"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="deleteModal=false">Cancel</button><button class="btn btn-danger flex-1">Archive</button></div>
        </div>
    </form>

    {{-- ============ REVIEW DRAWER ============ --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/50">
        <div class="h-full w-full max-w-xl overflow-y-auto card-solid border-l border-app p-6" @click.outside="drawerOpen=false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <template x-if="!drawer">
                <div class="space-y-3"><div class="skel-block h-8 w-40"></div><div class="skel-block h-24"></div><div class="skel-block h-24"></div></div>
            </template>
            <template x-if="drawer">
                <div class="space-y-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-strong" x-text="drawer.account.customer"></h2>
                            <p class="text-xs text-faint" x-text="drawer.account.app_type + ' · #' + drawer.account.user_id"></p>
                        </div>
                        <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="drawerOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
                    </div>

                    <template x-if="drawer.duplicates.length > 0">
                        <div class="rounded-xl bg-amber-500/10 p-3 text-xs text-amber-700">
                            <p class="font-semibold">Possible duplicate detected</p>
                            <template x-for="d in drawer.duplicates" :key="d.beneficiary_account_id">
                                <p x-text="d.user + ' — ' + d.match"></p>
                            </template>
                            <p class="mt-1 text-[11px]">This is a warning only — review carefully before deciding, it does not auto-reject.</p>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><p class="text-xs text-faint">Email</p><p class="text-body" x-text="drawer.account.email"></p></div>
                        <div><p class="text-xs text-faint">Phone</p><p class="text-body" x-text="drawer.account.phone || '—'"></p></div>
                        <div><p class="text-xs text-faint">Country</p><p class="text-body" x-text="drawer.account.country || '—'"></p></div>
                        <div><p class="text-xs text-faint">KYC level</p><p class="text-body" x-text="'Level ' + drawer.account.kyc_level"></p></div>
                        <div><p class="text-xs text-faint">Wallet account name</p><p class="text-body" x-text="drawer.account.account_name"></p></div>
                        <div>
                            <p class="text-xs text-faint">Wallet identifier</p>
                            <p class="text-body">
                                <span x-show="!revealed" x-text="drawer.account.account_id_masked"></span>
                                <span x-show="revealed" x-cloak x-text="revealedValue"></span>
                                <button type="button" class="ml-1 text-brand-600" @click="reveal()"><x-icon name="eye" class="h-3.5 w-3.5" /></button>
                            </p>
                        </div>
                        <div><p class="text-xs text-faint">Submitted</p><p class="text-body" x-text="drawer.account.submitted"></p></div>
                        <div><p class="text-xs text-faint">Status</p><p class="text-body" x-text="drawer.account.status_label"></p></div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase text-faint">Secure QR viewer</p>
                        <template x-if="drawer.account.has_qr">
                            <div class="mt-2 rounded-xl border border-app surface-2 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="qa-btn !px-2" @click="qrZoom = Math.max(0.5, qrZoom - 0.25)"><x-icon name="minus" class="h-3.5 w-3.5" /></button>
                                        <button type="button" class="qa-btn !px-2" @click="qrZoom = Math.min(3, qrZoom + 0.25)"><x-icon name="plus" class="h-3.5 w-3.5" /></button>
                                        <button type="button" class="qa-btn !px-2" @click="qrRotate = (qrRotate + 90) % 360"><x-icon name="refresh" class="h-3.5 w-3.5" /></button>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="qa-btn !px-2" @click="qrFullscreen = true"><x-icon name="eye" class="h-3.5 w-3.5" /> Full screen</button>
                                        <a :href="`/files/beneficiary-qr/${drawer.account.id}`" download class="qa-btn !px-2"><x-icon name="download" class="h-3.5 w-3.5" /></a>
                                    </div>
                                </div>
                                <div class="mt-3 grid place-items-center overflow-hidden rounded-lg" style="min-height:160px">
                                    <img :src="`/files/beneficiary-qr/${drawer.account.id}`" :style="`transform: scale(${qrZoom}) rotate(${qrRotate}deg); transition: transform .15s;`" class="max-h-48 select-none" draggable="false">
                                </div>
                                <p class="mt-2 text-center text-[11px] text-faint">Streamed from the private disk. Never a public URL. Views are access-logged.</p>
                            </div>
                        </template>
                        <template x-if="!drawer.account.has_qr">
                            <p class="mt-2 text-sm text-faint">No QR code submitted.</p>
                        </template>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase text-faint">Verification checklist</p>
                        <div class="mt-2 space-y-1.5 text-xs">
                            <template x-for="[key, item] in Object.entries(drawer.account.checklist)" :key="key">
                                <div class="flex items-center justify-between rounded-lg surface-2 px-2 py-1.5">
                                    <span class="capitalize text-body" x-text="key.replace(/_/g,' ')"></span>
                                    <select class="field !w-auto !py-1 text-[11px]" :value="item.status" @change="setChecklist(key, $event.target.value)">
                                        <option value="not_checked">Not checked</option>
                                        <option value="passed">Passed</option>
                                        <option value="warning">Warning</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                            </template>
                        </div>
                    </div>

                    <template x-if="drawer.funding">
                        <div class="border-t border-app pt-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold uppercase text-faint">Funding activity</p>
                                <a href="{{ route('admin.deposits.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Open full history →</a>
                            </div>
                            <div class="mt-2 grid grid-cols-4 gap-2 text-center">
                                <div class="rounded-xl surface-2 p-2"><p class="text-lg font-bold text-strong" x-text="drawer.funding.total"></p><p class="text-[10px] text-faint">Total</p></div>
                                <div class="rounded-xl surface-2 p-2"><p class="text-lg font-bold text-emerald-600" x-text="drawer.funding.successful"></p><p class="text-[10px] text-faint">Successful</p></div>
                                <div class="rounded-xl surface-2 p-2"><p class="text-lg font-bold text-sky-600" x-text="drawer.funding.pending"></p><p class="text-[10px] text-faint">Pending</p></div>
                                <div class="rounded-xl surface-2 p-2"><p class="text-lg font-bold text-rose-600" x-text="drawer.funding.failed"></p><p class="text-[10px] text-faint">Failed</p></div>
                            </div>
                            <p class="mt-2 text-xs text-body">Total funded: <span class="font-semibold" x-text="drawer.funding.total_funded"></span> CNY</p>
                            <p class="text-xs text-faint">Last request: <span x-text="drawer.funding.last_request || '—'"></span> · Last successful: <span x-text="drawer.funding.last_successful || '—'"></span></p>
                        </div>
                    </template>

                    <div class="flex flex-wrap gap-2 border-t border-app pt-3">
                        <template x-if="['pending','in_review','more_info_requested'].includes(drawer.account.status)">
                            <span class="flex flex-wrap gap-2">
                                <button type="button" class="qa-btn qa-btn-good" @click="approveFromDrawer()">Approve</button>
                                <button type="button" class="qa-btn" @click="requestInfoTarget = drawer.account.id; requestInfoModal = true">Request info</button>
                                <button type="button" class="qa-btn qa-btn-danger" @click="rejectTarget = drawer.account.id; rejectModal = true">Reject</button>
                            </span>
                        </template>
                        <template x-if="drawer.account.status !== 'suspended'">
                            <button type="button" class="qa-btn qa-btn-warn" @click="suspendTarget = drawer.account.id; suspendModal = true">Suspend</button>
                        </template>
                        <template x-if="drawer.account.status === 'suspended'">
                            <button type="button" class="qa-btn qa-btn-good" @click="restoreFromDrawer()">Restore</button>
                        </template>
                    </div>

                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Previous submissions</p>
                        <div class="mt-2 space-y-1.5 text-xs">
                            <template x-if="drawer.history.length === 0"><p class="text-faint">No previous submissions.</p></template>
                            <template x-for="h in drawer.history" :key="h.submitted">
                                <p class="text-body" x-text="h.submitted + ' · ' + h.app_type + ' · ' + h.status"></p>
                            </template>
                        </div>
                    </div>

                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Wallet history</p>
                        <div class="mu-timeline mt-2">
                            <template x-for="e in drawer.events" :key="e.at + e.event">
                                <div class="mu-timeline-item">
                                    <span class="mu-timeline-dot"></span>
                                    <p class="text-sm font-medium capitalize text-body" x-text="e.event.replace(/_/g,' ')"></p>
                                    <p class="text-xs text-muted" x-text="e.reason"></p>
                                    <p class="text-xs text-faint" x-text="e.actor + ' · ' + e.at"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Internal notes (private, never shown to the customer)</p>
                        <form method="POST" :action="`/admin/beneficiaries/${drawer.account.id}/notes`" class="mt-2">
                            @csrf
                            <textarea name="admin_notes" rows="3" class="field text-sm" x-text="drawer.account.admin_notes"></textarea>
                            <button class="qa-btn mt-2">Save note</button>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="qrFullscreen" x-cloak @click.self="qrFullscreen=false" class="fixed inset-0 z-[70] grid place-items-center bg-black/90 p-6">
        <template x-if="qrFullscreen && drawer && drawer.account.has_qr">
            <img :src="`/files/beneficiary-qr/${drawer.account.id}`" :style="`transform: scale(${qrZoom}) rotate(${qrRotate}deg);`" class="max-h-[80vh] max-w-[80vw]">
        </template>
        <button type="button" class="absolute right-6 top-6 rounded-lg bg-white/10 p-2 text-white" @click="qrFullscreen=false"><x-icon name="x" class="h-5 w-5" /></button>
    </div>
</div>
@endsection

@push('scripts')
<script>
function walletsConsole() {
    return {
        filtersOpen: false,
        selected: [],
        drawerOpen: false, drawer: null,
        revealed: false, revealedValue: '',
        qrZoom: 1, qrRotate: 0, qrFullscreen: false,
        rejectModal: false, rejectTarget: null,
        suspendModal: false, suspendTarget: null,
        requestInfoModal: false, requestInfoTarget: null,
        deleteModal: false, deleteTarget: null,
        bulkModal: null, bulkActionType: '', bulkReason: '',
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('wallets-search', () => this.$refs.searchInput?.focus());
                window.ShortcutManager.registerAction('wallets-filters', () => { this.filtersOpen = !this.filtersOpen; });
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; this.bulkModal = null; this.rejectModal = false; this.suspendModal = false; this.requestInfoModal = false; this.deleteModal = false; this.qrFullscreen = false; });
        },
        activeFilterCount() {
            const p = new URLSearchParams(window.location.search);
            return ['app_type', 'country_id', 'kyc_level', 'from', 'to'].filter((k) => p.get(k)).length;
        },
        clearFilters() { window.location = '{{ route('admin.beneficiaries.index', ['tab' => $tab]) }}'; },
        toggleAll(checked) { this.selected = checked ? @json($items->pluck('id')) : []; },
        runBulk(action) {
            if (this.selected.length === 0) return;
            this.bulkActionType = action;
            this.bulkModal = null;
            this.$nextTick(() => this.$refs.bulkForm.submit());
        },
        async openDrawer(id) {
            this.drawerOpen = true;
            this.drawer = null;
            this.revealed = false;
            this.qrZoom = 1; this.qrRotate = 0;
            try {
                const res = await fetch(`/admin/beneficiaries/${id}/row-detail`);
                this.drawer = await res.json();
            } catch (e) { this.drawerOpen = false; }
        },
        async reveal() {
            const res = await fetch(`/admin/beneficiaries/${this.drawer.account.id}/reveal`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
            });
            const data = await res.json();
            this.revealedValue = data.account_id;
            this.revealed = true;
        },
        setChecklist(key, status) {
            fetch(`/admin/beneficiaries/${this.drawer.account.id}/review-check`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ key, status }),
            });
            this.drawer.account.checklist[key].status = status;
        },
        approveFromDrawer() {
            if (!confirm('Approve this wallet account?')) return;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = `/admin/beneficiaries/${this.drawer.account.id}/approve`;
            f.innerHTML = '@csrf';
            document.body.appendChild(f); f.submit();
        },
        restoreFromDrawer() {
            if (!confirm('Restore this wallet account?')) return;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = `/admin/beneficiaries/${this.drawer.account.id}/restore`;
            f.innerHTML = '@csrf';
            document.body.appendChild(f); f.submit();
        },
    };
}
</script>
@endpush
