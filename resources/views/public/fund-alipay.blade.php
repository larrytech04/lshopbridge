@extends('layouts.public')
@section('title', __('China Wallet Funding').' · '.config('platform.name'))
@section('meta_description', __('Send funds to an approved China wallet using your LshopBridge balance or an available payment method. Review the recipient, exchange rate, fees and delivered amount before confirming.'))

@php
    $user = auth()->user();

    // Context-aware CTA state, per section 8/29 of the redesign brief — never
    // the same call-to-action for a guest, an unverified customer, a verified
    // customer with no approved recipient yet, and a fully eligible customer.
    $ctaState = match (true) {
        ! $user => 'guest',
        ! ($eligibility['kyc_ok'] ?? false) => 'unverified',
        ! ($eligibility['has_approved_beneficiary'] ?? false) => 'no_beneficiary',
        default => 'eligible',
    };
@endphp

@section('content')
<section class="mx-auto max-w-none px-4 pt-16 sm:px-6 sm:pt-20"
         x-data="fundingQuote({
             amount: {{ $defaultAmount }},
             appType: @js($defaultWalletCode),
             baseCurrency: '{{ config('platform.base_currency') }}',
             targetCurrency: '{{ config('platform.target_currency') }}',
             quoteUrl: '{{ route('calculator') }}',
             initialQuote: @js($quote),
             wallets: @js($wallets->pluck('name', 'code')),
         })">
    <div class="grid items-start gap-10 lg:grid-cols-2 lg:gap-14">
        {{-- ============================================================ LEFT: pitch + wallet selector + CTA --}}
        <div>
            <h1 class="text-4xl font-extrabold tracking-tight text-strong sm:text-5xl">
                <span class="sm:hidden">{{ __('Fund China Wallets') }}</span>
                <span class="hidden sm:inline">{{ __('Fund') }} <span class="text-gradient">{{ __('Alipay, WeChat Pay') }}</span> {{ __('and other supported China wallets') }}</span>
            </h1>
            <p class="mt-5 max-w-xl text-lg text-body">{{ __('Send funds to an approved China wallet using your LshopBridge balance or an available payment method. Review the recipient, exchange rate, fees and delivered amount before confirming.') }}</p>

            {{-- Wallet selector: real, admin-configured wallet types — no hardcoded availability. --}}
            @if ($wallets->isEmpty())
                <div class="mt-8"><x-empty icon="wallet" title="{{ __('No wallets configured yet') }}" message="{{ __('Check back soon while we finish setting up China wallet funding.') }}" /></div>
            @else
                <div class="mt-8 grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="{{ __('China wallet type') }}">
                    @foreach ($wallets as $wallet)
                        <button type="button" role="radio" :aria-checked="appType === '{{ $wallet->code }}'"
                                @click="appType = '{{ $wallet->code }}'"
                                class="group rounded-2xl border p-4 text-left transition-all duration-200"
                                :class="appType === '{{ $wallet->code }}' ? 'border-brand-500 card-solid shadow-sm ring-1 ring-brand-400/40' : 'border-app surface hover:border-brand-400/40'">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold text-strong">{{ $wallet->name }}</span>
                                <span class="pill {{ $wallet->automated_funding ? 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25' : 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25' }}">
                                    {{ $wallet->automated_funding ? __('Automated') : __('Manual processing') }}
                                </span>
                            </div>
                            @if ($wallet->processing_time_estimate)
                                <p class="mt-1.5 text-xs text-muted">{{ $wallet->processing_time_estimate }}</p>
                            @endif
                            @if ($wallet->min_kyc_level)
                                <p class="mt-1 text-[11px] text-faint">{{ __('Requires verification level :level+', ['level' => $wallet->min_kyc_level]) }}</p>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Primary / secondary actions, context-aware per customer state --}}
            <div class="mt-8 flex flex-wrap items-center gap-3">
                @switch($ctaState)
                    @case('guest')
                        <a href="{{ route('register') }}" class="btn btn-primary px-6 py-3 text-base">{{ __('Create Account to Continue') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
                        <a href="{{ route('login') }}" class="btn btn-ghost px-6 py-3 text-base">{{ __('Sign In') }}</a>
                        @break
                    @case('unverified')
                        <a href="{{ route('verification.index') }}" class="btn btn-primary px-6 py-3 text-base">{{ __('Complete Verification') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
                        @break
                    @case('no_beneficiary')
                        <a href="{{ route('beneficiaries.index') }}" class="btn btn-primary px-6 py-3 text-base">{{ __('Add your first China wallet') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
                        <a href="{{ route('how-it-works') }}" class="btn btn-ghost px-6 py-3 text-base">{{ __('Learn How Verification Works') }}</a>
                        @break
                    @default
                        <a href="{{ route('funding.create') }}" class="btn btn-primary px-6 py-3 text-base">{{ __('Fund Now') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
                @endswitch
            </div>

            <p class="mt-4 max-w-xl text-xs text-faint">{{ __('Availability depends on your country, verification level, recipient approval, transaction limits and the wallet you choose.') }}</p>
        </div>

        {{-- ============================================================ RIGHT: calculator --}}
        <div class="glass-strong relative overflow-hidden rounded-3xl p-6 shadow-2xl ring-1 ring-app sm:rounded-[2rem] sm:p-8 lg:sticky lg:top-24">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-strong">{{ __('Check your funding amount') }}</h2>
                <span class="pill bg-sky-500/15 text-sky-600 ring-1 ring-sky-500/25">{{ __('Estimate') }}</span>
            </div>

            <div class="mt-4">
                <label for="fund-amount" class="label text-xs">{{ __('You Pay') }} ({{ config('platform.base_currency') }})</label>
                <input id="fund-amount" type="number" min="1" step="1" inputmode="decimal" x-model.number="amount" class="field text-lg font-bold"
                       aria-describedby="fund-recipient-gets">
            </div>

            <div class="relative my-3 flex items-center justify-center" aria-hidden="true">
                <div class="absolute inset-x-2 top-1/2 h-px -translate-y-1/2" style="background: var(--border);"></div>
                <span class="relative grid h-9 w-9 place-items-center rounded-full text-white shadow-md" style="background: var(--color-brand-600);"><x-icon name="chevron-down" class="h-4 w-4" /></span>
            </div>

            <div id="fund-recipient-gets" class="rounded-2xl p-4 ring-1 ring-app" style="background: color-mix(in srgb, #64748b 12%, transparent);" aria-live="polite">
                <p class="text-xs font-medium text-muted">{{ __('Recipient gets') }}</p>
                <template x-if="quote">
                    <p class="mt-0.5 text-3xl font-extrabold tracking-tight text-strong"><span x-text="money(quote.target_amount, quote.target_currency)"></span></p>
                </template>
                <template x-if="!quote && !loading">
                    <p class="mt-0.5 text-xl font-semibold text-faint">{{ __('Enter an amount') }}</p>
                </template>
                <template x-if="loading">
                    <p class="mt-0.5 text-xl font-semibold text-faint">{{ __('Calculating…') }}</p>
                </template>
                <p class="mt-1 text-[11px] text-faint" x-show="quote" x-cloak>
                    <span x-text="wallets[appType] ?? ''"></span>
                </p>
            </div>

            <dl class="mt-4 space-y-2 text-sm" x-show="quote" x-cloak>
                <div class="flex justify-between">
                    <dt class="text-muted">{{ __('Current Estimated Rate') }}</dt>
                    <dd class="font-mono font-medium text-body" x-text="rateLabel()"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-muted">{{ __('Service fee') }}</dt>
                    <dd class="font-medium text-body"><span x-text="quote ? money(quote.fee, baseCurrency) : ''"></span></dd>
                </div>
                <div class="flex justify-between border-t border-app pt-2.5 text-base font-bold">
                    <dt class="text-strong">{{ __('Total to pay') }}</dt>
                    <dd class="text-strong"><span x-text="quote ? money(quote.total_charged, baseCurrency) : ''"></span></dd>
                </div>
                <div class="flex justify-between text-[11px] text-faint" x-show="quote?.rate_updated_at">
                    <dt>{{ __('Rate checked') }}</dt>
                    <dd x-text="rateAgo()"></dd>
                </div>
            </dl>

            <template x-if="error">
                <p class="mt-4 rounded-xl bg-rose-500/10 p-3 text-sm text-rose-600 ring-1 ring-rose-500/25">{{ __('A funding quote is temporarily unavailable. Please try again in a moment.') }}</p>
            </template>
            <template x-if="quote && quote.rate_available === false">
                <p class="mt-4 rounded-xl bg-amber-500/10 p-3 text-sm text-amber-600 ring-1 ring-amber-500/25">{{ __('Rate temporarily unavailable.') }}</p>
            </template>

            @switch($ctaState)
                @case('guest')
                    <a href="{{ route('register') }}" class="btn btn-primary mt-5 w-full py-3">{{ __('Create Account to Continue') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
                    @break
                @case('unverified')
                    <a href="{{ route('verification.index') }}" class="btn btn-primary mt-5 w-full py-3">{{ __('Complete Verification') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
                    @break
                @case('no_beneficiary')
                    <a href="{{ route('beneficiaries.index') }}" class="btn btn-primary mt-5 w-full py-3">{{ __('Add your first China wallet') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
                    @break
                @default
                    <a href="{{ route('funding.create') }}" class="btn btn-primary mt-5 w-full py-3">{{ __('Continue with This Quote') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
            @endswitch
            <p class="mt-2.5 text-center text-xs text-muted">{{ __('Estimate only. Your final exchange rate, fees and recipient amount will be confirmed before payment.') }}</p>
        </div>
    </div>
</section>

{{-- ============================================================ TRUST STRIP --}}
<section class="mx-auto mt-20 max-w-none px-4 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ __('Built for trust') }}</h2>
        <p class="mt-2 text-body">{{ __('Every funding request is verified, transparent and tracked end to end.') }}</p>
    </div>

    <div class="divide-app mt-10 grid sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4 lg:divide-y-0">
        @foreach ([
            ['Verified--Streamline-Rounded-Streamline-Material.png', __('Verified Recipients'), __('Add and verify recipient accounts before sending funds.')],
            ['Cash-Exchange-Rate--Streamline-Flex.png', __('Transparent Quote'), __('Review the effective rate, fees and delivered amount before confirming.')],
            ['Delivery-Package-Give--Streamline-Freehand.png', __('Trackable Delivery'), __('Follow every stage of your funding request from payment to completion.')],
            ['Security-Shield-Rate-Stars--Streamline-Freehand.png', __('Secure Confirmation'), __('Sensitive funding actions may require password, MFA or additional verification.')],
        ] as [$icon, $t, $b])
            <div class="flex flex-col items-center gap-3.5 px-6 py-8 text-center">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-brand-500/10 text-brand-600"><x-img-icon :name="$icon" class="h-8 w-8" /></span>
                <h3 class="font-bold text-strong">{{ $t }}</h3>
                <p class="max-w-[15rem] text-sm leading-relaxed text-muted">{{ $b }}</p>
            </div>
        @endforeach
    </div>
    <p class="mt-8 text-center text-xs text-faint">{{ __('Delivery time depends on the selected wallet, provider and verification requirements.') }}</p>
</section>

{{-- ============================================================ HOW FUNDING WORKS (same visual language as the About page's "How it works": floating loop art + big numbered steps, no icon badges) --}}
@if ($steps->isNotEmpty())
<section class="mt-20 w-full surface border-y border-app">
    <div class="mx-auto max-w-none px-4 py-16 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ __('How Funding Works') }}</h2>
        </div>

        <div class="mx-auto text-center">
            <img src="{{ asset('assets/'.rawurlencode('how it works aboutpg.png')) }}" alt="" class="img-float mx-auto h-36 w-auto sm:h-44" loading="lazy">
            <div class="float-shadow mx-auto mt-4"></div>
        </div>

        <div class="mt-6 grid gap-x-10 gap-y-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($steps as $i => $step)
                <div class="flex items-start gap-2.5">
                    <span class="shrink-0 text-3xl font-black leading-none tracking-tight text-brand-600">0{{ $i + 1 }}</span>
                    <div>
                        <h3 class="text-sm font-bold text-strong">{{ $step->title }}</h3>
                        <p class="mt-1 text-xs leading-relaxed text-muted">{{ $step->body }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('how-it-works') }}" class="btn btn-ghost">{{ __('Learn More') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
    </div>
</section>
@endif

{{-- ============================================================ RATE & FEE TRANSPARENCY --}}
<section class="mx-auto mt-20 max-w-none px-4 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ __('Understanding Your Quote') }}</h2>
        <p class="mt-2 text-body">{{ __('Four numbers make up every quote, here is what each one means.') }}</p>
    </div>

    <dl class="divide-app mt-10 grid sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4">
        <div class="flex flex-col items-center gap-1.5 px-6 py-6 text-center">
            <dt class="border-t-2 border-brand-500 pt-3 font-bold text-strong">{{ __('Base Rate') }}</dt>
            <dd class="max-w-[15rem] text-sm leading-relaxed text-muted">{{ __('The underlying conversion rate before the platform margin.') }}</dd>
        </div>
        <div class="flex flex-col items-center gap-1.5 px-6 py-6 text-center">
            <dt class="border-t-2 border-brand-500 pt-3 font-bold text-strong">{{ __('Effective Rate') }}</dt>
            <dd class="max-w-[15rem] text-sm leading-relaxed text-muted">{{ __('The rate used to calculate the amount the recipient receives.') }}</dd>
        </div>
        <div class="flex flex-col items-center gap-1.5 px-6 py-6 text-center">
            <dt class="border-t-2 border-brand-500 pt-3 font-bold text-strong">{{ __('Service Fee') }}</dt>
            <dd class="max-w-[15rem] text-sm leading-relaxed text-muted">{{ __('The charge for processing the request.') }}</dd>
        </div>
        <div class="flex flex-col items-center gap-1.5 px-6 py-6 text-center">
            <dt class="border-t-2 border-brand-500 pt-3 font-bold text-strong">{{ __('Total to Pay') }}</dt>
            <dd class="max-w-[15rem] text-sm leading-relaxed text-muted">{{ __('The complete amount charged to the customer.') }}</dd>
        </div>
    </dl>

    @if ($quote['rate_available'])
        <div class="mx-auto mt-10 max-w-3xl rounded-2xl border border-dashed border-app p-6 text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-faint">{{ __('Illustrative example only — not a live quote.') }}</p>
            <div class="mt-3 flex flex-wrap items-center justify-center gap-x-8 gap-y-2 font-mono text-sm">
                <span class="text-muted">{{ __('You pay') }} <strong class="text-strong">{{ money($quote['source_amount'], $quote['source_currency']) }}</strong></span>
                <span class="text-muted">{{ __('Service fee') }} <strong class="text-strong">{{ money($quote['fee'], $quote['source_currency']) }}</strong></span>
                <span class="text-muted">{{ __('Recipient gets') }} <strong class="text-strong">{{ money($quote['target_amount'], $quote['target_currency']) }}</strong></span>
            </div>
        </div>
    @endif
</section>

{{-- ============================================================ SAFETY --}}
<section class="mx-auto mt-20 max-w-none px-4 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ __('Confirm Before You Fund') }}</h2>
    </div>
    <ul class="mx-auto mt-10 max-w-xl space-y-4 text-sm text-body">
        <li class="flex items-start gap-3"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" /> {{ __('Verify the recipient name and wallet identifier.') }}</li>
        <li class="flex items-start gap-3"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" /> {{ __('Never share passwords, MFA codes or recovery codes.') }}</li>
        <li class="flex items-start gap-3"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" /> {{ __('LshopBridge support will not ask for your full security credentials.') }}</li>
        <li class="flex items-start gap-3"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" /> {{ __('Review the delivered amount and fees before confirmation.') }}</li>
        <li class="flex items-start gap-3"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" /> {{ __('Report suspicious activity through the Security Center.') }}</li>
    </ul>
    <div class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm">
        <a href="{{ route('security.index') }}" class="font-medium text-brand-500 hover:underline">{{ __('Security Center') }}</a>
        <a href="{{ route('legal.show', 'china-wallet-funding-terms') }}" class="font-medium text-brand-500 hover:underline">{{ __('China Wallet Funding Terms') }}</a>
        <a href="{{ route('legal.show', 'refund-policy') }}" class="font-medium text-brand-500 hover:underline">{{ __('Refund & Reversal Policy') }}</a>
        <a href="{{ route('contact') }}" class="font-medium text-brand-500 hover:underline">{{ __('Support') }}</a>
    </div>
</section>

@endsection
