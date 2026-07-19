@extends('layouts.public')
@section('title', 'Payment methods · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-5xl px-4 pt-16 text-center sm:px-6">
    <h1 class="text-4xl font-extrabold text-strong sm:text-5xl">{{ cms('cms_pmpage_title', __('Accepted payment methods')) }}</h1>
    <p class="mx-auto mt-4 max-w-2xl text-lg text-body">{{ cms('cms_pmpage_subtitle', __('Top up your wallet using the channels you already trust, mobile money, cards, bank transfer, USSD & crypto, accepted across Africa.')) }}</p>
    <div class="mt-5 flex flex-wrap items-center justify-center gap-3 text-xs font-medium text-muted">
        <span class="inline-flex items-center gap-1.5"><x-icon name="check-circle" class="h-4 w-4 text-emerald-500" /> {{ __('Instant Delivery') }}</span>
        <span class="inline-flex items-center gap-1.5"><x-icon name="shield" class="h-4 w-4 text-brand-500" /> {{ __('Secure & encrypted') }}</span>
        <span class="inline-flex items-center gap-1.5"><x-icon name="globe" class="h-4 w-4 text-brand-500" /> {{ __('40+ African countries') }}</span>
    </div>
</section>

<section class="mx-auto mt-14 max-w-5xl px-4 pb-4 sm:px-6">
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
<section class="mx-auto max-w-5xl px-4 pb-4 sm:px-6">
    <div class="glass rounded-3xl p-6 sm:p-8">
        <h2 class="text-lg font-bold text-strong">{{ __('How your top-up is processed') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @forelse ($methods as $m)
                <div class="flex items-start gap-3 rounded-2xl border border-app surface p-4">
                    <x-icon :name="match($m->type){'momo'=>'phone-device','bank'=>'building','crypto'=>'bitcoin','card'=>'card',default=>'wallet'}" class="mt-0.5 h-6 w-6 shrink-0 text-brand-500" />
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-strong">{{ $m->name }}</h3>
                            @if ($m->is_automated)<span class="pill bg-emerald-500/15 text-emerald-500 ring-1 ring-emerald-400/30">{{ __('Instant') }}</span>@else<span class="pill bg-amber-500/15 text-amber-500 ring-1 ring-amber-400/30">{{ __('Manual review') }}</span>@endif
                        </div>
                        @if ($m->description)<p class="mt-1 text-sm text-muted">{{ $m->description }}</p>@endif
                        <p class="mt-1 text-xs text-faint">{{ __('Min') }} {{ money($m->min_amount, $m->currency ?? config('platform.base_currency')) }}@if($m->max_amount) · {{ __('Max') }} {{ money($m->max_amount, $m->currency ?? config('platform.base_currency')) }}@endif</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted">{{ __('Payment methods will appear here once an admin adds them.') }}</p>
            @endforelse
        </div>
    </div>
</section>

<div class="mx-auto max-w-5xl px-4 py-12 text-center sm:px-6">
    <a href="{{ route('register') }}" class="btn btn-primary px-6 py-3">{{ __('Create account to deposit') }}</a>
</div>
@endsection
