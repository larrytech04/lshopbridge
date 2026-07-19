@extends('layouts.admin')
@section('page-title', $user->name)

@section('content')
<div class="space-y-6">
    <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← All users</a>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-glass-card class="lg:col-span-2">
            <div class="flex items-center gap-4">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-brand-600 text-lg font-bold text-strong">{{ $user->initials() }}</span>
                <div>
                    <h2 class="text-xl font-bold text-strong">{{ $user->name }}</h2>
                    <p class="text-sm text-muted">{{ $user->email }} · {{ $user->phone }}</p>
                    <p class="text-xs text-faint">{{ $user->country->name ?? '-' }} · Joined {{ $user->created_at->format('M Y') }}</p>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="pill surface text-body ring-1 ring-white/10">{{ $user->role->label() }}</span>
                <x-status-badge :status="$user->status" />
                <x-status-badge :status="$user->kyc_status" />
                <span class="pill surface text-body ring-1 ring-white/10">KYC L{{ $user->kyc_level }}</span>
            </div>
        </x-glass-card>

        <x-glass-card>
            <p class="text-sm text-muted">Wallet balance</p>
            <p class="mt-1 text-2xl font-bold text-strong">{{ money(optional($user->wallets->first())->balance ?? 0, config('platform.base_currency')) }}</p>
            <form method="POST" action="{{ route('admin.users.wallet', $user) }}" class="mt-4 space-y-2">
                @csrf
                <div class="flex gap-2">
                    <select name="type" class="field max-w-[110px]"><option value="credit">Credit</option><option value="debit">Debit</option></select>
                    <input name="amount" type="number" step="0.01" class="field" placeholder="Amount" required>
                </div>
                <input name="reason" class="field" placeholder="Reason" required>
                <button class="btn btn-ghost w-full text-sm">Adjust wallet</button>
            </form>
        </x-glass-card>
    </div>

    {{-- Controls --}}
    <x-glass-card>
        <h3 class="font-semibold text-strong">Account controls</h3>
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-4 grid gap-4 sm:grid-cols-4">
            @csrf @method('PUT')
            <div><label class="label">Role</label><select name="role" class="field">@foreach (['user','agent','admin','super_admin'] as $r)<option value="{{ $r }}" @selected($user->role->value===$r)>{{ ucfirst(str_replace('_',' ',$r)) }}</option>@endforeach</select></div>
            <div><label class="label">Status</label><select name="status" class="field">@foreach (['active','suspended','blocked'] as $s)<option value="{{ $s }}" @selected($user->status===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
            <div><label class="label">KYC level</label><select name="kyc_level" class="field">@for($i=0;$i<=3;$i++)<option value="{{ $i }}" @selected($user->kyc_level===$i)>Level {{ $i }}</option>@endfor</select></div>
            <div><label class="label">Reason (optional)</label><input name="status_reason" value="{{ $user->status_reason }}" class="field"></div>
            <div class="sm:col-span-4"><button class="btn btn-primary">Save</button></div>
        </form>
    </x-glass-card>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-glass-card padding="p-0">
            <h3 class="p-5 font-semibold text-strong">Recent deposits</h3>
            <div class="divide-y divide-app">
                @forelse ($deposits as $d)<a href="{{ route('admin.deposits.show', $d) }}" class="flex items-center justify-between px-5 py-3 hover:bg-white/[0.02]"><span class="text-sm text-body">{{ money($d->net_amount, $d->currency) }}</span><x-status-badge :status="$d->status" /></a>@empty<p class="px-5 py-6 text-center text-sm text-faint">None</p>@endforelse
            </div>
        </x-glass-card>
        <x-glass-card padding="p-0">
            <h3 class="p-5 font-semibold text-strong">Risk flags</h3>
            <div class="divide-y divide-app">
                @forelse ($flags as $f)<div class="px-5 py-3"><p class="text-sm text-strong">{{ $f->rule_code }}</p><p class="text-xs text-faint">{{ $f->reason }}</p></div>@empty<p class="px-5 py-6 text-center text-sm text-faint">No flags</p>@endforelse
            </div>
        </x-glass-card>
    </div>
</div>
@endsection
