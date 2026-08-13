@extends('layouts.public')
@section('title', __(':country | China Wallet Funding & Digital Services', ['country' => $country->name]).' · '.config('platform.name'))
@section('meta_description', $hasRealContent
    ? __('Fund China wallets, buy digital products and ship goods from :country through :name. See real payment methods, providers and rates.', ['country' => $country->name, 'name' => config('platform.name')])
    : __(':name is expanding to :country. See what is already available platform-wide while local payment options are being added.', ['country' => $country->name, 'name' => config('platform.name')]))
@section('robots', $robotsOverride ?? '')

@push('structured-data')
    {!! \App\Services\Seo\StructuredDataBuilder::scriptTag($breadcrumbSchema) !!}
@endpush

@section('content')
<div class="mx-auto max-w-4xl px-4 pt-8 pb-16 sm:px-6">
    <div class="mb-5"><x-breadcrumbs :items="$breadcrumbs" /></div>

    <div class="flex items-center gap-3">
        @if ($country->flag_emoji)<span class="text-4xl" aria-hidden="true">{{ $country->flag_emoji }}</span>@endif
        <h1 class="text-2xl font-extrabold text-strong sm:text-3xl">{{ __(':country: Fund China Wallets & Digital Services', ['country' => $country->name]) }}</h1>
    </div>

    @if (! $hasRealContent || ! $isFullyLaunched)
        <div class="mt-5 flex items-start gap-3 rounded-2xl bg-sky-500/10 p-4 ring-1 ring-sky-400/30">
            <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />
            <p class="text-sm text-sky-800 dark:text-sky-300">
                {{ __(':name is expanding to :country. Local payment methods are still being added, but you can already create an account, shop digital products and use any payment method available to your account below.', ['name' => config('platform.name'), 'country' => $country->name]) }}
            </p>
        </div>
    @else
        <p class="mt-4 max-w-2xl text-lg text-body">
            {{ __('Fund Alipay and other China wallets from :country, backed by real local payment options and transparent rates.', ['country' => $country->name]) }}
        </p>
    @endif

    <div class="mt-8 grid gap-6 sm:grid-cols-2">
        @if ($country->currency_code)
            <div class="rounded-2xl border border-app p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-faint">{{ __('Local currency') }}</p>
                <p class="mt-1 text-xl font-bold text-strong">{{ $country->currency_code }}</p>
                @if ($exchangeRate)
                    <p class="mt-1 text-sm text-muted">{{ __('Current indicative rate: 1 :base ≈ :rate CNY', ['base' => $country->currency_code, 'rate' => number_format((float) $exchangeRate->rate, 4)]) }}</p>
                @endif
            </div>
        @endif

        @if ($momoProviders->isNotEmpty())
            <div class="rounded-2xl border border-app p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-faint">{{ __('Mobile Money providers') }}</p>
                <ul class="mt-2 space-y-1 text-sm text-body">
                    @foreach ($momoProviders as $momo)
                        <li class="flex items-center gap-2"><x-icon name="check-circle" class="h-4 w-4 text-emerald-500" /> {{ ucfirst($momo->provider) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @if ($paymentMethods->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-xl font-bold text-strong">{{ __('Payment methods available in :country', ['country' => $country->name]) }}</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach ($paymentMethods as $method)
                    <div class="flex items-center gap-2.5 rounded-xl border border-app px-4 py-3 text-sm font-medium text-body">
                        <x-icon name="card" class="h-4 w-4 text-brand-500" /> {{ $method->name }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($walletTypes->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-xl font-bold text-strong">{{ __('China wallets you can fund from :country', ['country' => $country->name]) }}</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($walletTypes as $type)
                    <span class="pill surface border border-app px-3.5 py-1.5">{{ $type->name }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-10 rounded-3xl border border-app p-6">
        <h2 class="text-lg font-bold text-strong">{{ __('Verification and limits') }}</h2>
        <p class="mt-2 text-sm text-body">
            {{ __('Verification tiers and transaction limits are the same across every supported country. See the full breakdown on the') }}
            <a href="{{ route('public.fees') }}" class="font-semibold text-brand-500 hover:text-brand-600">{{ __('Fees & rates page') }}</a>.
        </p>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('public.fund') }}" class="btn btn-primary">{{ __('Start funding a China wallet') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
        <a href="{{ route('shop.index') }}" class="btn btn-ghost">{{ __('Browse the shop') }}</a>
        <a href="{{ route('guides.index') }}" class="btn btn-ghost">{{ __('Read the China buying guides') }}</a>
    </div>
</div>
@endsection
