@extends('layouts.public')
@section('title', 'Payment methods · '.config('platform.name'))
@section('meta_description', __('Browse the payment methods available on :name for depositing and funding your wallet, including Mobile Money and other options enabled for your country.', ['name' => config('platform.name')]))

@push('structured-data')
    {!! \App\Services\Seo\StructuredDataBuilder::scriptTag(app(\App\Services\Seo\StructuredDataBuilder::class)->breadcrumbList([
        ['name' => __('Home'), 'url' => app(\App\Services\Seo\CanonicalUrlService::class)->normalize(route('home'))],
        ['name' => __('Payment methods'), 'url' => app(\App\Services\Seo\CanonicalUrlService::class)->normalize(route('public.payment-methods'))],
    ])) !!}
@endpush

@section('content')
<section class="mx-auto max-w-none px-4 pt-20 text-center sm:px-6">
    <h1 class="text-5xl font-extrabold tracking-tight text-strong sm:text-6xl">{{ cms('cms_pmpage_title', __('Accepted payment methods')) }}</h1>
    <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-body sm:text-xl">{{ cms('cms_pmpage_subtitle', __('Top up your LshopBridge wallet using the channels you already trust, mobile money, cards, bank transfer, USSD and crypto, all accepted across Africa.')) }}</p>
    <div class="mt-7 flex flex-wrap items-center justify-center gap-3 text-sm font-medium text-body">
        <span class="pill surface border border-app px-3.5 py-1.5"><x-icon name="check-circle" class="h-4 w-4 text-emerald-500" /> {{ __('Instant Delivery') }}</span>
        <span class="pill surface border border-app px-3.5 py-1.5"><x-icon name="shield" class="h-4 w-4 text-brand-500" /> {{ __('Secure & encrypted') }}</span>
        <a href="{{ route('countries.index') }}" class="pill surface border border-app px-3.5 py-1.5 transition hover:-translate-y-0.5"><x-icon name="globe" class="h-4 w-4 text-brand-500" /> {{ __('40+ African countries') }}</a>
    </div>
</section>

<section class="mx-auto mt-14 max-w-none px-4 pb-4 sm:px-6">
    @foreach (config('payments.accepted') as $group => $items)
        <div class="mb-10">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-faint">{{ __($group) }}</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($items as [$key, $name])
                    <div class="flex items-center gap-3 rounded-2xl surface p-4 ring-1 ring-app">
                        <x-pay-icon :name="$key" class="h-9 w-9 shrink-0 shadow-sm" />
                        <span class="text-sm font-semibold text-strong">{{ __($name) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</section>

{{-- Operational note: how funding actually settles --}}
<section class="mx-auto mt-20 max-w-none px-4 pb-20 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ __('How your top-up is processed') }}</h2>
        <p class="mt-2 text-body">{{ __('Every method below is live and configured, with real limits and processing times.') }}</p>
    </div>
    @if ($methods->isNotEmpty())
        <div class="mx-auto mt-10 grid max-w-6xl gap-5 sm:grid-cols-2">
            @foreach ($methods as $m)
                <div class="flex items-start gap-4 rounded-2xl border border-app surface p-5">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-brand-500/10 text-brand-600">
                        <x-icon :name="match($m->type){'momo'=>'phone-device','bank'=>'building','crypto'=>'bitcoin','card'=>'card',default=>'wallet'}" class="h-6 w-6" />
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-strong">{{ $m->name }}</h3>
                            @if ($m->is_automated)<span class="pill bg-emerald-500/15 text-emerald-500 ring-1 ring-emerald-400/30">{{ __('Instant') }}</span>@else<span class="pill bg-amber-500/15 text-amber-500 ring-1 ring-amber-400/30">{{ __('Manual review') }}</span>@endif
                        </div>
                        @if ($m->description)<p class="mt-1.5 text-sm leading-relaxed text-muted">{{ $m->description }}</p>@endif
                        <p class="mt-1.5 text-xs text-faint">{{ __('Min') }} {{ money($m->min_amount, $m->currency ?? config('platform.base_currency')) }}@if($m->max_amount) · {{ __('Max') }} {{ money($m->max_amount, $m->currency ?? config('platform.base_currency')) }}@endif</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="mx-auto mt-10 max-w-md">
            <x-empty icon="wallet" title="{{ __('No payment methods yet') }}" message="{{ __('Payment methods will appear here once an admin adds them.') }}" />
        </div>
    @endif
</section>

@endsection
