@extends('layouts.app')
@section('page-title', 'Add money')

@php
    $payIconMap = ['mtn_momo' => 'mtn', 'orange_money' => 'orange', 'flutterwave' => 'card', 'crypto' => 'usdt', 'bank_transfer' => 'bank'];

    $methodsJs = $methods->map(fn ($m) => [
        'id' => $m->id,
        'code' => $m->code,
        'name' => $m->name,
        'type' => $m->type,
        'currency' => $m->currency ?? config('platform.base_currency'),
        'automated' => (bool) $m->is_automated,
        'instructions' => $m->instructions,
        'payIcon' => $payIconMap[$m->code] ?? 'account',
        'min' => (float) $m->min_amount,
        'max' => $m->max_amount ? (float) $m->max_amount : null,
    ])->values();

    $channelsJs = [
        'momo' => $momoNumbers->map(fn ($n) => ['id' => $n->id, 'label' => ucfirst($n->provider).' MoMo', 'value' => $n->number, 'name' => $n->account_name])->values(),
        'bank' => $bankAccounts->map(fn ($b) => ['id' => $b->id, 'label' => $b->bank_name, 'value' => $b->account_number, 'name' => $b->account_name])->values(),
        'crypto' => $cryptoWallets->map(fn ($c) => ['id' => $c->id, 'label' => $c->asset.' · '.$c->network, 'value' => $c->address, 'name' => ''])->values(),
    ];

    // Every platform currency, common ones first so they don't get lost in 140+ options.
    $allCurrencies = config('platform.currencies');
    $commonFirst = collect(['XAF', 'USD', 'NGN', 'GHS', 'EUR', 'GBP', 'CNY', 'KES', 'ZAR'])->filter(fn ($c) => isset($allCurrencies[$c]));
    $orderedCodes = $commonFirst->concat(collect(array_keys($allCurrencies))->diff($commonFirst)->sort()->values());
    $currenciesJs = $orderedCodes->map(fn ($c) => ['code' => $c, 'symbol' => $allCurrencies[$c]['symbol'] ?? $c])->values();
@endphp

@section('content')
<x-page-header :title="__('Add money')" />

<div class="mx-auto max-w-2xl">
    <x-glass-card>
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-strong">{{ __('Recent deposits') }}</h3>
        </div>
        <div class="mt-4 space-y-2">
            @forelse ($recent as $d)
                <a href="{{ route('deposit.show', $d) }}" class="flex items-center justify-between rounded-xl px-3 py-2 hover:surface-2">
                    <div>
                        <p class="text-sm font-medium text-strong">{{ money($d->net_amount, $d->currency) }}</p>
                        <p class="text-xs text-faint">{{ $d->created_at->diffForHumans() }}</p>
                    </div>
                    <x-status-badge :status="$d->status" />
                </a>
            @empty
                <p class="py-6 text-center text-sm text-faint">{{ __('No deposits yet.') }}</p>
            @endforelse
        </div>
        <button type="button" @click="window.dispatchEvent(new CustomEvent('open-deposit-wizard'))" class="btn btn-primary mt-4 w-full">
            <x-icon name="deposit" class="h-4 w-4" /> {{ __('Add money') }}
        </button>
    </x-glass-card>
</div>

{{-- Popup wizard: currency + amount -> payment method -> that method's own details --}}
<div x-data="depositWizard(@js($methodsJs), @js($channelsJs), @js($currenciesJs), @js(auth()->user()->phone))"
     x-init="launch()" x-on:open-deposit-wizard.window="launch()" x-cloak>

    <div x-show="open" x-transition.opacity.duration.250ms class="fixed inset-0 z-[90] bg-black/55 backdrop-blur-sm" @click="close()" style="display:none"></div>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-2 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
         class="card-solid fixed inset-x-3 bottom-3 z-[100] mx-auto max-h-[85vh] max-w-lg overflow-hidden rounded-3xl border border-app shadow-2xl sm:inset-x-0 sm:inset-y-0 sm:m-auto sm:h-fit"
         style="display:none">

        <div class="flex items-center justify-between border-b border-app px-5 py-4">
            <div class="flex items-center gap-1.5">
                <span class="h-1.5 w-7 rounded-full transition-all duration-300" :class="step >= 1 ? 'bg-brand-600' : 'surface-2'"></span>
                <span class="h-1.5 w-7 rounded-full transition-all duration-300" :class="step >= 2 ? 'bg-brand-600' : 'surface-2'"></span>
                <span class="h-1.5 w-7 rounded-full transition-all duration-300" :class="step >= 3 ? 'bg-brand-600' : 'surface-2'"></span>
            </div>
            <a href="{{ route('wallet.index') }}" class="grid h-8 w-8 place-items-center rounded-full text-muted transition hover:surface-2 hover:text-strong">
                <x-icon name="x" class="h-4 w-4" />
            </a>
        </div>

        <form method="POST" action="{{ route('deposit.store') }}" enctype="multipart/form-data" class="max-h-[70vh] overflow-y-auto px-5 py-5">
            @csrf
            <input type="hidden" name="payment_method_id" :value="methodId">

            {{-- Step 1: currency + amount --}}
            <div x-show="step === 1" x-transition.opacity.duration.200ms>
                <h3 class="text-lg font-bold text-strong">{{ __('How much are you adding?') }}</h3>
                <p class="mt-1 text-sm text-muted">{{ __('Choose a currency and enter an amount.') }}</p>

                <div class="mt-5">
                    <label class="label">{{ __('Currency') }}</label>
                    <select x-model="currency" @change="methodId = null" class="field">
                        <template x-for="c in currencies" :key="c.code">
                            <option :value="c.code" x-text="c.code + ' (' + c.symbol + ')'"></option>
                        </template>
                    </select>
                </div>
                <div class="mt-4">
                    <label class="label">{{ __('Amount') }}</label>
                    <input type="number" name="amount" x-model="amount" min="1" required class="field text-2xl font-bold" placeholder="50000">
                </div>

                <button type="button" @click="step = 2" :disabled="!amount || Number(amount) <= 0" class="btn btn-primary mt-6 w-full disabled:opacity-40">
                    {{ __('Next') }} <x-icon name="arrow-right" class="h-4 w-4" />
                </button>
            </div>

            {{-- Step 2: choose payment method --}}
            <div x-show="step === 2" x-cloak x-transition.opacity.duration.200ms>
                <button type="button" @click="step = 1" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-strong">
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 rotate-180" /> {{ __('Back') }}
                </button>
                <h3 class="text-lg font-bold text-strong">{{ __('Choose a payment method') }}</h3>

                <div class="mt-4 space-y-2.5">
                    <template x-for="m in methodsForCurrency" :key="m.id">
                        <button type="button" @click="selectMethod(m.id)" class="flex w-full items-center gap-3 rounded-2xl border border-app card-solid p-4 text-left transition hover:border-brand-400/60 hover:shadow-md">
                            <span class="grid h-11 w-11 shrink-0 place-items-center">
                                <template x-if="m.payIcon === 'mtn'"><x-pay-icon name="mtn" class="h-11 w-11 shadow-sm" /></template>
                                <template x-if="m.payIcon === 'orange'"><x-pay-icon name="orange" class="h-11 w-11 shadow-sm" /></template>
                                <template x-if="m.payIcon === 'card'"><x-pay-icon name="card" class="h-11 w-11 shadow-sm" /></template>
                                <template x-if="m.payIcon === 'usdt'"><x-pay-icon name="usdt" class="h-11 w-11 shadow-sm" /></template>
                                <template x-if="m.payIcon === 'bank'"><x-pay-icon name="bank" class="h-11 w-11 shadow-sm" /></template>
                                <template x-if="m.payIcon === 'account'"><x-pay-icon name="account" class="h-11 w-11 shadow-sm" /></template>
                            </span>
                            <span class="min-w-0 flex-1 font-semibold text-strong" x-text="m.name"></span>
                            <span class="pill shrink-0" :class="m.automated ? 'bg-emerald-500/15 text-emerald-600' : 'bg-amber-500/15 text-amber-600'" x-text="m.automated ? '{{ __('Instant') }}' : '{{ __('Manual') }}'"></span>
                            <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-faint" />
                        </button>
                    </template>
                    <template x-if="methodsForCurrency.length === 0">
                        <p class="rounded-xl border border-app card-solid p-4 text-center text-sm text-muted">{{ __('No payment methods available for this currency yet.') }}</p>
                    </template>
                </div>
            </div>

            {{-- Step 3: the selected method's own details --}}
            <div x-show="step === 3" x-cloak x-transition.opacity.duration.200ms>
                <button type="button" @click="step = 2" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-strong">
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 rotate-180" /> {{ __('Back') }}
                </button>

                <template x-if="current">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="h-10 w-10 shrink-0 overflow-hidden rounded-full shadow-sm">
                                <template x-if="current.payIcon === 'mtn'"><x-pay-icon name="mtn" /></template>
                                <template x-if="current.payIcon === 'orange'"><x-pay-icon name="orange" /></template>
                                <template x-if="current.payIcon === 'card'"><x-pay-icon name="card" /></template>
                                <template x-if="current.payIcon === 'usdt'"><x-pay-icon name="usdt" /></template>
                                <template x-if="current.payIcon === 'bank'"><x-pay-icon name="bank" /></template>
                                <template x-if="current.payIcon === 'account'"><x-pay-icon name="account" /></template>
                            </span>
                            <h3 class="text-lg font-bold text-strong" x-text="current.name"></h3>
                        </div>

                        <div class="mt-4 flex items-center justify-between rounded-xl border border-app card-solid p-3 text-sm">
                            <span class="text-muted">{{ __('Amount') }}</span>
                            <span class="font-semibold text-strong" x-text="currency + ' ' + Number(amount || 0).toLocaleString()"></span>
                        </div>

                        {{-- Mobile money: confirm the number to charge --}}
                        <template x-if="current.type === 'momo'">
                            <div class="mt-3">
                                <label class="label">{{ __('Phone number to charge') }}</label>
                                <input type="tel" name="phone" x-model="phone" required class="field">
                                <p class="mt-3 flex items-start gap-1.5 rounded-xl border border-app card-solid p-3 text-xs text-muted">
                                    <x-icon name="shield" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500" />
                                    {{ __('You\'ll get a prompt on this number to approve the charge.') }}
                                </p>
                            </div>
                        </template>

                        {{-- Card: no raw card fields collected here, a real gateway takes over via its own secure checkout. --}}
                        <template x-if="current.type === 'card'">
                            <div class="mt-3">
                                <p class="flex items-start gap-1.5 rounded-xl border border-app card-solid p-3 text-xs text-muted">
                                    <x-icon name="shield" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500" />
                                    {{ __('You\'ll be taken to a secure payment page to enter your card details. Your wallet is credited automatically once confirmed.') }}
                                </p>
                            </div>
                        </template>

                        {{-- Crypto: our receiving address + an optional tx hash for faster reconciliation --}}
                        <template x-if="current.type === 'crypto'">
                            <div class="mt-3 space-y-3">
                                <template x-for="ch in channelsForCurrent" :key="ch.id">
                                    <div class="flex items-center justify-between gap-2 rounded-xl border border-app card-solid p-3">
                                        <div class="min-w-0">
                                            <p class="text-[11px] uppercase tracking-wide text-faint" x-text="ch.label"></p>
                                            <p class="mt-0.5 truncate font-mono font-semibold text-strong" x-text="ch.value"></p>
                                        </div>
                                        <button type="button" class="shrink-0 rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-700"
                                                @click="navigator.clipboard.writeText(ch.value); $el.textContent = '{{ __('Copied') }}'">{{ __('Copy') }}</button>
                                    </div>
                                </template>
                                <div>
                                    <label class="label">{{ __('Transaction hash (optional, speeds up confirmation)') }}</label>
                                    <input type="text" name="tx_hash" x-model="txHash" class="field font-mono text-sm" placeholder="0x…">
                                </div>
                                <p class="flex items-start gap-1.5 rounded-xl border border-app card-solid p-3 text-xs text-muted">
                                    <x-icon name="shield" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500" />
                                    {{ __('Send the exact amount to the address above. Your wallet is credited once the network confirms.') }}
                                </p>
                            </div>
                        </template>

                        {{-- Bank transfer: only this method's own destination + proof --}}
                        <template x-if="current.type === 'bank'">
                            <div class="mt-3 space-y-3">
                                <template x-if="current.instructions">
                                    <div class="rounded-xl border border-amber-400/30 bg-amber-500/10 p-3 text-sm text-amber-700">
                                        <p class="whitespace-pre-line" x-text="current.instructions"></p>
                                    </div>
                                </template>
                                <template x-for="ch in channelsForCurrent" :key="ch.id">
                                    <div class="flex items-center justify-between gap-2 rounded-xl border border-app card-solid p-3">
                                        <div class="min-w-0">
                                            <p class="text-[11px] uppercase tracking-wide text-faint" x-text="ch.label"></p>
                                            <p class="mt-0.5 truncate font-mono font-semibold text-strong" x-text="ch.value"></p>
                                            <p class="truncate text-xs text-muted" x-text="ch.name"></p>
                                        </div>
                                        <button type="button" class="shrink-0 rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-700"
                                                @click="navigator.clipboard.writeText(ch.value); $el.textContent = '{{ __('Copied') }}'">{{ __('Copy') }}</button>
                                    </div>
                                </template>
                                <div>
                                    <label class="label">{{ __('Proof of payment (optional now, can upload later)') }}</label>
                                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" class="field">
                                </div>
                                <p class="flex items-start gap-1.5 rounded-xl border border-app card-solid p-3 text-xs text-muted">
                                    <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-500" />
                                    {{ __('Send the payment using the details above; an admin confirms it shortly.') }}
                                </p>
                            </div>
                        </template>

                        <button type="submit" class="btn btn-primary mt-6 w-full">
                            <span x-text="current.automated ? '{{ __('Pay & credit wallet') }}' : '{{ __('Submit deposit') }}'"></span>
                        </button>
                    </div>
                </template>
            </div>
        </form>
    </div>
</div>
@endsection
