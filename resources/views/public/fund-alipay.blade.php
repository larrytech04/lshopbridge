@extends('layouts.public')
@section('title', 'Fund Alipay · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-none px-4 pt-20 sm:px-6">
    <div class="grid items-center gap-12 lg:grid-cols-2">
        <div>
            <h1 class="text-4xl font-extrabold text-strong sm:text-5xl">{{ __('Fund') }} <span class="text-gradient">{{ __('Alipay, WeChat Pay') }}</span> {{ __('& more') }}</h1>
            <p class="mt-5 text-lg text-body">{{ __('Send money to any China wallet from your phone. Pick your app, enter an amount, and we deliver automatically.') }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                @foreach ($apps as $key => $label)
                    <span class="pill surface text-body ring-1 ring-white/10"><x-icon name="wallet" class="h-4 w-4 text-brand-200" /> {{ $label }}</span>
                @endforeach
            </div>
            <div class="mt-8">
                <a href="{{ route('register') }}" class="btn btn-primary px-6 py-3 text-base">{{ __('Fund now') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </div>
        </div>

        {{-- Funding calculator, the rich glass card (moved from the homepage hero) --}}
        <div class="glass-strong relative overflow-hidden rounded-3xl p-6 shadow-2xl ring-1 ring-app sm:rounded-[2rem] sm:p-8"
             x-data="feeCalculator({ amount: 100000, rate: {{ $rate }}, feePercent: {{ (float) setting('display_fee_percent', 2.5) }}, feeFixed: 0, baseCurrency: '{{ config('platform.base_currency') }}', targetCurrency: '{{ config('platform.target_currency') }}' })">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-strong">{{ __('Funding calculator') }}</h3>
                <span class="inline-flex items-center rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-semibold text-emerald-500 ring-1 ring-emerald-500/25">{{ __("Today's rate") }}</span>
            </div>
            <div class="mt-4">
                <label class="label text-xs">{{ __('You send') }} ({{ config('platform.base_currency') }})</label>
                <input type="number" min="0" x-model.number="amount" class="field text-lg font-bold">
            </div>
            <div class="relative my-3 flex items-center justify-center">
                <div class="absolute inset-x-2 top-1/2 h-px -translate-y-1/2" style="background: var(--border);"></div>
                <span class="relative grid h-9 w-9 place-items-center rounded-full text-white shadow-md" style="background: var(--color-brand-600);"><x-icon name="swap" class="h-4 w-4" /></span>
            </div>
            <div class="rounded-2xl p-4 ring-1 ring-app" style="background: color-mix(in srgb, #64748b 12%, transparent);">
                <p class="text-xs font-medium text-muted">{{ __('Recipient gets') }}</p>
                <p class="mt-0.5 text-3xl font-extrabold tracking-tight text-strong"><span x-text="money(receives, targetCurrency)"></span></p>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-muted">{{ __('Exchange rate') }}</dt><dd class="font-medium text-body">1 {{ config('platform.base_currency') }} = {{ rtrim(rtrim(number_format($rate, 6), '0'), '.') }} {{ config('platform.target_currency') }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">{{ __('Service fee (est.)') }}</dt><dd class="font-medium text-body" x-text="money(fee, baseCurrency)"></dd></div>
                <div class="flex justify-between border-t border-app pt-2.5 text-base font-bold"><dt class="text-strong">{{ __('Total to pay') }}</dt><dd class="text-strong" x-text="money(total, baseCurrency)"></dd></div>
            </dl>
            <a href="{{ route('register') }}" class="btn btn-primary mt-5 w-full py-3">{{ __('Get this rate') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
            <p class="mt-2.5 text-center text-[11px] text-faint">{{ __('Estimate only · final rate shown at checkout') }}</p>
        </div>
    </div>
</section>

<section class="mx-auto mt-20 max-w-none px-4 sm:px-6">
    <div class="grid gap-6 md:grid-cols-3">
        @foreach ([
            ['lock', 'Verified recipients', 'Save & verify China wallets once for safe repeat funding.'],
            ['swap', 'Auto-delivery', 'Funds are pushed to the recipient instantly after payment clears.'],
            ['chart', 'Fair rates', 'Transparent, admin-managed rates with the fee shown up front.'],
        ] as [$icon, $t, $b])
            <div class="glass rounded-2xl p-6">
                <x-icon :name="$icon" class="h-6 w-6 text-brand-200" />
                <h3 class="mt-3 font-semibold text-strong">{{ $t }}</h3>
                <p class="mt-2 text-sm text-muted">{{ $b }}</p>
            </div>
        @endforeach
    </div>
</section>
@endsection
