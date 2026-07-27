@extends('layouts.admin')
@section('page-title', 'Deposit Accounts')

@php
    $summaryCards = [
        ['Total', $summary['total'], 'list', 'slate'],
        ['Active', $summary['active'], 'check-circle', 'emerald'],
        ['MoMo numbers', $summary['momo'], 'phone', 'sky'],
        ['Crypto wallets', $summary['crypto'], 'bitcoin', 'amber'],
        ['Bank accounts', $summary['bank'], 'building', 'slate'],
    ];
@endphp

@section('content')
<div x-data="depositAccountsPage()" class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-strong">Deposit Accounts</h1>
        <p class="text-sm text-muted">The MoMo numbers, crypto wallets, and bank accounts customers pay into for manual deposits. Account numbers are masked until revealed.</p>
    </div>

    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-5">
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

    {{-- MoMo --}}
    <section>
        <h3 class="mb-3 font-semibold text-strong">MTN / Orange MoMo numbers</h3>
        <div class="grid gap-4 lg:grid-cols-2">
            <x-glass-card padding="p-0">
                <div class="divide-y divide-app">
                    @forelse ($momo as $n)
                        <div class="flex items-center justify-between px-5 py-3 {{ $n->trashed() ? 'opacity-60' : '' }}">
                            <div>
                                <p class="text-strong">{{ strtoupper($n->provider) }} · <span x-text="revealed['momo-{{ $n->id }}'] ?? '{{ $n->maskedNumber() }}'"></span></p>
                                <p class="text-xs text-faint">{{ $n->account_name }} @if($n->country) · {{ $n->country->name }} @endif</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="reveal('momo', {{ $n->id }})" title="Reveal"><x-icon name="eye" class="h-4 w-4 text-faint" /></button>
                                @unless($n->trashed())
                                    <form method="POST" action="{{ route('admin.channels.active', ['type'=>'momo','id'=>$n->id]) }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="is_active" value="{{ $n->is_active ? 0 : 1 }}"><button class="text-xs {{ $n->is_active ? 'text-emerald-600' : 'text-faint' }}">{{ $n->is_active ? 'Active' : 'Inactive' }}</button></form>
                                    <form method="POST" action="{{ route('admin.channels.destroy', ['type'=>'momo','id'=>$n->id]) }}" onsubmit="return confirm('Archive this account?')">@csrf @method('DELETE')<button class="text-rose-600"><x-icon name="trash" class="h-4 w-4" /></button></form>
                                @else
                                    <form method="POST" action="{{ route('admin.channels.restore', ['type'=>'momo','id'=>$n->id]) }}">@csrf<button class="text-xs text-brand-500">Restore</button></form>
                                @endunless
                            </div>
                        </div>
                    @empty<p class="px-5 py-6 text-center text-sm text-faint">No MoMo numbers.</p>@endforelse
                </div>
            </x-glass-card>
            <x-glass-card>
                <form method="POST" action="{{ route('admin.channels.store', ['type'=>'momo']) }}" class="space-y-3">@csrf
                    <select name="provider" class="field"><option value="mtn">MTN</option><option value="orange">Orange</option></select>
                    <input name="number" class="field" placeholder="Number" required>
                    <input name="account_name" class="field" placeholder="Account name" required>
                    <select name="country_id" class="field"><option value="">Any country</option>@foreach ($countries as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" step="0.01" name="min_deposit" class="field" placeholder="Min deposit">
                        <input type="number" step="0.01" name="max_deposit" class="field" placeholder="Max deposit">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded surface-2"> Active</label>
                    <button class="btn btn-primary w-full">Add MoMo number</button>
                </form>
            </x-glass-card>
        </div>
    </section>

    {{-- Crypto --}}
    <section>
        <h3 class="mb-3 font-semibold text-strong">Crypto wallets</h3>
        <div class="grid gap-4 lg:grid-cols-2">
            <x-glass-card padding="p-0">
                <div class="divide-y divide-app">
                    @forelse ($crypto as $c)
                        <div class="flex items-center justify-between px-5 py-3 {{ $c->trashed() ? 'opacity-60' : '' }}">
                            <div>
                                <p class="text-strong">{{ $c->asset }} · {{ $c->network }}</p>
                                <p class="break-all font-mono text-xs text-faint" x-text="revealed['crypto-{{ $c->id }}'] ?? '{{ $c->maskedAddress() }}'"></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="reveal('crypto', {{ $c->id }})" title="Reveal"><x-icon name="eye" class="h-4 w-4 text-faint" /></button>
                                @unless($c->trashed())
                                    <form method="POST" action="{{ route('admin.channels.active', ['type'=>'crypto','id'=>$c->id]) }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="is_active" value="{{ $c->is_active ? 0 : 1 }}"><button class="text-xs {{ $c->is_active ? 'text-emerald-600' : 'text-faint' }}">{{ $c->is_active ? 'Active' : 'Inactive' }}</button></form>
                                    <form method="POST" action="{{ route('admin.channels.destroy', ['type'=>'crypto','id'=>$c->id]) }}" onsubmit="return confirm('Archive this account?')">@csrf @method('DELETE')<button class="text-rose-600"><x-icon name="trash" class="h-4 w-4" /></button></form>
                                @else
                                    <form method="POST" action="{{ route('admin.channels.restore', ['type'=>'crypto','id'=>$c->id]) }}">@csrf<button class="text-xs text-brand-500">Restore</button></form>
                                @endunless
                            </div>
                        </div>
                    @empty<p class="px-5 py-6 text-center text-sm text-faint">No crypto wallets.</p>@endforelse
                </div>
            </x-glass-card>
            <x-glass-card>
                <form method="POST" action="{{ route('admin.channels.store', ['type'=>'crypto']) }}" class="space-y-3">@csrf
                    <div class="grid grid-cols-2 gap-2"><input name="asset" class="field" placeholder="USDT" required><input name="network" class="field" placeholder="TRC20" required></div>
                    <input name="address" class="field" placeholder="Wallet address" required>
                    <input name="memo" class="field" placeholder="Memo (optional)">
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded surface-2"> Active</label>
                    <button class="btn btn-primary w-full">Add crypto wallet</button>
                </form>
            </x-glass-card>
        </div>
    </section>

    {{-- Bank --}}
    <section>
        <h3 class="mb-3 font-semibold text-strong">Bank accounts</h3>
        <div class="grid gap-4 lg:grid-cols-2">
            <x-glass-card padding="p-0">
                <div class="divide-y divide-app">
                    @forelse ($bank as $b)
                        <div class="flex items-center justify-between px-5 py-3 {{ $b->trashed() ? 'opacity-60' : '' }}">
                            <div>
                                <p class="text-strong">{{ $b->bank_name }} · <span x-text="revealed['bank-{{ $b->id }}'] ?? '{{ $b->maskedAccountNumber() }}'"></span></p>
                                <p class="text-xs text-faint">{{ $b->account_name }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="reveal('bank', {{ $b->id }})" title="Reveal"><x-icon name="eye" class="h-4 w-4 text-faint" /></button>
                                @unless($b->trashed())
                                    <form method="POST" action="{{ route('admin.channels.active', ['type'=>'bank','id'=>$b->id]) }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="is_active" value="{{ $b->is_active ? 0 : 1 }}"><button class="text-xs {{ $b->is_active ? 'text-emerald-600' : 'text-faint' }}">{{ $b->is_active ? 'Active' : 'Inactive' }}</button></form>
                                    <form method="POST" action="{{ route('admin.channels.destroy', ['type'=>'bank','id'=>$b->id]) }}" onsubmit="return confirm('Archive this account?')">@csrf @method('DELETE')<button class="text-rose-600"><x-icon name="trash" class="h-4 w-4" /></button></form>
                                @else
                                    <form method="POST" action="{{ route('admin.channels.restore', ['type'=>'bank','id'=>$b->id]) }}">@csrf<button class="text-xs text-brand-500">Restore</button></form>
                                @endunless
                            </div>
                        </div>
                    @empty<p class="px-5 py-6 text-center text-sm text-faint">No bank accounts.</p>@endforelse
                </div>
            </x-glass-card>
            <x-glass-card>
                <form method="POST" action="{{ route('admin.channels.store', ['type'=>'bank']) }}" class="space-y-3">@csrf
                    <input name="bank_name" class="field" placeholder="Bank name" required>
                    <input name="account_name" class="field" placeholder="Account name" required>
                    <div class="grid grid-cols-2 gap-2"><input name="account_number" class="field" placeholder="Account number" required><input name="swift" class="field" placeholder="SWIFT"></div>
                    <div class="grid grid-cols-2 gap-2"><input name="iban" class="field" placeholder="IBAN (optional)"><input name="routing_number" class="field" placeholder="Routing number (optional)"></div>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded surface-2"> Active</label>
                    <button class="btn btn-primary w-full">Add bank account</button>
                </form>
            </x-glass-card>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
function depositAccountsPage() {
    return {
        revealed: {},
        async reveal(type, id) {
            const res = await fetch(`/admin/channels/${type}/${id}/reveal`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}' },
            });
            if (res.status === 423) {
                window.location.href = '{{ route('admin.password.confirm') }}';
                return;
            }
            if (!res.ok) return;
            const data = await res.json();
            this.revealed[`${type}-${id}`] = data.value;
        },
    };
}
</script>
@endpush
