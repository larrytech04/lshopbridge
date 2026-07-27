@extends('layouts.app')
@section('page-title', 'Fund China Wallet')

@section('content')
<div class="mx-auto max-w-2xl"
     x-data="{
        step: {{ $errors->has('pin') || $errors->has('funding_source') || $errors->has('payment_method_id') ? 4 : ($errors->has('amount') ? 2 : 1) }},
        amount: {{ old('amount', 50000) }},
        rate: {{ $sampleQuote['exchange_rate'] }},
        feePercent: {{ (float) setting('display_fee_percent', 2.5) }},
        source: '{{ old('funding_source', 'wallet') }}',
        methodId: {{ old('payment_method_id', optional($methods->first())->id ?? 'null') }},
        beneficiaryId: {{ old('beneficiary_account_id', optional($beneficiaries->first())->id ?? 'null') }},
        walletBalance: {{ (float) $wallet->balance }},
        get fee() { return Math.max(0, this.amount * this.feePercent / 100); },
        get total() { return Number(this.amount) + this.fee; },
        get receives() { return this.amount * this.rate; },
        get insufficient() { return this.source === 'wallet' && this.total > this.walletBalance; },
        get beneficiary() { return this.beneficiaries.find(b => b.id === this.beneficiaryId); },
        beneficiaries: @js($beneficiaries->map(fn ($b) => ['id' => $b->id, 'label' => $b->app_type->label().', '.$b->account_name])),
        money(v, c) { return Number(v||0).toLocaleString(undefined,{maximumFractionDigits:2}) + ' ' + c; },
        next() { if (this.step < 4) this.step++; },
        back() { if (this.step > 1) this.step--; },
     }">
    <a href="{{ route('funding.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← Back</a>
    <h2 class="mt-2 text-2xl font-bold text-strong">{{ __('Fund China Wallet') }}</h2>

    {{-- Step indicator --}}
    <div class="mt-5 flex items-center">
        @foreach (['Recipient', 'Amount', 'Payment', 'Confirm'] as $i => $label)
            <div class="flex flex-1 flex-col items-center text-center">
                <span class="grid h-7 w-7 place-items-center rounded-full text-xs font-bold" :class="step > {{ $i + 1 }} ? 'bg-brand-600 text-white' : (step === {{ $i + 1 }} ? 'bg-brand-600 text-white' : 'surface-2 text-faint')">{{ $i + 1 }}</span>
                <span class="mt-1 text-[10px] font-medium text-muted">{{ __($label) }}</span>
            </div>
            @if (! $loop->last)<span class="mx-1 h-0.5 flex-1 surface-2" :class="step > {{ $i + 1 }} ? 'bg-brand-600' : ''"></span>@endif
        @endforeach
    </div>

    <form method="POST" action="{{ route('funding.store') }}" class="mt-6 space-y-6">
        @csrf

        {{-- Step 1: Recipient --}}
        <div x-show="step === 1">
            <x-glass-card>
                <label class="label">{{ __('Recipient China wallet') }}</label>
                <select name="beneficiary_account_id" x-model.number="beneficiaryId" required class="field">
                    @foreach ($beneficiaries as $b)
                        <option value="{{ $b->id }}">{{ $b->app_type->label() }}, {{ $b->account_name }} ({{ $b->account_id }})</option>
                    @endforeach
                </select>
                <a href="{{ route('beneficiaries.index') }}" class="mt-2 inline-block text-xs text-brand-300 hover:text-brand-200">{{ __('+ Add another China wallet') }}</a>
            </x-glass-card>
            <button type="button" @click="next()" class="btn btn-primary mt-4 w-full">{{ __('Continue') }}</button>
        </div>

        {{-- Step 2: Amount --}}
        <div x-show="step === 2" x-cloak>
            <x-glass-card>
                <label class="label">{{ __('Amount to send') }} ({{ config('platform.base_currency') }})</label>
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
            <div class="mt-4 flex gap-3">
                <button type="button" @click="back()" class="btn btn-ghost flex-1">{{ __('Back') }}</button>
                <button type="button" @click="next()" class="btn btn-primary flex-1">{{ __('Continue') }}</button>
            </div>
        </div>

        {{-- Step 3: Payment method --}}
        <div x-show="step === 3" x-cloak>
            <x-glass-card>
                <label class="label">{{ __('Pay with') }}</label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="funding_source" value="wallet" x-model="source" class="peer sr-only">
                        <div class="rounded-2xl border border-app surface p-4 peer-checked:border-brand-400/60 peer-checked:bg-slate-500/10">
                            <div class="flex items-center gap-3"><x-icon name="wallet" class="h-5 w-5 text-brand-200" /><span class="font-medium text-strong">{{ __('Wallet balance') }}</span></div>
                            <p class="mt-1 text-xs text-muted">{{ money($wallet->balance, $wallet->currency) }} {{ __('available') }}</p>
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
            <div class="mt-4 flex gap-3">
                <button type="button" @click="back()" class="btn btn-ghost flex-1">{{ __('Back') }}</button>
                <button type="button" @click="next()" class="btn btn-primary flex-1" :disabled="insufficient">{{ __('Continue') }}</button>
            </div>
        </div>

        {{-- Step 4: Review & confirm --}}
        <div x-show="step === 4" x-cloak>
            <x-glass-card>
                <h3 class="font-semibold text-strong">{{ __('Review & confirm') }}</h3>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">{{ __('Recipient') }}</dt><dd class="text-body" x-text="beneficiary?.label"></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ __('You send') }}</dt><dd class="font-semibold text-strong" x-text="money(total, '{{ config('platform.base_currency') }}')"></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ __('Recipient receives') }}</dt><dd class="font-semibold text-strong" x-text="money(receives, '{{ config('platform.target_currency') }}')"></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">{{ __('Paying with') }}</dt><dd class="text-body" x-text="source === 'wallet' ? '{{ __('Wallet balance') }}' : '{{ __('Direct payment') }}'"></dd></div>
                </dl>

                @if ($user->hasTransactionPin())
                    <div x-show="source === 'wallet'" class="mt-5 border-t border-app pt-4">
                        <label class="label">{{ __('Transaction PIN') }}</label>
                        <input type="password" inputmode="numeric" name="pin" class="field" placeholder="••••">
                        @error('pin')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>
                @else
                    <p x-show="source === 'wallet'" class="mt-5 border-t border-app pt-4 text-sm text-amber-400">
                        {{ __('Set a transaction PIN in :link before funding from your wallet.', ['link' => '']) }}
                        <a href="{{ route('security.index') }}" class="font-semibold underline">{{ __('Security & Devices') }}</a>
                    </p>
                @endif
            </x-glass-card>
            <div class="mt-4 flex gap-3">
                <button type="button" @click="back()" class="btn btn-ghost flex-1">{{ __('Back') }}</button>
                <button type="submit" class="btn btn-primary flex-1" :disabled="insufficient || (source === 'wallet' && {{ $user->hasTransactionPin() ? 'false' : 'true' }})">
                    <x-icon name="fund" class="h-4 w-4" /> <span x-text="'Send ' + money(receives, '{{ config('platform.target_currency') }}')"></span>
                </button>
            </div>
            <p class="mt-3 text-center text-xs text-faint">{{ __('Final rate & fee are confirmed at checkout. Verified recipients fund automatically.') }}</p>
        </div>
    </form>
</div>
@endsection
