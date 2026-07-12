@extends('layouts.admin')
@section('page-title', 'Deposit accounts')

@section('content')
<div class="space-y-8">
    {{-- MoMo --}}
    <section>
        <h3 class="mb-3 font-semibold text-strong">MTN / Orange MoMo numbers</h3>
        <div class="grid gap-4 lg:grid-cols-2">
            <x-glass-card padding="p-0">
                <div class="divide-y divide-app">
                    @forelse ($momo as $n)
                        <div class="flex items-center justify-between px-5 py-3">
                            <div><p class="text-strong">{{ strtoupper($n->provider) }} · {{ $n->number }}</p><p class="text-xs text-faint">{{ $n->account_name }}</p></div>
                            <form method="POST" action="{{ route('admin.channels.destroy', ['type'=>'momo','id'=>$n->id]) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-rose-300"><x-icon name="x" class="h-4 w-4" /></button></form>
                        </div>
                    @empty<p class="px-5 py-6 text-center text-sm text-faint">No MoMo numbers.</p>@endforelse
                </div>
            </x-glass-card>
            <x-glass-card>
                <form method="POST" action="{{ route('admin.channels.store', ['type'=>'momo']) }}" class="space-y-3">@csrf
                    <select name="provider" class="field"><option value="mtn">MTN</option><option value="orange">Orange</option></select>
                    <input name="number" class="field" placeholder="Number" required>
                    <input name="account_name" class="field" placeholder="Account name" required>
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
                        <div class="flex items-center justify-between px-5 py-3">
                            <div><p class="text-strong">{{ $c->asset }} · {{ $c->network }}</p><p class="break-all font-mono text-xs text-faint">{{ $c->address }}</p></div>
                            <form method="POST" action="{{ route('admin.channels.destroy', ['type'=>'crypto','id'=>$c->id]) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-rose-300"><x-icon name="x" class="h-4 w-4" /></button></form>
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
                        <div class="flex items-center justify-between px-5 py-3">
                            <div><p class="text-strong">{{ $b->bank_name }} · {{ $b->account_number }}</p><p class="text-xs text-faint">{{ $b->account_name }}</p></div>
                            <form method="POST" action="{{ route('admin.channels.destroy', ['type'=>'bank','id'=>$b->id]) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-rose-300"><x-icon name="x" class="h-4 w-4" /></button></form>
                        </div>
                    @empty<p class="px-5 py-6 text-center text-sm text-faint">No bank accounts.</p>@endforelse
                </div>
            </x-glass-card>
            <x-glass-card>
                <form method="POST" action="{{ route('admin.channels.store', ['type'=>'bank']) }}" class="space-y-3">@csrf
                    <input name="bank_name" class="field" placeholder="Bank name" required>
                    <input name="account_name" class="field" placeholder="Account name" required>
                    <div class="grid grid-cols-2 gap-2"><input name="account_number" class="field" placeholder="Account number" required><input name="swift" class="field" placeholder="SWIFT"></div>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded surface-2"> Active</label>
                    <button class="btn btn-primary w-full">Add bank account</button>
                </form>
            </x-glass-card>
        </div>
    </section>
</div>
@endsection
