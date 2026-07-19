@extends('layouts.app')
@section('page-title', 'New funding')

@section('content')
<div class="mx-auto max-w-2xl"
     x-data="{
        amount: {{ old('amount', 50000) }},
        rate: {{ $sampleQuote['exchange_rate'] }},
        feePercent: {{ (float) setting('display_fee_percent', 2.5) }},
        source: '{{ old('funding_source', 'wallet') }}',
        methodId: {{ old('payment_method_id', optional($methods->first())->id ?? 'null') }},
        walletBalance: {{ (float) $wallet->balance }},
        get fee() { return Math.max(0, this.amount * this.feePercent / 100); },
        get total() { return Number(this.amount) + this.fee; },
        get receives() { return this.amount * this.rate; },
        get insufficient() { return this.source === 'wallet' && this.total > this.walletBalance; },
        money(v, c) { return Number(v||0).toLocaleString(undefined,{maximumFractionDigits:2}) + ' ' + c; },
     }">
    <a href="{{ route('funding.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← Back</a>
    <h2 class="mt-2 text-2xl font-bold text-strong">{{ __('Fund a China wallet') }}</h2>

    <form method="POST" action="{{ route('funding.store') }}" class="mt-6 space-y-6">
        @csrf

        <x-glass-card>
            <label class="label">{{ __('Recipient China wallet') }}</label>
            <select name="beneficiary_account_id" required class="field">
                @foreach ($beneficiaries as $b)
                    <option value="{{ $b->id }}" @selected(old('beneficiary_account_id') == $b->id)>{{ $b->app_type->label() }}, {{ $b->account_name }} ({{ $b->account_id }})</option>
                @endforeach
            </select>
            <a href="{{ route('beneficiaries.index') }}" class="mt-2 inline-block text-xs text-brand-300 hover:text-brand-200">{{ __('+ Add another China wallet') }}</a>
        </x-glass-card>

        <x-glass-card>
            <label class="label">Amount to send ({{ config('platform.base_currency') }})</label>
            <input type="number" name="amount" min="1" x-model.number="amount" required class="field text-lg font-semibold">

            <div class="mt-5 rounded-2xl border border-app surface p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted">{{ __('Recipient receives') }}</span>
                    <span class="text-xl font-bold text-strong" x-text="money(receives, '{{ config('platform.target_currency') }}')"></span>
                </div>
                <dl class="mt-3 space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">{{ __('Rate') }}</dt><dd class="text-body">1 {{ config('platform.base_currency') }} = {{ rtrim(rtrim(number_format($sampleQuote['exchange_rate'],6),'0'),'.') }} {{ config('platform.target_currency') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ __('Fee (est.)') }}</dt><dd class="text-body" x-text="money(fee, '{{ config('platform.base_currency') }}')"></dd></div>
                    <div class="flex justify-between border-t border-app pt-1.5 font-semibold"><dt class="text-strong">{{ __('Total charged') }}</dt><dd class="text-strong" x-text="money(total, '{{ config('platform.base_currency') }}')"></dd></div>
                </dl>
            </div>
        </x-glass-card>

        <x-glass-card>
            <label class="label">{{ __('Pay with') }}</label>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="cursor-pointer">
                    <input type="radio" name="funding_source" value="wallet" x-model="source" class="peer sr-only">
                    <div class="rounded-2xl border border-app surface p-4 peer-checked:border-brand-400/60 peer-checked:bg-slate-500/10">
                        <div class="flex items-center gap-3"><x-icon name="wallet" class="h-5 w-5 text-brand-200" /><span class="font-medium text-strong">{{ __('Wallet balance') }}</span></div>
                        <p class="mt-1 text-xs text-muted">{{ money($wallet->balance, $wallet->currency) }} available</p>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="funding_source" value="direct" x-model="source" class="peer sr-only">
                    <div class="rounded-2xl border border-app surface p-4 peer-checked:border-brand-400/60 peer-checked:bg-slate-500/10">
                        <div class="flex items-center gap-3"><x-icon name="card" class="h-5 w-5 text-brand-200" /><span class="font-medium text-strong">{{ __('Pay directly') }}</span></div>
                        <p class="mt-1 text-xs text-muted">{{ __('Charge instantly via a provider') }}</p>
                    </div>
                </label>
            </div>

            <div x-show="source === 'direct'" x-cloak class="mt-4">
                <label class="label">{{ __('Payment method') }}</label>
                <select name="payment_method_id" x-model.number="methodId" class="field">
                    @forelse ($methods as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@empty<option value="">{{ __('No automated methods configured') }}</option>@endforelse
                </select>
            </div>

            <p x-show="insufficient" x-cloak class="mt-3 text-sm text-rose-300">{{ __('Insufficient wallet balance, top up or pay directly.') }}</p>
        </x-glass-card>

        <button class="btn btn-primary w-full py-3" :disabled="insufficient">
            <x-icon name="fund" class="h-4 w-4" /> <span x-text="'Send ' + money(receives, '{{ config('platform.target_currency') }}')">{{ __('Confirm funding') }}</span>
        </button>
        <p class="text-center text-xs text-faint">{{ __('Final rate &amp; fee are confirmed at checkout. Verified recipients fund automatically.') }}</p>
    </form>
</div>
@endsection
