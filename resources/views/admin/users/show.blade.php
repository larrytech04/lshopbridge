@extends('layouts.admin')
@section('page-title', $user->name)

@section('content')
@php
    $primaryWallet = $user->wallets->first();
    $available = $primaryWallet ? ((float) $primaryWallet->balance - (float) $primaryWallet->locked_balance) : 0;
    $currency = $primaryWallet->currency ?? config('platform.base_currency');

    $openFlags = $flags->where('status', 'open');
    $riskCount = $openFlags->count();
    $riskLevel = $riskCount === 0 ? 'None' : ($riskCount === 1 ? 'Low' : ($riskCount <= 3 ? 'Medium' : 'High'));
    $riskColor = match ($riskLevel) { 'None' => 'emerald', 'Low' => 'sky', 'Medium' => 'amber', default => 'rose' };

    $points = (int) $user->points;
    $tier = $points >= 1000 ? 'Gold' : ($points >= 250 ? 'Silver' : 'Bronze');

    $completionFields = [$user->name, $user->email, $user->phone, $user->country_id, $user->city, $user->address, $user->date_of_birth, $user->gender, $user->email_verified_at, $user->phone_verified_at];
    $completion = (int) round(collect($completionFields)->filter(fn ($f) => ! is_null($f) && $f !== '')->count() / count($completionFields) * 100);

    $parseAgent = function (?string $ua) {
        if (! $ua) return ['browser' => '—', 'os' => '—'];
        $browser = str_contains($ua, 'Edg/') ? 'Edge' : (str_contains($ua, 'Chrome/') ? 'Chrome' : (str_contains($ua, 'Firefox/') ? 'Firefox' : ((str_contains($ua, 'Safari/') && ! str_contains($ua, 'Chrome')) ? 'Safari' : 'Other')));
        $os = str_contains($ua, 'Windows') ? 'Windows' : (str_contains($ua, 'Mac OS') ? 'macOS' : (str_contains($ua, 'Android') ? 'Android' : ((str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) ? 'iOS' : (str_contains($ua, 'Linux') ? 'Linux' : 'Other'))));
        return ['browser' => $browser, 'os' => $os];
    };
    $currentSession = $sessions->first();
    $currentDevice = $parseAgent($currentSession->user_agent ?? null);
    $unreadCount = $notifications->whereNull('read_at')->count();
    $openTickets = $disputes->whereIn('status', ['open', 'in_progress'])->count();
    $pendingKyc = $kycVerifications->firstWhere('status', 'pending');
    $pendingAgent = $user->agent && $user->agent->status === 'pending' ? $user->agent : null;
@endphp

<div x-data="{ tab: 'overview', q: '', notifyOpen: false }" @keydown.window="if(!$event.ctrlKey && !$event.metaKey && document.activeElement.tagName!=='INPUT' && document.activeElement.tagName!=='TEXTAREA'){ if($event.key==='1')tab='overview'; if($event.key==='2')tab='wallet'; if($event.key==='3')tab='compliance'; if($event.key==='4')tab='marketplace'; if($event.key==='5')tab='security'; }" class="mx-auto max-w-[1600px] space-y-4">

    <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-600 hover:text-brand-700">← All users</a>

    {{-- ============ STICKY HEADER + QUICK ACTIONS + TABS ============ --}}
    {{-- lg-only: on mobile the identity row, pills and tabs wrap across many
         lines, and a pinned card that tall would block the whole viewport. --}}
    <div class="card-solid lg:sticky lg:top-16 z-20 space-y-4 rounded-3xl border border-app p-5 shadow-lg">
        {{-- Identity row --}}
        <div class="flex flex-wrap items-start gap-4">
            <div class="relative shrink-0">
                @if ($user->avatar_path)
                    <img src="{{ Storage::url($user->avatar_path) }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-2xl object-cover">
                @elseif ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-2xl object-cover">
                @else
                    <span class="grid h-16 w-16 place-items-center rounded-2xl bg-brand-600 text-xl font-bold text-white">{{ $user->initials() }}</span>
                @endif
                <span class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full ring-2 ring-white {{ $user->isOnline() ? 'bg-emerald-500' : 'bg-slate-400' }}" title="{{ $user->isOnline() ? 'Online now' : 'Offline' }}"></span>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-strong">{{ $user->name }}</h2>
                    @if ((int) $user->kyc_level >= 2)<x-verified-tick class="h-5 w-5 shrink-0" />@endif
                    @if ($user->country)<x-flag :iso="$user->country->iso2" class="h-4 w-6" />@endif
                    <span class="text-sm text-muted">{{ $user->country->name ?? '—' }}</span>
                </div>
                <p class="text-sm text-muted">{{ $user->email }} · {{ $user->phone ?: '—' }}</p>
                <div class="mt-2 flex flex-wrap gap-1.5 text-xs">
                    <span class="pill surface text-body ring-1 ring-white/10">Member since {{ $user->created_at->format('M Y') }}</span>
                    <span class="pill surface text-body ring-1 ring-white/10">KYC L{{ $user->kyc_level }}</span>
                    <span class="pill bg-{{ $riskColor }}-500/15 text-{{ $riskColor }}-600 ring-1 ring-{{ $riskColor }}-400/30">Risk: {{ $riskLevel }}</span>
                    <span class="pill bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30">{{ $tier }} tier</span>
                    <button type="button" class="pill surface text-body ring-1 ring-white/10" title="Copy referral code" @click="navigator.clipboard.writeText('{{ $user->referral_code }}')">Ref: {{ $user->referral_code }} <x-icon name="copy" class="ml-1 inline h-3 w-3" /></button>
                    <button type="button" class="pill surface text-body ring-1 ring-white/10" title="Copy user ID" @click="navigator.clipboard.writeText('{{ $user->id }}')">User #{{ $user->id }} <x-icon name="copy" class="ml-1 inline h-3 w-3" /></button>
                    @if ($primaryWallet)<button type="button" class="pill surface text-body ring-1 ring-white/10" title="Copy wallet ID" @click="navigator.clipboard.writeText('{{ $primaryWallet->id }}')">Wallet #{{ $primaryWallet->id }} <x-icon name="copy" class="ml-1 inline h-3 w-3" /></button>@endif
                </div>
            </div>
        </div>

        {{-- Quick stat strip --}}
        <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(7.5rem,1fr)] gap-3 overflow-x-auto pb-1 text-xs">
            <div><p class="text-faint">Balance</p><p class="font-bold text-strong">{{ money($available, $currency) }}</p></div>
            <div><p class="text-faint">Lifetime deposits</p><p class="font-semibold text-body">{{ money($stats['lifetime_deposits'], $currency) }}</p></div>
            <div><p class="text-faint">Funding sent</p><p class="font-semibold text-body">{{ money($stats['lifetime_funding_sent'], 'CNY') }}</p></div>
            <div><p class="text-faint">Spending</p><p class="font-semibold text-body">{{ money($stats['lifetime_spending'], $currency) }}</p></div>
            <div><p class="text-faint">Fees paid</p><p class="font-semibold text-body">{{ money($stats['fees_paid'], $currency) }}</p></div>
            <div><p class="text-faint">Pending txns</p><p class="font-semibold text-body">{{ $stats['pending_count'] }}</p></div>
            <div><p class="text-faint">Disputes</p><p class="font-semibold text-body">{{ $disputes->count() }}</p></div>
            <div><p class="text-faint">Risk flags</p><p class="font-semibold text-body">{{ $riskCount }} open</p></div>
            @if ($user->agent)<div><p class="text-faint">Agent rating</p><p class="font-semibold text-body">★ {{ number_format($user->agent->rating, 1) }}</p></div>@endif
            <div><p class="text-faint">Last login</p><p class="font-semibold text-body">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</p></div>
            <div><p class="text-faint">Device</p><p class="font-semibold text-body">{{ $currentDevice['browser'] }} / {{ $currentDevice['os'] }}</p></div>
            <div><p class="text-faint">Current IP</p><p class="font-semibold text-body">{{ $user->last_login_ip ?? '—' }}</p></div>
            <div><p class="text-faint">Language</p><p class="font-semibold text-body">{{ strtoupper($user->locale ?: 'en') }}</p></div>
        </div>

        {{-- Quick action bar --}}
        <div class="no-scrollbar flex items-center gap-2 overflow-x-auto border-t border-app pt-3">
            <button type="button" class="qa-btn" @click="tab='overview'; $nextTick(() => document.getElementById('edit-profile')?.scrollIntoView({behavior:'smooth'}))"><x-icon name="user" class="h-3.5 w-3.5" /> Edit user</button>
            <button type="button" class="qa-btn" @click="tab='wallet'"><x-icon name="plus" class="h-3.5 w-3.5" /> Credit / Debit</button>

            <form method="POST" action="{{ route('admin.users.wallet.freeze', $user) }}" onsubmit="return confirm('{{ ($primaryWallet?->status === 'frozen') ? 'Unfreeze' : 'Freeze' }} this wallet?')" class="inline">@csrf<button class="qa-btn qa-btn-warn"><x-icon name="lock" class="h-3.5 w-3.5" /> {{ ($primaryWallet?->status === 'frozen') ? 'Unfreeze wallet' : 'Freeze wallet' }}</button></form>

            @if ($user->status !== 'active')
                <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('Activate this user?')" class="inline">@csrf<input type="hidden" name="status" value="active"><button class="qa-btn qa-btn-good"><x-icon name="check" class="h-3.5 w-3.5" /> Activate</button></form>
            @endif
            @if ($user->status !== 'suspended')
                <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('Suspend this user?')" class="inline">@csrf<input type="hidden" name="status" value="suspended"><button class="qa-btn qa-btn-warn"><x-icon name="alert" class="h-3.5 w-3.5" /> Suspend</button></form>
            @endif
            @if ($user->status !== 'blocked')
                <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('Ban this user? This blocks all account access.')" class="inline">@csrf<input type="hidden" name="status" value="blocked"><button class="qa-btn qa-btn-danger"><x-icon name="ban" class="h-3.5 w-3.5" /> Ban</button></form>
            @endif

            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" onsubmit="return confirm('Send a password reset link to {{ $user->email }}?')" class="inline">@csrf<button class="qa-btn"><x-icon name="refresh" class="h-3.5 w-3.5" /> Reset password</button></form>
            <form method="POST" action="{{ route('admin.users.reset-2fa', $user) }}" onsubmit="return confirm('Reset 2FA for this user?')" class="inline">@csrf<button class="qa-btn"><x-icon name="shield" class="h-3.5 w-3.5" /> Reset 2FA</button></form>
            <form method="POST" action="{{ route('admin.users.impersonate', $user) }}" onsubmit="return confirm('Log in as {{ $user->name }}? You can return to your admin account anytime.')" class="inline">@csrf<button class="qa-btn"><x-icon name="user-circle" class="h-3.5 w-3.5" /> Login as user</button></form>

            <button type="button" class="qa-btn" @click="notifyOpen = true"><x-icon name="bell" class="h-3.5 w-3.5" /> Notify</button>
            <a href="{{ route('admin.users.activity.export', $user) }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export CSV</a>

            @if ($pendingKyc)<a href="{{ route('admin.kyc.show', $pendingKyc) }}" class="qa-btn qa-btn-good"><x-icon name="shield" class="h-3.5 w-3.5" /> Review KYC</a>@endif
            @if ($pendingAgent)<a href="{{ route('admin.agents.show', $pendingAgent) }}" class="qa-btn qa-btn-good"><x-icon name="truck" class="h-3.5 w-3.5" /> Review agent</a>@endif
        </div>

        {{-- Tabs + in-page search --}}
        <div class="flex flex-wrap items-center gap-3 border-t border-app pt-3">
            <div class="no-scrollbar flex flex-1 items-center gap-1.5 overflow-x-auto">
                <button type="button" class="mu-tab" :class="tab==='overview' ? 'mu-tab-active' : ''" @click="tab='overview'">Overview</button>
                <button type="button" class="mu-tab" :class="tab==='wallet' ? 'mu-tab-active' : ''" @click="tab='wallet'">Wallet & Finance</button>
                <button type="button" class="mu-tab" :class="tab==='compliance' ? 'mu-tab-active' : ''" @click="tab='compliance'">Verification & Compliance</button>
                <button type="button" class="mu-tab" :class="tab==='marketplace' ? 'mu-tab-active' : ''" @click="tab='marketplace'">Marketplace</button>
                <button type="button" class="mu-tab" :class="tab==='security' ? 'mu-tab-active' : ''" @click="tab='security'">Security & Activity</button>
            </div>
            <div class="relative w-full max-w-[14rem] sm:w-56">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-faint" />
                <input x-model="q" placeholder="Search this tab…" class="field !py-1.5 pl-8 text-xs">
            </div>
        </div>
    </div>

    {{-- ============ SEND NOTIFICATION MODAL ============ --}}
    <div x-show="notifyOpen" x-cloak @click.self="notifyOpen=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        <div class="glass-strong w-full max-w-md rounded-2xl p-6" @click.outside="notifyOpen=false">
            <h3 class="font-semibold text-strong">Send notification to {{ $user->name }}</h3>
            <form method="POST" action="{{ route('admin.users.notify', $user) }}" class="mt-4 space-y-3">
                @csrf
                <div><label class="label">Subject</label><input name="subject" required class="field"></div>
                <div><label class="label">Message</label><textarea name="body" rows="4" required class="field"></textarea></div>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="send_mail" value="1" class="rounded"> Also send by email</label>
                <div class="flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="notifyOpen=false">Cancel</button><button class="btn btn-primary flex-1">Send</button></div>
            </form>
        </div>
    </div>

    {{-- ============ BODY: MAIN (tabs) + STICKY SIDEBAR ============ --}}
    <div class="grid gap-6 lg:grid-cols-4">
        <div class="min-w-0 space-y-6 lg:col-span-3">

            {{-- ===================== OVERVIEW TAB ===================== --}}
            <div x-show="tab==='overview'" x-cloak class="space-y-6">
                <x-glass-card solid id="edit-profile">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-strong">Profile & personal details</h3>
                        <span class="text-xs text-faint">{{ $completion }}% complete</span>
                    </div>
                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full surface-2"><div class="h-full rounded-full bg-brand-600" style="width: {{ $completion }}%"></div></div>

                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-5 space-y-5">
                        @csrf @method('PUT')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label class="label">Full name</label><input name="name" value="{{ old('name', $user->name) }}" required class="field"></div>
                            <div><label class="label">Email</label><input name="email" type="email" value="{{ old('email', $user->email) }}" required class="field"></div>
                            <div><label class="label">Phone</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="field"></div>
                            <div><label class="label">Phone country code</label><input name="phone_country" value="{{ old('phone_country', $user->phone_country) }}" placeholder="e.g. CM" class="field"></div>
                            <div>
                                <label class="label">Country / Nationality</label>
                                <select name="country_id" class="field">
                                    <option value="">—</option>
                                    @foreach ($countries as $c)<option value="{{ $c->id }}" @selected(old('country_id', $user->country_id) == $c->id)>{{ $c->name }}</option>@endforeach
                                </select>
                            </div>
                            <div><label class="label">City</label><input name="city" value="{{ old('city', $user->city) }}" class="field"></div>
                            <div class="sm:col-span-2"><label class="label">Address</label><input name="address" value="{{ old('address', $user->address) }}" class="field"></div>
                            <div><label class="label">Date of birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}" class="field"></div>
                            <div>
                                <label class="label">Gender</label>
                                <select name="gender" class="field">
                                    <option value="">—</option>
                                    @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $lbl)<option value="{{ $val }}" @selected(old('gender', $user->gender) === $val)>{{ $lbl }}</option>@endforeach
                                </select>
                            </div>
                            <div><label class="label">Loyalty points</label><input type="number" min="0" name="points" value="{{ old('points', $user->points) }}" class="field"></div>
                            <div><label class="label">Language</label><input value="{{ strtoupper($user->locale ?: 'en') }}" disabled class="field opacity-60"></div>
                        </div>

                        <div class="border-t border-app pt-4">
                            <p class="label mb-2">Verification & security</p>
                            <div class="flex flex-wrap items-center gap-4">
                                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="email_verified" value="1" @checked(old('email_verified', (bool) $user->email_verified_at)) class="rounded"> Email verified</label>
                                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="phone_verified" value="1" @checked(old('phone_verified', (bool) $user->phone_verified_at)) class="rounded"> Phone verified</label>
                                <span class="flex items-center gap-1.5 text-sm text-muted">
                                    2FA:
                                    @if ($user->hasMfaEnabled())
                                        <span class="pill bg-emerald-500/15 text-[10px] font-bold uppercase text-emerald-600 ring-1 ring-emerald-400/30">On</span>
                                    @else
                                        <span class="pill bg-slate-400/15 text-[10px] font-bold uppercase text-slate-500 ring-1 ring-slate-400/30">Off</span>
                                    @endif
                                    <span class="text-xs text-faint">(user-managed only, use "Reset 2FA" above to turn off)</span>
                                </span>
                            </div>
                        </div>

                        <div class="border-t border-app pt-4">
                            <p class="label mb-2">Access & risk controls</p>
                            <div class="grid gap-4 sm:grid-cols-4">
                                <div><label class="label">Role</label><select name="role" class="field">@foreach (['user','agent','admin','super_admin'] as $r)<option value="{{ $r }}" @selected($user->role->value===$r)>{{ ucfirst(str_replace('_',' ',$r)) }}</option>@endforeach</select></div>
                                <div><label class="label">Status</label><select name="status" class="field">@foreach (['active','suspended','blocked'] as $s)<option value="{{ $s }}" @selected($user->status===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
                                <div><label class="label">KYC level</label><select name="kyc_level" class="field">@for($i=0;$i<=3;$i++)<option value="{{ $i }}" @selected($user->kyc_level===$i)>Level {{ $i }}</option>@endfor</select></div>
                                <div><label class="label">Reason (optional)</label><input name="status_reason" value="{{ old('status_reason', $user->status_reason) }}" class="field"></div>
                            </div>
                        </div>

                        <button class="btn btn-primary">Save all changes</button>
                    </form>
                </x-glass-card>

                <x-glass-card solid>
                    <h3 class="font-semibold text-strong">Internal admin notes</h3>
                    <p class="text-xs text-muted">Private — never visible to the customer.</p>
                    <form method="POST" action="{{ route('admin.users.notes', $user) }}" class="mt-3 space-y-2">
                        @csrf
                        <textarea name="admin_notes" rows="4" class="field" placeholder="Internal notes about this customer…">{{ old('admin_notes', $user->admin_notes) }}</textarea>
                        <button class="btn btn-ghost text-sm">Save notes</button>
                    </form>
                </x-glass-card>

                <div>
                    <h3 class="mb-3 font-semibold text-strong">Timeline</h3>
                    <div class="mu-timeline space-y-4">
                        @forelse ($timeline as $ev)
                            <div class="mu-timeline-item" x-show="!q || '{{ strtolower($ev['title'].' '.$ev['subtitle']) }}'.includes(q.toLowerCase())">
                                <span class="mu-timeline-dot" style="background: {{ $ev['color'] }}"><x-icon :name="$ev['icon']" class="h-3 w-3" /></span>
                                <a href="{{ $ev['url'] }}" class="block rounded-xl px-3 py-2 hover:surface">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-medium text-strong">{{ $ev['title'] }}</p>
                                        <span class="shrink-0 text-xs text-faint">{{ $ev['at']->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs text-muted">{{ $ev['subtitle'] }}</p>
                                </a>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-faint">No activity recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ===================== WALLET & FINANCE TAB ===================== --}}
            <div x-show="tab==='wallet'" x-cloak class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-glass-card solid><p class="text-xs text-muted">Available balance</p><p class="mt-1 text-xl font-bold text-strong">{{ money($available, $currency) }}</p></x-glass-card>
                    <x-glass-card solid><p class="text-xs text-muted">Locked balance</p><p class="mt-1 text-xl font-bold text-strong">{{ money($primaryWallet->locked_balance ?? 0, $currency) }}</p></x-glass-card>
                    <x-glass-card solid><p class="text-xs text-muted">Wallet status</p><p class="mt-1 text-xl font-bold {{ ($primaryWallet?->status === 'frozen') ? 'text-rose-600' : 'text-emerald-600' }}">{{ ucfirst($primaryWallet->status ?? 'active') }}</p></x-glass-card>
                </div>

                <x-glass-card solid>
                    <h3 class="font-semibold text-strong">Adjust wallet</h3>
                    <form method="POST" action="{{ route('admin.users.wallet', $user) }}" class="mt-4 grid gap-3 sm:grid-cols-4">
                        @csrf
                        <select name="type" class="field"><option value="credit">Credit</option><option value="debit">Debit</option></select>
                        <input name="amount" type="number" step="0.01" class="field" placeholder="Amount" required>
                        <input name="reason" class="field sm:col-span-2" placeholder="Reason" required>
                        <button class="btn btn-primary sm:col-span-4">Adjust wallet</button>
                    </form>
                </x-glass-card>

                <div>
                    <h3 class="mb-3 font-semibold text-strong">China wallets (Alipay / WeChat / UnionPay)</h3>
                    <div class="divide-y divide-app">
                        @forelse ($user->beneficiaryAccounts as $b)
                            <div class="flex items-center justify-between py-3" x-show="!q || '{{ strtolower($b->account_name.' '.$b->account_id) }}'.includes(q.toLowerCase())">
                                <div><p class="text-sm text-body">{{ $b->account_name }} · {{ $b->app_type->label() }}</p><p class="text-xs text-faint">{{ $b->account_id }}</p></div>
                                <x-status-badge :status="$b->status" />
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-faint">None linked.</p>
                        @endforelse
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-3 font-semibold text-strong">Deposits</h3>
                        <div class="divide-y divide-app">
                            @forelse ($deposits as $d)
                                <a href="{{ route('admin.deposits.show', $d) }}" class="flex items-center justify-between py-3 hover:surface" x-show="!q || '{{ strtolower($d->reference) }}'.includes(q.toLowerCase())">
                                    <div><span class="text-sm text-body">{{ money($d->net_amount, $d->currency) }}</span><span class="ml-2 text-xs text-faint">{{ $d->created_at->diffForHumans() }}</span></div>
                                    <x-status-badge :status="$d->status" />
                                </a>
                            @empty
                                <p class="py-6 text-center text-sm text-faint">No deposits yet.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-3 font-semibold text-strong">Funding requests</h3>
                        <div class="divide-y divide-app">
                            @forelse ($funding as $f)
                                <a href="{{ route('admin.funding.show', $f) }}" class="flex items-center justify-between py-3 hover:surface" x-show="!q || '{{ strtolower($f->reference) }}'.includes(q.toLowerCase())">
                                    <div><span class="text-sm text-body">{{ money($f->target_amount, $f->target_currency) }}</span><span class="ml-2 text-xs text-faint">{{ $f->created_at->diffForHumans() }}</span></div>
                                    <x-status-badge :status="$f->status" />
                                </a>
                            @empty
                                <p class="py-6 text-center text-sm text-faint">No funding requests yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="mb-3 font-semibold text-strong">Wallet ledger (all transactions)</h3>
                    <div class="max-h-[32rem] divide-y divide-app overflow-y-auto">
                        @forelse ($transactions as $t)
                            <div class="flex items-center justify-between py-3" x-show="!q || '{{ strtolower($t->category.' '.$t->description) }}'.includes(q.toLowerCase())">
                                <div>
                                    <p class="text-sm text-body">{{ ucfirst($t->category) }} <span class="text-xs text-faint">· {{ $t->created_at->diffForHumans() }}</span></p>
                                    <p class="text-xs text-faint">{{ $t->description }}</p>
                                </div>
                                <span class="text-sm font-semibold {{ $t->type === 'credit' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $t->type === 'credit' ? '+' : '-' }}{{ money($t->amount, $t->currency) }}</span>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-faint">No wallet activity yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ===================== VERIFICATION & COMPLIANCE TAB ===================== --}}
            <div x-show="tab==='compliance'" x-cloak class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-glass-card solid><p class="text-xs text-muted">KYC status</p><div class="mt-1"><x-status-badge :status="$user->kyc_status" /></div></x-glass-card>
                    <x-glass-card solid><p class="text-xs text-muted">KYC level</p><p class="mt-1 text-xl font-bold text-strong">Level {{ $user->kyc_level }}</p></x-glass-card>
                    <x-glass-card solid><p class="text-xs text-muted">Risk level</p><p class="mt-1 text-xl font-bold text-{{ $riskColor }}-600">{{ $riskLevel }} ({{ $riskCount }} open)</p></x-glass-card>
                </div>

                <div>
                    <h3 class="mb-3 font-semibold text-strong">KYC submissions</h3>
                    <div class="divide-y divide-app">
                        @forelse ($kycVerifications as $k)
                            <a href="{{ route('admin.kyc.show', $k) }}" class="flex items-center justify-between py-3 hover:surface" x-show="!q || '{{ strtolower($k->document_type ?? '') }}'.includes(q.toLowerCase())">
                                <div>
                                    <p class="text-sm text-body">{{ ucfirst(str_replace('_', ' ', $k->document_type ?? 'Document')) }} · target L{{ $k->target_level }}</p>
                                    <p class="text-xs text-faint">Submitted {{ $k->created_at->diffForHumans() }} @if($k->reviewed_at) · reviewed {{ $k->reviewed_at->diffForHumans() }} @endif @if($k->rejection_reason) · {{ $k->rejection_reason }} @endif</p>
                                </div>
                                <x-status-badge :status="$k->status" />
                            </a>
                        @empty
                            <p class="py-6 text-center text-sm text-faint">No submissions yet.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h3 class="mb-3 font-semibold text-strong">Risk flags</h3>
                    <div class="divide-y divide-app">
                        @forelse ($flags as $f)
                            <div class="flex items-center justify-between py-3" x-show="!q || '{{ strtolower($f->rule_code.' '.$f->reason) }}'.includes(q.toLowerCase())">
                                <div><p class="text-sm text-strong">{{ $f->rule_code }} <span class="pill ml-1 bg-{{ $f->severity === 'high' ? 'rose' : ($f->severity === 'medium' ? 'amber' : 'sky') }}-500/15 text-{{ $f->severity === 'high' ? 'rose' : ($f->severity === 'medium' ? 'amber' : 'sky') }}-600 text-[10px]">{{ ucfirst($f->severity) }}</span></p><p class="text-xs text-faint">{{ $f->reason }} · {{ $f->created_at->diffForHumans() }}</p></div>
                                <x-status-badge :status="$f->status" />
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-faint">No flags.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ===================== MARKETPLACE TAB ===================== --}}
            <div x-show="tab==='marketplace'" x-cloak class="space-y-6">
                <div>
                    <h3 class="mb-3 font-semibold text-strong">Shop orders</h3>
                    <div class="divide-y divide-app">
                        @forelse ($orders as $o)
                            <a href="{{ route('admin.shop.orders.show', $o) }}" class="flex items-center justify-between py-3 hover:surface" x-show="!q || '{{ strtolower($o->reference) }}'.includes(q.toLowerCase())">
                                <div><span class="text-sm text-body">{{ money($o->total, $o->currency) }}</span><span class="ml-2 text-xs text-faint">{{ $o->created_at->diffForHumans() }}</span></div>
                                <x-status-badge :status="$o->status" />
                            </a>
                        @empty
                            <p class="py-6 text-center text-sm text-faint">No shop orders yet.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h3 class="mb-3 font-semibold text-strong">Reviews written</h3>
                    <div class="divide-y divide-app">
                        @forelse ($reviews as $r)
                            <div class="py-3"><p class="text-sm text-strong">★ {{ $r->rating }}/5</p><p class="text-xs text-faint">{{ \Illuminate\Support\Str::limit($r->comment, 100) }} · {{ $r->created_at->diffForHumans() }}</p></div>
                        @empty
                            <p class="py-6 text-center text-sm text-faint">No reviews written.</p>
                        @endforelse
                    </div>
                </div>

                @if ($user->agent)
                    <x-glass-card solid>
                        <h3 class="font-semibold text-strong">Agent profile</h3>
                        <p class="mt-1 text-sm text-body">{{ $user->agent->business_name }}</p>
                        <p class="text-xs text-faint">{{ $user->agent->warehouse_city }} · ★ {{ number_format($user->agent->rating, 1) }} ({{ $user->agent->reviews_count }} reviews)</p>
                        <div class="mt-2"><x-status-badge :status="$user->agent->status" /></div>
                        <a href="{{ route('admin.agents.show', $user->agent) }}" class="mt-3 inline-block text-sm text-brand-600 hover:text-brand-700">Manage agent profile →</a>
                    </x-glass-card>
                @endif

                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-3 font-semibold text-strong">Referrals ({{ $referrals->count() }})</h3>
                        <div class="divide-y divide-app">
                            @forelse ($referrals as $r)
                                <a href="{{ route('admin.users.show', $r) }}" class="flex items-center justify-between py-3 hover:surface">
                                    <div><p class="text-sm text-body">{{ $r->name }}</p><p class="text-xs text-faint">{{ $r->email }} · joined {{ $r->created_at->diffForHumans() }}</p></div>
                                    <x-status-badge :status="$r->kyc_status" />
                                </a>
                            @empty
                                <p class="py-6 text-center text-sm text-faint">No referrals yet.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-3 font-semibold text-strong">Support tickets</h3>
                        <div class="divide-y divide-app">
                            @forelse ($disputes as $d)
                                <a href="{{ route('admin.disputes.show', $d) }}" class="block py-3 hover:surface">
                                    <div class="flex items-center justify-between"><p class="truncate text-sm text-strong">{{ $d->subject }}</p><x-status-badge :status="$d->status" /></div>
                                    <p class="text-xs text-faint">{{ ucfirst($d->category) }} · {{ $d->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <p class="py-6 text-center text-sm text-faint">No tickets.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===================== SECURITY & ACTIVITY TAB ===================== --}}
            <div x-show="tab==='security'" x-cloak class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-4">
                    <x-glass-card solid><p class="text-xs text-muted">2FA</p><p class="mt-1 text-lg font-bold {{ $user->hasMfaEnabled() ? 'text-emerald-600' : 'text-rose-600' }}">{{ $user->hasMfaEnabled() ? 'Enabled' : 'Disabled' }}</p></x-glass-card>
                    <x-glass-card solid><p class="text-xs text-muted">Last login</p><p class="mt-1 text-sm font-semibold text-strong">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</p><p class="text-xs text-faint">{{ $user->last_login_ip ?? '—' }}</p></x-glass-card>
                    <x-glass-card solid><p class="text-xs text-muted">Last seen</p><p class="mt-1 text-sm font-semibold text-strong">{{ $user->last_seen_at?->diffForHumans() ?? 'Never' }}</p></x-glass-card>
                    <x-glass-card solid><p class="text-xs text-muted">Unread notifications</p><p class="mt-1 text-lg font-bold text-strong">{{ $unreadCount }}</p></x-glass-card>
                </div>

                <div>
                    <h3 class="mb-3 font-semibold text-strong">Active sessions</h3>
                    <div class="divide-y divide-app">
                        @forelse ($sessions as $s)
                            @php $dev = $parseAgent($s->user_agent); @endphp
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-sm text-body">{{ $dev['browser'] }} on {{ $dev['os'] }}</p>
                                    <p class="text-xs text-faint">{{ $s->ip_address }} · active {{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.users.sessions.revoke', [$user, $s->id]) }}" onsubmit="return confirm('Revoke this session? The user will be logged out.')">
                                    @csrf @method('DELETE')
                                    <button class="qa-btn qa-btn-danger">Revoke</button>
                                </form>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-faint">No active sessions.</p>
                        @endforelse
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-3 font-semibold text-strong">User activity (their own actions)</h3>
                        <div class="max-h-[26rem] divide-y divide-app overflow-y-auto">
                            @forelse ($activity as $a)
                                <div class="py-3" x-show="!q || '{{ strtolower($a->action.' '.$a->description) }}'.includes(q.toLowerCase())">
                                    <p class="text-sm text-body">{{ $a->description ?? $a->action }}</p>
                                    <p class="text-xs text-faint">{{ $a->action }} @if($a->ip) · {{ $a->ip }} @endif · {{ $a->created_at->diffForHumans() }}</p>
                                </div>
                            @empty
                                <p class="py-6 text-center text-sm text-faint">No recorded activity yet.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-3 font-semibold text-strong">Admin log (actions taken on this account)</h3>
                        <div class="max-h-[26rem] divide-y divide-app overflow-y-auto">
                            @forelse ($adminLog as $a)
                                @php $actor = \App\Models\User::find($a->user_id); @endphp
                                <div class="py-3">
                                    <p class="text-sm text-body">{{ $a->description ?? $a->action }}</p>
                                    <p class="text-xs text-faint">by {{ $actor->name ?? 'System' }} · {{ $a->ip }} · {{ $a->created_at->diffForHumans() }}</p>
                                </div>
                            @empty
                                <p class="py-6 text-center text-sm text-faint">No admin actions recorded yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="mb-3 font-semibold text-strong">Notification history</h3>
                    <div class="max-h-[20rem] divide-y divide-app overflow-y-auto">
                        @forelse ($notifications as $n)
                            <div class="flex items-center justify-between py-3">
                                <div><p class="text-sm text-body">{{ $n->data['title'] ?? 'Notification' }}</p><p class="text-xs text-faint">{{ \Illuminate\Support\Str::limit($n->data['message'] ?? '', 80) }} · {{ $n->created_at->diffForHumans() }}</p></div>
                                @if(is_null($n->read_at))<span class="pill bg-brand-500/15 text-brand-600 text-[10px]">Unread</span>@endif
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-faint">No notifications sent yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ STICKY SIDEBAR ============ --}}
        <div class="space-y-4 lg:sticky lg:top-[22rem] lg:col-span-1 lg:self-start">
            <x-glass-card solid>
                <p class="text-xs text-muted">Current balance</p>
                <p class="mt-1 text-2xl font-bold text-strong">{{ money($available, $currency) }}</p>
                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-xl surface-2 p-2"><p class="text-faint">Risk</p><p class="font-semibold text-{{ $riskColor }}-600">{{ $riskLevel }}</p></div>
                    <div class="rounded-xl surface-2 p-2"><p class="text-faint">Status</p><p class="font-semibold text-strong">{{ ucfirst($user->status) }}</p></div>
                    <div class="rounded-xl surface-2 p-2"><p class="text-faint">KYC</p><p class="font-semibold text-strong">{{ $user->kyc_status->label() }}</p></div>
                    <div class="rounded-xl surface-2 p-2"><p class="text-faint">Wallet</p><p class="font-semibold text-strong">{{ ucfirst($primaryWallet->status ?? 'active') }}</p></div>
                </div>
            </x-glass-card>

            <x-glass-card solid>
                <h4 class="text-sm font-semibold text-strong">Quick actions</h4>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <button type="button" class="qa-btn" @click="tab='wallet'"><x-icon name="wallet" class="h-3.5 w-3.5" /> Wallet</button>
                    <button type="button" class="qa-btn" @click="notifyOpen=true"><x-icon name="bell" class="h-3.5 w-3.5" /> Notify</button>
                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" onsubmit="return confirm('Send a password reset link?')">@csrf<button class="qa-btn"><x-icon name="refresh" class="h-3.5 w-3.5" /> Reset pw</button></form>
                </div>
            </x-glass-card>

            <x-glass-card solid>
                <h4 class="text-sm font-semibold text-strong">Recent activity</h4>
                <div class="mt-2 space-y-2">
                    @forelse ($timeline->take(5) as $ev)
                        <a href="{{ $ev['url'] }}" class="flex items-center gap-2 rounded-lg px-1 py-1 hover:surface">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-white" style="background: {{ $ev['color'] }}"><x-icon :name="$ev['icon']" class="h-3 w-3" /></span>
                            <span class="min-w-0 flex-1"><span class="block truncate text-xs font-medium text-body">{{ $ev['title'] }}</span><span class="block text-[10px] text-faint">{{ $ev['at']->diffForHumans() }}</span></span>
                        </a>
                    @empty
                        <p class="py-3 text-center text-xs text-faint">No activity yet.</p>
                    @endforelse
                </div>
            </x-glass-card>

            <x-glass-card solid>
                <h4 class="text-sm font-semibold text-strong">Recent transactions</h4>
                <div class="mt-2 space-y-2">
                    @forelse ($transactions->take(5) as $t)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-body">{{ ucfirst($t->category) }}</span>
                            <span class="font-semibold {{ $t->type === 'credit' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $t->type === 'credit' ? '+' : '-' }}{{ money($t->amount, $t->currency) }}</span>
                        </div>
                    @empty
                        <p class="py-3 text-center text-xs text-faint">No transactions yet.</p>
                    @endforelse
                </div>
            </x-glass-card>

            <x-glass-card solid>
                <div class="flex items-center justify-between text-sm"><span class="text-muted">Open tickets</span><span class="font-bold text-strong">{{ $openTickets }}</span></div>
                <div class="mt-2 flex items-center justify-between text-sm"><span class="text-muted">Unread notifications</span><span class="font-bold text-strong">{{ $unreadCount }}</span></div>
            </x-glass-card>
        </div>
    </div>
</div>
@endsection
