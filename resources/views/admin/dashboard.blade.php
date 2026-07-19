@extends('layouts.admin')
@section('page-title', 'Overview')

@section('content')
<div class="space-y-6">
    {{-- KPI cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
        <x-stat label="Users" :value="$cards['users']" :counter="true" icon="users" />
        <x-stat label="Deposited" :value="$cards['deposited']" suffix=" {{ config('platform.base_currency') }}" :counter="true" icon="deposit" />
        <x-stat label="Funded (CNY)" :value="$cards['funded']" :counter="true" icon="fund" />
        <x-stat label="Shop sales" :value="$cards['shop']" suffix=" {{ config('platform.base_currency') }}" :counter="true" icon="bag" />
        <x-stat label="Wallet liabilities" :value="$cards['liabilities']" suffix=" {{ config('platform.base_currency') }}" :counter="true" icon="wallet" />
        <x-stat label="Fee revenue" :value="$cards['revenue']" suffix=" {{ config('platform.base_currency') }}" :counter="true" icon="chart" />
    </div>

    {{-- Action queues --}}
    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['KYC', $queues['kyc'], route('admin.kyc.index'), 'shield'],
            ['Agents', $queues['agents'], route('admin.agents.index'), 'truck'],
            ['China wallets', $queues['beneficiaries'], route('admin.beneficiaries.index'), 'card'],
            ['Deposits', $queues['deposits'], route('admin.deposits.index'), 'deposit'],
            ['Funding', $queues['funding'], route('admin.funding.index'), 'fund'],
            ['Risk flags', $queues['risk'], route('admin.risk.index'), 'flag'],
        ] as [$label, $count, $url, $icon])
            <a href="{{ $url }}" class="glass glass-hover rounded-2xl p-4">
                <div class="flex items-center justify-between">
                    <x-icon :name="$icon" class="h-5 w-5 text-slate-400" />
                    @if ($count > 0)<span class="grid h-6 min-w-6 place-items-center rounded-full bg-rose-500/90 px-1.5 text-xs font-bold text-strong">{{ $count }}</span>@endif
                </div>
                <p class="mt-3 text-sm text-muted">{{ $label }}</p>
                <p class="text-xs {{ $count > 0 ? 'text-amber-300' : 'text-emerald-300' }}">{{ $count > 0 ? 'Needs attention' : 'All clear' }}</p>
            </a>
        @endforeach
    </div>

    {{-- Chart + recent --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <x-glass-card class="lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-strong">Platform monitor · 14 days</h3>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span><span class="text-muted">Deposits</span></span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-500"></span><span class="text-muted">Funding</span></span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-accent-500"></span><span class="text-muted">Shop</span></span>
                </div>
            </div>
            @php $max = max(1, $monitor->max(fn($d) => max($d['deposits'], $d['funding'], $d['shop']))); @endphp
            <div class="mt-6 flex h-48 items-end gap-1.5">
                @foreach ($monitor as $d)
                    <div class="group flex flex-1 flex-col items-center gap-1.5">
                        <div class="flex h-full w-full items-end justify-center gap-0.5">
                            <div class="w-1/3 rounded-t bg-emerald-500/80 transition-all" style="height: {{ max(2, ($d['deposits'] / $max) * 100) }}%" title="Deposits {{ money($d['deposits'], config('platform.base_currency')) }} · {{ $d['date'] }}"></div>
                            <div class="w-1/3 rounded-t bg-brand-500/80 transition-all" style="height: {{ max(2, ($d['funding'] / $max) * 100) }}%" title="Funding {{ money($d['funding'], config('platform.base_currency')) }} · {{ $d['date'] }}"></div>
                            <div class="w-1/3 rounded-t bg-accent-500/80 transition-all" style="height: {{ max(2, ($d['shop'] / $max) * 100) }}%" title="Shop {{ money($d['shop'], config('platform.base_currency')) }} · {{ $d['date'] }}"></div>
                        </div>
                        <span class="text-[10px] text-faint">{{ $d['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-glass-card>

        <x-glass-card>
            <h3 class="font-semibold text-strong">Latest webhooks</h3>
            <div class="mt-4 space-y-2">
                @forelse ($recentWebhooks as $w)
                    <a href="{{ route('admin.webhooks.show', $w) }}" class="flex items-center justify-between rounded-xl px-3 py-2 hover:bg-white/5">
                        <div><p class="text-sm text-strong">{{ $w->provider_code }}</p><p class="text-xs text-faint">{{ $w->created_at->diffForHumans() }}</p></div>
                        <x-status-badge :status="$w->status" />
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-faint">No webhooks yet.</p>
                @endforelse
            </div>
        </x-glass-card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-glass-card padding="p-0">
            <div class="flex items-center justify-between p-5"><h3 class="font-semibold text-strong">Recent deposits</h3><a href="{{ route('admin.deposits.index') }}" class="text-sm text-brand-300">All</a></div>
            <div class="divide-y divide-app">
                @foreach ($recentDeposits as $d)
                    <a href="{{ route('admin.deposits.show', $d) }}" class="flex items-center justify-between px-5 py-3 hover:bg-white/[0.02]">
                        <div><p class="text-sm text-strong">{{ $d->user->name }}</p><p class="text-xs text-faint">{{ money($d->net_amount, $d->currency) }}</p></div>
                        <x-status-badge :status="$d->status" />
                    </a>
                @endforeach
            </div>
        </x-glass-card>
        <x-glass-card padding="p-0">
            <div class="flex items-center justify-between p-5"><h3 class="font-semibold text-strong">Recent funding</h3><a href="{{ route('admin.funding.index') }}" class="text-sm text-brand-300">All</a></div>
            <div class="divide-y divide-app">
                @foreach ($recentFunding as $f)
                    <a href="{{ route('admin.funding.show', $f) }}" class="flex items-center justify-between px-5 py-3 hover:bg-white/[0.02]">
                        <div><p class="text-sm text-strong">{{ $f->user->name }}</p><p class="text-xs text-faint">{{ money($f->target_amount, $f->target_currency) }}</p></div>
                        <x-status-badge :status="$f->status" />
                    </a>
                @endforeach
            </div>
        </x-glass-card>
    </div>
</div>
@endsection
