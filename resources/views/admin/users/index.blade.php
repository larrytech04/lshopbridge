@extends('layouts.admin')
@section('page-title', 'Users Management')

@section('content')
@php
    $statCards = [
        ['Total users', $stats['total'], 'Multiple-Users-1--Streamline-Ultimate.svg', '#3B82F6', false],
        ['Active', $stats['active'], 'Recruiting-Employee-Target-Validated-Check-2--Streamline-Ultimate.png', '#10B981', false],
        ['Online now', $stats['online'], 'User-Network--Streamline-Ultimate.png', '#22C55E', false],
        ['New today', $stats['new_today'], 'User-Story--Streamline-Ultimate.png', '#8B5CF6', false],
        ['Pending KYC', $stats['pending_kyc'], 'Work-Pending-For-Review--Streamline-Bangalore.png', '#F59E0B', false],
        ['Verified', $stats['verified'], 'Verified--Streamline-Rounded-Streamline-Material.png', '#0EA5E9', false],
        ['Suspended', $stats['suspended'], 'Disability-Help-Alarm-Sos--Streamline-Ultimate.png', '#F59E0B', false],
        ['Frozen wallets', $stats['frozen_wallets'], 'Money-Wallet-1--Streamline-Ultimate.png', '#F97316', false],
        ['Blocked / banned', $stats['blocked'], 'Shop-Dislike--Streamline-Ultimate.png', '#EF4444', false],
        ['Agents', $stats['agents'], 'Delivery-Package-Give--Streamline-Freehand.png', '#0EA5E9', false],
        ['Administrators', $stats['admins'], 'Settings-User--Streamline-Ultimate.png', '#64748B', false],
        ['Deposits today', $stats['deposits_today'], 'Saving-Bank-Cash--Streamline-Ultimate.png', '#10B981', true],
        ['Funding sent today', $stats['funding_today'], 'Real-Estate-Insurance-Dollar-Hand-House--Streamline-Freehand.png', '#F97316', true],
        ['Open tickets', $stats['open_tickets'], 'Customer-Relationship-Management-Call-Center-Support--Streamline-Ultimate.png', '#EC4899', false],
        ['Fraud alerts', $stats['fraud_alerts'], 'Identity-Theft--Streamline-Brooklyn.png', '#EF4444', false],
        ['Total wallet balance', $stats['total_balance'], 'Money-Bags--Streamline-Ultimate.png', '#7C5CFC', true],
    ];
    $currency = config('platform.base_currency');
    $riskLevelOf = function ($count) {
        return $count === 0 ? ['None', 'emerald'] : ($count === 1 ? ['Low', 'sky'] : ($count <= 3 ? ['Medium', 'amber'] : ['High', 'rose']));
    };
    $tierOf = fn ($points) => $points >= 1000 ? 'Gold' : ($points >= 250 ? 'Silver' : 'Bronze');
@endphp

<div x-data="usersConsole()" x-init="init()" class="space-y-5" @keydown.window="onKey($event)">

    {{-- ============ TITLE ============ --}}
    <div>
        <h1 class="text-2xl font-bold text-strong">Users Management</h1>
        <p class="text-sm text-muted">Manage customers, agents, administrators and merchants across the entire platform.</p>
    </div>

    {{-- ============ LIVE STATS ============ --}}
    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-4 xl:grid-cols-8">
        @foreach ($statCards as [$label, $value, $icon, $tint, $money])
            <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: {{ $tint }}"><x-img-icon :name="$icon" class="h-4 w-4" /></span>
                    <p class="truncate text-[11px] text-faint">{{ $label }}</p>
                </div>
                <p class="mt-2 text-lg font-bold text-strong">
                    <span x-data="counter({{ (float) $value }}, 1200, 0)" x-intersect.once="start()" x-text="display">0</span>@if ($money ?? false) {{ $currency }}@endif
                </p>
            </div>
        @endforeach
    </div>

    {{-- ============ SEARCH + FILTERS + ACTION BAR ============ --}}
    <div class="card-solid space-y-4 rounded-3xl border border-app p-5 shadow-sm">
        <form method="GET" id="filter-form" class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                    <input x-ref="searchInput" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name, email, phone, user ID, referral code, China wallet, agent, country, city…"
                           class="field !rounded-full pl-11 pr-4" @input.debounce.500ms="$el.form.requestSubmit()">
                </div>
                <button type="button" class="qa-btn" @click="filtersOpen = !filtersOpen"><x-icon name="filter" class="h-3.5 w-3.5" /> Filters <span x-show="activeFilterCount() > 0" x-text="'(' + activeFilterCount() + ')'"></span></button>
                <button type="button" class="qa-btn" @click="view = view === 'table' ? 'card' : 'table'"><x-icon :name="'list'" class="h-3.5 w-3.5" /> <span x-text="view === 'table' ? 'Card view' : 'Table view'"></span></button>
                <a href="{{ route('admin.users.export', request()->query()) }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export CSV</a>
                <button type="button" class="qa-btn qa-btn-good" @click="createOpen = true"><x-icon name="plus" class="h-3.5 w-3.5" /> Create user</button>
            </div>

            {{-- Advanced filters (collapsible) --}}
            <div x-show="filtersOpen" x-collapse x-cloak class="grid gap-3 border-t border-app pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <select name="role" class="field"><option value="">All roles</option>@foreach (['user','agent','admin','super_admin'] as $r)<option value="{{ $r }}" @selected(($filters['role'] ?? '')===$r)>{{ ucfirst(str_replace('_',' ',$r)) }}</option>@endforeach</select>
                <select name="status" class="field"><option value="">All status</option>@foreach (['active','suspended','blocked'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
                <select name="kyc_level" class="field"><option value="">Any KYC level</option>@for ($i=0;$i<=3;$i++)<option value="{{ $i }}" @selected(($filters['kyc_level'] ?? '') === (string) $i)>Level {{ $i }}</option>@endfor</select>
                <select name="kyc_status" class="field"><option value="">Any KYC status</option>@foreach (['pending','approved','rejected'] as $s)<option value="{{ $s }}" @selected(($filters['kyc_status'] ?? '')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>

                <select name="country_id" class="field"><option value="">Any country</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(($filters['country_id'] ?? '') == $c->id)>{{ $c->name }}</option>@endforeach</select>
                <select name="online" class="field"><option value="">Any online status</option><option value="1" @selected(($filters['online'] ?? '')==='1')>Online now</option><option value="0" @selected(($filters['online'] ?? '')==='0')>Offline</option></select>
                <select name="risk_level" class="field"><option value="">Any risk level</option>@foreach (['none'=>'None','low'=>'Low','medium'=>'Medium','high'=>'High'] as $val=>$lbl)<option value="{{ $val }}" @selected(($filters['risk_level'] ?? '')===$val)>{{ $lbl }}</option>@endforeach</select>
                <select name="tier" class="field"><option value="">Any loyalty tier</option>@foreach (['bronze'=>'Bronze','silver'=>'Silver','gold'=>'Gold'] as $val=>$lbl)<option value="{{ $val }}" @selected(($filters['tier'] ?? '')===$val)>{{ $lbl }}</option>@endforeach</select>

                <select name="email_verified" class="field"><option value="">Email verified?</option><option value="1" @selected(($filters['email_verified'] ?? '')==='1')>Verified</option><option value="0" @selected(($filters['email_verified'] ?? '')==='0')>Unverified</option></select>
                <select name="phone_verified" class="field"><option value="">Phone verified?</option><option value="1" @selected(($filters['phone_verified'] ?? '')==='1')>Verified</option><option value="0" @selected(($filters['phone_verified'] ?? '')==='0')>Unverified</option></select>
                <select name="two_factor_enabled" class="field"><option value="">2FA enabled?</option><option value="1" @selected(($filters['two_factor_enabled'] ?? '')==='1')>Enabled</option><option value="0" @selected(($filters['two_factor_enabled'] ?? '')==='0')>Disabled</option></select>
                <select name="china_wallet" class="field"><option value="">China wallet linked?</option><option value="1" @selected(($filters['china_wallet'] ?? '')==='1')>Linked</option><option value="0" @selected(($filters['china_wallet'] ?? '')==='0')>Not linked</option></select>

                <select name="agent_status" class="field"><option value="">Any agent status</option>@foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','suspended'=>'Suspended'] as $val=>$lbl)<option value="{{ $val }}" @selected(($filters['agent_status'] ?? '')===$val)>{{ $lbl }}</option>@endforeach</select>
                <select name="currency" class="field"><option value="">Any currency</option>@foreach (['XAF','NGN','GHS','USD','CNY'] as $cur)<option value="{{ $cur }}" @selected(($filters['currency'] ?? '')===$cur)>{{ $cur }}</option>@endforeach</select>
                <select name="tag" class="field"><option value="">Any tag</option>@foreach ($allTags as $t)<option value="{{ $t }}" @selected(($filters['tag'] ?? '')===$t)>{{ $t }}</option>@endforeach</select>
                <div class="flex gap-2"><input type="number" name="balance_min" value="{{ $filters['balance_min'] ?? '' }}" placeholder="Min balance" class="field"><input type="number" name="balance_max" value="{{ $filters['balance_max'] ?? '' }}" placeholder="Max balance" class="field"></div>

                <div class="flex gap-2 sm:col-span-2"><input type="date" name="created_from" value="{{ $filters['created_from'] ?? '' }}" class="field" title="Registered from"><input type="date" name="created_to" value="{{ $filters['created_to'] ?? '' }}" class="field" title="Registered to"></div>
                <div class="flex gap-2 sm:col-span-2">
                    <button class="btn btn-primary flex-1 text-sm">Apply filters</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-ghost flex-1 text-sm">Reset</a>
                </div>
            </div>

            {{-- Saved presets (client-side, localStorage) --}}
            <div x-show="filtersOpen" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
                <span class="text-xs font-semibold text-faint">Presets:</span>
                <template x-for="p in presets" :key="p.name">
                    <span class="inline-flex items-center gap-1 rounded-full surface-2 px-2.5 py-1 text-xs">
                        <a :href="p.query" class="text-body hover:text-strong" x-text="p.name"></a>
                        <button type="button" @click="deletePreset(p.name)" class="text-faint hover:text-rose-500"><x-icon name="x" class="h-3 w-3" /></button>
                    </span>
                </template>
                <button type="button" class="text-xs font-semibold text-brand-600 hover:text-brand-700" @click="savePreset()">+ Save current filters</button>
            </div>
        </form>

        {{-- Bulk action bar (only visible when rows selected) --}}
        <div x-show="selected.length > 0" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
            <span class="text-xs font-semibold text-strong" x-text="selected.length + ' selected'"></span>
            <button type="button" class="qa-btn qa-btn-good" @click="runBulk('verify', true)"><x-icon name="check" class="h-3.5 w-3.5" /> Verify KYC</button>
            <button type="button" class="qa-btn qa-btn-warn" @click="runBulk('suspend', true)"><x-icon name="alert" class="h-3.5 w-3.5" /> Suspend</button>
            <button type="button" class="qa-btn qa-btn-good" @click="runBulk('activate', true)"><x-icon name="check" class="h-3.5 w-3.5" /> Activate</button>
            <button type="button" class="qa-btn" @click="bulkModal = 'credit'"><x-icon name="plus" class="h-3.5 w-3.5" /> Credit wallet</button>
            <button type="button" class="qa-btn" @click="bulkModal = 'debit'"><x-icon name="minus" class="h-3.5 w-3.5" /> Debit wallet</button>
            <button type="button" class="qa-btn" @click="bulkModal = 'notify'"><x-icon name="bell" class="h-3.5 w-3.5" /> Notify</button>
            <button type="button" class="qa-btn" @click="bulkModal = 'tags'"><x-icon name="doc" class="h-3.5 w-3.5" /> Assign tags</button>
            <button type="button" class="qa-btn qa-btn-danger" @click="runBulk('delete', true)"><x-icon name="trash" class="h-3.5 w-3.5" /> Delete</button>
        </div>
    </div>

    {{-- ============ MAIN GRID: TABLE/CARDS + INSIGHTS SIDEBAR ============ --}}
    <div class="grid gap-6 xl:grid-cols-4">
        <div class="min-w-0 xl:col-span-3">

            {{-- ---- TABLE VIEW ---- --}}
            <div x-show="view === 'table'" class="overflow-x-auto rounded-2xl border border-app">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="border-b border-app text-muted" style="background: var(--surface-1);">
                        <tr>
                            <th class="sticky left-0 z-10 px-3 py-3" style="background: var(--surface-1);"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded"></th>
                            <th class="sticky left-10 z-10 px-3 py-3 font-medium" style="background: var(--surface-1);">User</th>
                            <th class="px-3 py-3 font-medium" x-show="cols.includes('country')">Country</th>
                            <th class="px-3 py-3 font-medium" x-show="cols.includes('wallet')">Wallet</th>
                            <th class="px-3 py-3 font-medium" x-show="cols.includes('kyc')">KYC</th>
                            <th class="px-3 py-3 font-medium" x-show="cols.includes('risk')">Risk</th>
                            <th class="px-3 py-3 font-medium" x-show="cols.includes('status')">Status</th>
                            <th class="px-3 py-3 font-medium" x-show="cols.includes('login')">Last login</th>
                            <th class="px-3 py-3 font-medium" x-show="cols.includes('activity')">Activity</th>
                            <th class="px-3 py-3 font-medium" x-show="cols.includes('created')">Created</th>
                            <th class="px-3 py-3 text-right">
                                <button type="button" @click.stop="colsOpen = !colsOpen" class="rounded-lg p-1 hover:surface-2"><x-icon name="cog" class="h-4 w-4" /></button>
                                <div x-show="colsOpen" x-cloak @click.outside="colsOpen = false" class="card-solid absolute right-4 z-30 mt-2 w-48 rounded-xl border border-app p-2 text-left shadow-lg">
                                    <template x-for="c in colOptions" :key="c.key">
                                        <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-normal normal-case text-body hover:surface"><input type="checkbox" :checked="cols.includes(c.key)" @change="toggleCol(c.key)" class="rounded"> <span x-text="c.label"></span></label>
                                    </template>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-app">
                        @forelse ($users as $u)
                            @php
                                [$riskLabel, $riskColor] = $riskLevelOf($u->open_risk_flags_count);
                                $tier = $tierOf($u->points);
                                $online = $u->last_seen_at && $u->last_seen_at->gt(now()->subMinutes(5));
                                $idle = ! $online && $u->last_seen_at && $u->last_seen_at->gt(now()->subMinutes(30));
                                $dotColor = $u->status === 'blocked' ? 'bg-rose-500' : ($u->kyc_status->value === 'pending' ? 'bg-violet-500' : ($online ? 'bg-emerald-500' : ($idle ? 'bg-amber-400' : 'bg-slate-400')));
                            @endphp
                            <tr class="hover:surface" data-search="{{ strtolower($u->name.' '.$u->email.' '.$u->phone) }}">
                                <td class="sticky left-0 z-10 px-3 py-3" style="background: var(--surface-1);"><input type="checkbox" value="{{ $u->id }}" x-model="selected" class="rounded"></td>
                                <td class="sticky left-10 z-10 cursor-pointer px-3 py-3" style="background: var(--surface-1);" @click="toggleExpand({{ $u->id }})">
                                    <div class="flex items-center gap-2.5">
                                        <div class="relative shrink-0">
                                            <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ $u->initials() }}</span>
                                            <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full ring-2 ring-white {{ $dotColor }}"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="flex items-center gap-1 truncate font-medium text-strong">{{ $u->name }}
                                                @if ($u->isKycApproved())<x-verified-tick class="h-3.5 w-3.5 shrink-0" />@endif
                                                @if ($tier === 'Gold')<span class="pill bg-amber-500/15 text-amber-600 text-[9px]">VIP</span>@endif
                                                @if ($u->role->value === 'agent')<span class="pill bg-sky-500/15 text-sky-600 text-[9px]">Agent</span>@endif
                                                @if (in_array($u->role->value, ['admin','super_admin']))<span class="pill bg-slate-500/15 text-slate-600 text-[9px]">Admin</span>@endif
                                            </p>
                                            <p class="truncate text-xs text-faint">{{ $u->email }} · {{ $u->phone ?: '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3" x-show="cols.includes('country')">
                                    @if ($u->country)<span class="inline-flex items-center gap-1.5 text-xs text-body"><x-flag :iso="$u->country->iso2" class="h-3 w-4.5" /> {{ $u->country->name }}</span>@else<span class="text-xs text-faint">—</span>@endif
                                </td>
                                <td class="px-3 py-3 text-xs text-body" x-show="cols.includes('wallet')">{{ money($u->wallets->first()?->balance ?? 0, $u->wallets->first()?->currency ?? $currency) }}</td>
                                <td class="px-3 py-3" x-show="cols.includes('kyc')"><span class="text-xs text-body">L{{ $u->kyc_level }}</span> <x-status-badge :status="$u->kyc_status" class="ml-1 text-[10px]" /></td>
                                <td class="px-3 py-3" x-show="cols.includes('risk')"><span class="pill bg-{{ $riskColor }}-500/15 text-{{ $riskColor }}-600 text-[10px]">{{ $riskLabel }}</span></td>
                                <td class="px-3 py-3" x-show="cols.includes('status')"><x-status-badge :status="$u->status" class="text-[10px]" /></td>
                                <td class="px-3 py-3 text-xs text-faint" x-show="cols.includes('login')">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="px-3 py-3 text-xs text-faint" x-show="cols.includes('activity')">{{ $u->deposits_count }}d · {{ $u->shop_orders_count }}o · {{ $u->disputes_count }}t</td>
                                <td class="px-3 py-3 text-xs text-faint" x-show="cols.includes('created')">{{ $u->created_at->format('M j, Y') }}</td>
                                <td class="px-3 py-3 text-right">
                                    <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                        <button type="button" @click.stop="open = !open" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="chevron-down" class="h-4 w-4" /></button>
                                        <div x-show="open" x-cloak x-transition class="card-solid absolute right-0 z-20 mt-1 w-56 rounded-xl border border-app p-1.5 text-left shadow-lg">
                                            <a href="{{ route('admin.users.show', $u) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="user" class="h-4 w-4" /> Open profile</a>
                                            <a href="{{ route('admin.users.show', $u) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="wallet" class="h-4 w-4" /> Open wallet</a>
                                            <form method="POST" action="{{ route('admin.users.wallet.freeze', $u) }}" onsubmit="return confirm('Toggle wallet freeze for {{ $u->name }}?')">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="lock" class="h-4 w-4" /> Freeze/unfreeze wallet</button></form>
                                            <form method="POST" action="{{ route('admin.users.status', $u) }}" onsubmit="return confirm('Suspend {{ $u->name }}?')">@csrf<input type="hidden" name="status" value="suspended"><button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="alert" class="h-4 w-4" /> Suspend</button></form>
                                            <form method="POST" action="{{ route('admin.users.status', $u) }}" onsubmit="return confirm('Ban {{ $u->name }}?')">@csrf<input type="hidden" name="status" value="blocked"><button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="ban" class="h-4 w-4" /> Ban user</button></form>
                                            <form method="POST" action="{{ route('admin.users.reset-password', $u) }}" onsubmit="return confirm('Send password reset link?')">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="refresh" class="h-4 w-4" /> Reset password</button></form>
                                            <form method="POST" action="{{ route('admin.users.reset-2fa', $u) }}" onsubmit="return confirm('Reset 2FA?')">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="shield" class="h-4 w-4" /> Reset 2FA</button></form>
                                            <form method="POST" action="{{ route('admin.users.impersonate', $u) }}" onsubmit="return confirm('Log in as {{ $u->name }}?')">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="user-circle" class="h-4 w-4" /> Login as user</button></form>
                                            <a href="{{ route('admin.users.show', $u) }}#compliance" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="shield" class="h-4 w-4" /> Approve / reject KYC</a>
                                            @if ($u->agent)<a href="{{ route('admin.agents.show', $u->agent) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="truck" class="h-4 w-4" /> Approve / reject agent</a>@endif
                                            <a href="{{ route('admin.users.activity.export', $u) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="download" class="h-4 w-4" /> Export user</a>
                                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Delete {{ $u->name }}? This is recoverable by an engineer but hides the account immediately.')">@csrf @method('DELETE')<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="trash" class="h-4 w-4" /> Delete</button></form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            {{-- Row expand --}}
                            <tr x-show="expanded === {{ $u->id }}" x-cloak>
                                <td colspan="11" class="surface-2 px-6 py-4">
                                    <template x-if="!rowCache[{{ $u->id }}]"><p class="text-sm text-faint">Loading…</p></template>
                                    <template x-if="rowCache[{{ $u->id }}]">
                                        <div class="grid gap-4 sm:grid-cols-3">
                                            <div>
                                                <p class="text-xs font-semibold uppercase text-faint">Wallet</p>
                                                <p class="mt-1 text-sm font-bold text-strong" x-text="rowCache[{{ $u->id }}]?.wallet_balance"></p>
                                                <p class="mt-3 text-xs font-semibold uppercase text-faint">Notes</p>
                                                <p class="text-sm text-body" x-text="rowCache[{{ $u->id }}]?.notes || '—'"></p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase text-faint">Recent activity</p>
                                                <template x-for="(ev, i) in (rowCache[{{ $u->id }}]?.timeline || [])" :key="i">
                                                    <div class="mt-1.5 flex items-center gap-2 text-xs"><span class="h-2 w-2 rounded-full" :style="`background:${ev.color}`"></span><span class="text-body" x-text="ev.title"></span><span class="text-faint" x-text="ev.at"></span></div>
                                                </template>
                                                <template x-if="(rowCache[{{ $u->id }}]?.timeline || []).length === 0"><p class="text-xs text-faint">No activity.</p></template>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase text-faint">Support tickets</p>
                                                <template x-for="(t, i) in (rowCache[{{ $u->id }}]?.tickets || [])" :key="i"><p class="mt-1 text-xs text-body" x-text="t.subject + ' — ' + t.status"></p></template>
                                                <template x-if="(rowCache[{{ $u->id }}]?.tickets || []).length === 0"><p class="text-xs text-faint">No tickets.</p></template>
                                                <p class="mt-3 text-xs font-semibold uppercase text-faint">China wallets</p>
                                                <template x-for="(w, i) in (rowCache[{{ $u->id }}]?.china_wallets || [])" :key="i"><p class="mt-1 text-xs text-body" x-text="w.name + ' · ' + w.type"></p></template>
                                                <template x-if="(rowCache[{{ $u->id }}]?.china_wallets || []).length === 0"><p class="text-xs text-faint">None linked.</p></template>
                                            </div>
                                        </div>
                                    </template>
                                    <a href="{{ route('admin.users.show', $u) }}" class="mt-3 inline-block text-xs font-semibold text-brand-600 hover:text-brand-700">Open full profile →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="p-0"></td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($users->isEmpty())
                    <x-empty icon="users" title="No users match these filters" message="Try clearing some filters or search terms." />
                @endif
            </div>

            {{-- ---- CARD VIEW ---- --}}
            <div x-show="view === 'card'" x-cloak class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($users as $u)
                    @php
                        [$riskLabel, $riskColor] = $riskLevelOf($u->open_risk_flags_count);
                        $online = $u->last_seen_at && $u->last_seen_at->gt(now()->subMinutes(5));
                    @endphp
                    <a href="{{ route('admin.users.show', $u) }}" class="card-solid block rounded-2xl border border-app p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-center gap-3">
                            <div class="relative shrink-0">
                                <span class="grid h-11 w-11 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">{{ $u->initials() }}</span>
                                <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full ring-2 ring-white {{ $online ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-strong">{{ $u->name }}</p>
                                <p class="truncate text-xs text-faint">{{ $u->email }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <x-status-badge :status="$u->status" class="text-[10px]" />
                            <span class="pill bg-{{ $riskColor }}-500/15 text-{{ $riskColor }}-600 text-[10px]">{{ $riskLabel }}</span>
                            @if ($u->country)<span class="pill surface text-[10px] text-body">{{ $u->country->name }}</span>@endif
                        </div>
                        <p class="mt-3 text-sm font-bold text-strong">{{ money($u->wallets->first()?->balance ?? 0, $u->wallets->first()?->currency ?? $currency) }}</p>
                    </a>
                @endforeach
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
        </div>

        {{-- ============ INSIGHTS SIDEBAR ============ --}}
        <div class="space-y-4">
            <x-glass-card solid>
                <h3 class="font-semibold text-strong">Insights</h3>
                <div class="mt-3 space-y-2.5 text-sm">
                    @if ($insights['highest_depositor'])
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-white" style="background:#10B981"><x-img-icon name="Saving-Bank-Cash--Streamline-Ultimate.png" class="h-3 w-3" /></span>
                            <p class="text-body">Highest depositor: <a href="{{ route('admin.users.show', $insights['highest_depositor']) }}" class="font-semibold text-brand-600 hover:text-brand-700">{{ $insights['highest_depositor']->name }}</a></p>
                        </div>
                    @endif
                    @if ($insights['most_active'])
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-white" style="background:#8B5CF6"><x-img-icon name="Analyze-Data-4--Streamline-Brooklyn.png" class="h-3 w-3" /></span>
                            <p class="text-body">Most active: <a href="{{ route('admin.users.show', $insights['most_active']) }}" class="font-semibold text-brand-600 hover:text-brand-700">{{ $insights['most_active']->name }}</a></p>
                        </div>
                    @endif
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-white" style="background:#3B82F6"><x-img-icon name="User-Story--Streamline-Ultimate.png" class="h-3 w-3" /></span>
                        <p class="text-body">{{ $insights['recent_count'] }} accounts created in the last 24h.</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-white" style="background:#F59E0B"><x-img-icon name="Timezone--Streamline-Ux.png" class="h-3 w-3" /></span>
                        <p class="text-body">{{ $insights['inactive_count'] }} active users haven't logged in for 30+ days.</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-white" style="background:#0EA5E9"><x-img-icon name="Work-Pending-For-Review--Streamline-Bangalore.png" class="h-3 w-3" /></span>
                        <p class="text-body">{{ $insights['pending_kyc_count'] }} accounts have pending KYC.</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-white" style="background:#EC4899"><x-img-icon name="Delivery-Package-Give--Streamline-Freehand.png" class="h-3 w-3" /></span>
                        <p class="text-body">{{ $insights['pending_agents_count'] }} agent applications await review.</p>
                    </div>
                    @if ($insights['suspicious']->isNotEmpty())
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-white" style="background:#EF4444"><x-img-icon name="Identity-Theft--Streamline-Brooklyn.png" class="h-3 w-3" /></span>
                            <p class="text-body">{{ $insights['suspicious']->count() }} accounts have 2+ open risk flags:</p>
                        </div>
                        <ul class="ml-7 list-disc space-y-0.5">
                            @foreach ($insights['suspicious'] as $s)<li><a href="{{ route('admin.users.show', $s) }}" class="text-rose-600 hover:text-rose-700">{{ $s->name }}</a></li>@endforeach
                        </ul>
                    @endif
                </div>
                <p class="mt-3 text-[10px] text-faint">Computed live from platform data.</p>
            </x-glass-card>

            <x-glass-card solid>
                <h3 class="font-semibold text-strong">Registrations · 14 days</h3>
                @php $maxReg = max(1, $regTrend->max('count')); @endphp
                <div class="mt-4 flex h-28 items-end gap-1">
                    @foreach ($regTrend as $d)
                        <div class="group flex flex-1 flex-col items-center gap-1">
                            <div class="w-full rounded-t bg-brand-500/80" style="height: {{ max(3, ($d['count'] / $maxReg) * 100) }}%" title="{{ $d['count'] }} on {{ $d['label'] }}"></div>
                            <span class="text-[9px] text-faint">{{ $d['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-glass-card>
        </div>
    </div>

    {{-- ============ CREATE USER MODAL ============ --}}
    <div x-show="createOpen" x-cloak @click.self="createOpen=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.outside="createOpen=false">
            <h3 class="font-semibold text-strong">Create user</h3>
            <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 space-y-3">
                @csrf
                <div><label class="label">Full name</label><input name="name" required class="field"></div>
                <div><label class="label">Email</label><input name="email" type="email" required class="field"></div>
                <div><label class="label">Password</label><input name="password" type="password" required minlength="8" class="field"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="label">Role</label><select name="role" class="field"><option value="user">User</option><option value="agent">Agent</option><option value="admin">Admin</option></select></div>
                    <div><label class="label">Phone</label><input name="phone" class="field"></div>
                </div>
                <div class="flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="createOpen=false">Cancel</button><button class="btn btn-primary flex-1">Create</button></div>
            </form>
        </div>
    </div>

    {{-- ============ BULK ACTION FORM + MODALS ============ --}}
    <form :action="'{{ route('admin.users.bulk-action') }}'" method="POST" x-ref="bulkForm" class="hidden">
        @csrf
        <input type="hidden" name="action" x-bind:value="bulkActionType">
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <input type="hidden" name="amount" x-bind:value="bulkAmount">
        <input type="hidden" name="reason" x-bind:value="bulkReason">
        <input type="hidden" name="subject" x-bind:value="bulkSubject">
        <input type="hidden" name="body" x-bind:value="bulkBody">
        <input type="hidden" name="tags" x-bind:value="bulkTags">
    </form>

    <div x-show="bulkModal" x-cloak @click.self="bulkModal=null" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.outside="bulkModal=null">
            <h3 class="font-semibold text-strong" x-text="'Bulk ' + bulkModal + ' — ' + selected.length + ' users'"></h3>
            <div class="mt-4 space-y-3">
                <template x-if="bulkModal === 'credit' || bulkModal === 'debit'">
                    <div class="space-y-3">
                        <div><label class="label">Amount ({{ $currency }})</label><input type="number" step="0.01" x-model="bulkAmount" class="field"></div>
                        <div><label class="label">Reason</label><input x-model="bulkReason" class="field"></div>
                    </div>
                </template>
                <template x-if="bulkModal === 'notify'">
                    <div class="space-y-3">
                        <div><label class="label">Subject</label><input x-model="bulkSubject" class="field"></div>
                        <div><label class="label">Message</label><textarea x-model="bulkBody" rows="3" class="field"></textarea></div>
                    </div>
                </template>
                <template x-if="bulkModal === 'tags'">
                    <div><label class="label">Tags (comma separated)</label><input x-model="bulkTags" placeholder="vip, wholesale, watch-list" class="field"></div>
                </template>
                <div class="flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="bulkModal=null">Cancel</button><button type="button" class="btn btn-primary flex-1" @click="runBulk(bulkModal, false)">Apply</button></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function usersConsole() {
    return {
        view: localStorage.getItem('admin-users-view') || 'table',
        filtersOpen: false,
        colsOpen: false,
        createOpen: false,
        expanded: null,
        rowCache: {},
        selected: [],
        bulkModal: null,
        bulkActionType: '',
        bulkAmount: '', bulkReason: '', bulkSubject: '', bulkBody: '', bulkTags: '',
        cols: JSON.parse(localStorage.getItem('admin-users-cols') || 'null') || ['country', 'wallet', 'kyc', 'risk', 'status', 'login', 'activity', 'created'],
        colOptions: [
            { key: 'country', label: 'Country' }, { key: 'wallet', label: 'Wallet balance' }, { key: 'kyc', label: 'KYC' },
            { key: 'risk', label: 'Risk' }, { key: 'status', label: 'Status' }, { key: 'login', label: 'Last login' },
            { key: 'activity', label: 'Activity' }, { key: 'created', label: 'Created' },
        ],
        presets: JSON.parse(localStorage.getItem('admin-users-presets') || '[]'),
        init() {
            this.$watch('view', (v) => localStorage.setItem('admin-users-view', v));
            this.$watch('cols', (v) => localStorage.setItem('admin-users-cols', JSON.stringify(v)), { deep: true });
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('users-search', () => this.$refs.searchInput?.focus());
                window.ShortcutManager.registerAction('users-create', () => { this.createOpen = true; });
                window.ShortcutManager.registerAction('users-filters', () => { this.filtersOpen = !this.filtersOpen; });
                window.ShortcutManager.registerAction('users-export', () => { window.location = '{{ route('admin.users.export', request()->query()) }}'; });
                window.ShortcutManager.registerAction('users-refresh', () => window.location.reload());
            }
        },
        toggleCol(key) { this.cols = this.cols.includes(key) ? this.cols.filter(c => c !== key) : [...this.cols, key]; },
        toggleAll(checked) {
            this.selected = checked ? Array.from(document.querySelectorAll('tbody input[type=checkbox][value]')).map(el => parseInt(el.value)) : [];
        },
        activeFilterCount() {
            const params = new URLSearchParams(window.location.search);
            let n = 0;
            for (const [k] of params) if (!['page', 'q'].includes(k)) n++;
            return n;
        },
        async toggleExpand(id) {
            if (this.expanded === id) { this.expanded = null; return; }
            this.expanded = id;
            if (!this.rowCache[id]) {
                try {
                    const res = await fetch(`/admin/users/${id}/row-detail`);
                    this.rowCache[id] = await res.json();
                } catch (e) { this.rowCache[id] = {}; }
            }
        },
        runBulk(action, needsConfirm) {
            if (this.selected.length === 0) return;
            if (needsConfirm && !confirm(`${action} ${this.selected.length} selected users?`)) return;
            this.bulkActionType = action;
            this.bulkModal = null;
            this.$nextTick(() => this.$refs.bulkForm.submit());
        },
        savePreset() {
            const name = prompt('Name this filter preset:');
            if (!name) return;
            this.presets.push({ name, query: window.location.pathname + window.location.search });
            localStorage.setItem('admin-users-presets', JSON.stringify(this.presets));
        },
        deletePreset(name) {
            this.presets = this.presets.filter(p => p.name !== name);
            localStorage.setItem('admin-users-presets', JSON.stringify(this.presets));
        },
        onKey(e) {
            if (e.key === 'Escape') { this.createOpen = false; this.bulkModal = null; this.colsOpen = false; }
        },
    };
}
</script>
@endpush
