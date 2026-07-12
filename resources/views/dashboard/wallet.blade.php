@extends('layouts.app')
@section('page-title', 'Wallet')

@section('content')
<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="relative overflow-hidden rounded-3xl border border-app bg-slate-600/25 p-6 sm:col-span-1">
            <p class="text-sm text-body">{{ __('Wallet') }}</p>
            <p class="mt-2 text-3xl font-extrabold text-strong">{{ disp($wallet->balance) }}</p>
            @if ($wallet->locked_balance > 0)<p class="mt-1 text-xs text-amber-300">{{ disp($wallet->locked_balance) }} on hold</p>@endif
        </div>
        <x-stat label="Total in" :value="$inflow * display_currency()['rate']" :decimals="display_currency()['decimals']" suffix=" {{ display_currency()['code'] }}" :counter="true" icon="deposit" />
        <x-stat label="Total out" :value="$outflow * display_currency()['rate']" :decimals="display_currency()['decimals']" suffix=" {{ display_currency()['code'] }}" :counter="true" icon="fund" />
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('deposit.index') }}" class="btn btn-primary"><x-icon name="deposit" class="h-4 w-4" /> {{ __('Add money') }}</a>
        <a href="{{ route('funding.create') }}" class="btn btn-ghost"><x-icon name="fund" class="h-4 w-4" /> {{ __('Fund Alipay') }}</a>
    </div>

    <x-glass-card padding="p-0">
        <div class="flex items-center justify-between p-5">
            <h3 class="font-semibold text-strong">{{ __('Transactions') }}</h3>
            <a href="{{ route('transactions.index') }}" class="text-sm text-brand-300 hover:text-brand-200">{{ __('Full history') }}</a>
        </div>
        @include('dashboard.partials.txn-table', ['transactions' => $transactions])
        <div class="p-4">{{ $transactions->links() }}</div>
    </x-glass-card>
</div>
@endsection
