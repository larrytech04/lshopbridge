@extends('layouts.app')
@section('page-title', 'Wallet')

@section('content')
<div class="space-y-6">
    <x-page-header :title="__('Wallet')" />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-wallet-balance-carousel :wallet="$wallet" :wallets="$wallets" />
        <div class="rounded-2xl bg-emerald-600 p-5 text-white">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm opacity-90">{{ __('Total in') }}</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight">
                        <span x-data="counter({{ (float) $inflow * display_currency()['rate'] }}, 1500, {{ display_currency()['decimals'] }})" x-intersect.once="start()" x-text="display">0</span>
                    </p>
                    <p class="mt-0.5 text-xs font-medium opacity-90">{{ display_currency()['symbol'] }} . {{ display_currency()['code'] }}</p>
                </div>
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white text-emerald-600"><x-icon name="deposit" class="h-5 w-5" /></span>
            </div>
        </div>
        <div class="rounded-2xl bg-rose-600 p-5 text-white">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm opacity-90">{{ __('Total out') }}</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight">
                        <span x-data="counter({{ (float) $outflow * display_currency()['rate'] }}, 1500, {{ display_currency()['decimals'] }})" x-intersect.once="start()" x-text="display">0</span>
                    </p>
                    <p class="mt-0.5 text-xs font-medium opacity-90">{{ display_currency()['symbol'] }} . {{ display_currency()['code'] }}</p>
                </div>
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white text-rose-600"><x-icon name="fund" class="h-5 w-5" /></span>
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-app">
        <div class="flex flex-wrap items-center justify-between gap-2 p-5">
            <h3 class="font-semibold text-strong">{{ __('Transactions') }}</h3>
            <div class="flex items-center gap-4">
                <a href="{{ route('wallet.statement') }}" class="inline-flex items-center gap-1 text-sm text-brand-500 hover:text-brand-600"><x-icon name="download" class="h-3.5 w-3.5" /> {{ __('Download statement') }}</a>
                <a href="{{ route('transactions.index') }}" class="text-sm text-brand-500 hover:text-brand-600">{{ __('Full history') }}</a>
            </div>
        </div>
        @include('dashboard.partials.txn-table', ['transactions' => $transactions])
        <div class="p-4">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
