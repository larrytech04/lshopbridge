@extends('layouts.public')
@php
    // Admin-entered overrides from the seo_metadata table (see
    // admin/partials/seo-fields.blade.php + SeoMetadataController) win when
    // set; otherwise fall back to the sensible computed default below —
    // same precedence as every other model-backed page in this app.
    $agentSeo = $agent->seoMetadata;
@endphp
@section('title', $agentSeo?->meta_title ?: $agent->business_name.' · Shipping agent')
@section('meta_description', $agentSeo?->meta_description ?: __(':name is a verified shipping agent on :platform, helping you import and ship goods from China.', ['name' => $agent->business_name, 'platform' => config('platform.name')]))
@if ($agentSeo?->robots)
    @section('robots', $agentSeo->robots)
@endif
@if ($agentSeo?->canonical_override)
    @section('canonical', app(\App\Services\Seo\CanonicalUrlService::class)->fromOverride($agentSeo->canonical_override))
@endif

@push('structured-data')
    {!! \App\Services\Seo\StructuredDataBuilder::scriptTag($breadcrumbSchema) !!}
@endpush

@php
    $contactUrl = route('marketplace.show', $agent);
    $rates = $agent->shippingRates->where('is_active', true);
    $leadMin = $rates->min('estimated_days_min');
    $leadMax = $rates->max('estimated_days_max');
    $positive = $agent->rating > 0 ? round($agent->rating / 5 * 100, 2) : 100;
    $positiveLabel = rtrim(rtrim(number_format($positive, 2), '0'), '.');
@endphp

@section('content')
<section class="mx-auto max-w-none px-4 pt-16 sm:px-6" x-data="{ tab: 'info' }">
    <a href="{{ route('agents.index') }}" class="text-sm text-brand-500 hover:text-brand-600">← {{ __('All agents') }}</a>
    <div class="mt-3"><x-breadcrumbs :items="$breadcrumbs" /></div>

    {{-- Header --}}
    <div class="mt-4 flex items-start gap-4">
        <span class="relative grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-brand-600 text-xl font-bold text-white">
            @if ($agent->logo_path)<img src="{{ Storage::url($agent->logo_path) }}" class="h-full w-full object-cover" alt="">@else{{ strtoupper(substr($agent->business_name, 0, 2)) }}@endif
            <span class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 {{ $agent->isOnline() ? 'bg-emerald-500' : 'bg-slate-400' }}" style="border-color: var(--bg);" title="{{ $agent->isOnline() ? __('Online now') : __('Offline') }}"></span>
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <h1 class="truncate text-2xl font-extrabold text-strong">{{ $agent->business_name }}</h1>
                @if ($agent->verified_at)<x-verified-tick class="h-5 w-5 shrink-0" />@endif
            </div>
            <p class="mt-1 flex items-center gap-1.5 text-sm text-muted"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> {{ __('Online') }}</p>
        </div>
        <a href="{{ $contactUrl }}" class="btn btn-primary shrink-0 px-6">{{ __('Contact') }}</a>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-1 border-y border-app py-3 text-sm">
        @if ($agent->verified_at)
            <span class="flex items-center gap-1.5 font-semibold text-strong"><x-verified-tick class="h-4 w-4" /> {{ __('Verified agent') }}</span>
        @endif
        <span class="text-muted"><span class="font-semibold text-strong">{{ number_format($agent->completed_orders) }}</span> {{ __('orders') }}</span>
        <span class="text-muted"><span class="font-semibold text-strong">{{ $agent->reviews_count }}</span> {{ __('reviews') }}</span>
        <span class="flex items-center gap-1 text-muted"><x-icon name="star" class="h-4 w-4 fill-current text-amber-400" /> <span class="font-semibold text-strong">{{ number_format((float) $agent->rating, 1) }}</span></span>
    </div>

    {{-- Tabs --}}
    <div class="mt-6 flex gap-6 border-b border-app text-sm font-semibold">
        @foreach (['info' => __('Info'), 'rates' => __('Rates').' ('.$rates->count().')', 'feedback' => __('Feedback').' ('.$agent->reviews_count.')'] as $key => $label)
            <button type="button" @click="tab = '{{ $key }}'"
                    class="-mb-px border-b-2 pb-3 transition"
                    :class="tab === '{{ $key }}' ? 'border-brand-500 text-strong' : 'border-transparent text-muted hover:text-strong'">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Info tab --}}
    <div x-show="tab === 'info'" class="mt-4">
        @if ($agent->bio)<p class="mb-5 text-sm text-body">{{ $agent->bio }}</p>@endif
        @php
            $stats = array_filter([
                [__('30-day orders'), number_format($agent->completed_orders)],
                [__('Positive feedback'), $positiveLabel.'%'],
                [__('Rating'), number_format((float) $agent->rating, 1).' / 5'],
                $leadMin ? [__('Avg lead time'), $leadMin.'–'.$leadMax.' '.__('days')] : null,
                [__('Trade type'), __('Procurement & Freight')],
                [__('Member points'), number_format($agent->points)],
                [__('Registered'), $agent->created_at?->diffForHumans(null, true).' '.__('ago')],
                [__('Warehouse'), trim(($agent->warehouse_city ? $agent->warehouse_city.', ' : '').($agent->warehouseCountry?->name ?? 'China'))],
            ]);
        @endphp
        <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as [$label, $value])
                <div class="rounded-2xl border border-app card-solid p-4">
                    <dt class="text-xs text-muted">{{ $label }}</dt>
                    <dd class="mt-1 text-lg font-bold text-strong">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
        @if ($agent->countries->isNotEmpty())
            <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-faint">{{ __('Coverage') }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($agent->countries as $c)<span class="pill surface text-body ring-1 ring-app">{{ $c->flag_emoji }} {{ $c->name }}</span>@endforeach
            </div>
        @endif
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach (($agent->shipping_methods ?? []) as $m)<span class="pill surface text-body ring-1 ring-app">{{ ucfirst($m) }} {{ __('freight') }}</span>@endforeach
        </div>
    </div>

    {{-- Rates tab --}}
    <div x-show="tab === 'rates'" x-cloak class="mt-4">
        @if ($rates->isNotEmpty())
            <div class="overflow-x-auto rounded-2xl border border-app card-solid">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-app text-muted"><tr>
                        <th class="px-5 py-3 font-medium">{{ __('Method') }}</th><th class="px-5 py-3 font-medium">{{ __('Destination') }}</th><th class="px-5 py-3 font-medium">{{ __('Price') }}</th><th class="px-5 py-3 font-medium">{{ __('ETA') }}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-app">
                        @foreach ($rates as $rate)
                            <tr>
                                <td class="px-5 py-3 font-medium text-strong">{{ ucfirst($rate->method) }}</td>
                                <td class="px-5 py-3 text-body">{{ $rate->destinationCountry?->name ?? __('Various') }}</td>
                                <td class="px-5 py-3 text-body">@if($rate->price_per_kg){{ money($rate->price_per_kg, $rate->currency) }}/kg @endif @if($rate->price_per_cbm){{ money($rate->price_per_cbm, $rate->currency) }}/cbm @endif</td>
                                <td class="px-5 py-3 text-body">{{ $rate->estimated_days_min }}–{{ $rate->estimated_days_max }} {{ __('days') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty icon="truck" title="{{ __('No published rates') }}" message="Contact the agent for a quote." />
        @endif
    </div>

    {{-- Feedback tab --}}
    <div x-show="tab === 'feedback'" x-cloak class="mt-4 space-y-3">
        @forelse ($reviews as $review)
            <div class="rounded-2xl border border-app card-solid p-5">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-strong">{{ $review->reviewerName() }}</span>
                    <span class="flex items-center gap-0.5 text-amber-400">@for($i=0;$i<$review->rating;$i++)<x-icon name="star" class="h-4 w-4 fill-current" />@endfor</span>
                </div>
                @if ($review->comment)<p class="mt-2 text-sm text-muted">{{ __($review->comment) }}</p>@endif
            </div>
        @empty
            <x-empty icon="star" title="{{ __('No reviews yet') }}" message="Be the first to review this agent after an order." />
        @endforelse

        <div class="rounded-2xl border border-dashed border-app p-5" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left">
                <span class="text-sm font-semibold text-strong">{{ __('Dealt with this agent off-platform? Leave feedback') }}</span>
                <x-icon name="chevron-down" class="h-4 w-4 text-faint" />
            </button>
            <form method="POST" action="{{ route('agents.guest-review', $agent) }}" x-show="open" x-cloak class="mt-4 space-y-3">
                @csrf
                <x-honeypot />
                <x-form-timing form-type="review_feedback" />
                <p class="text-xs text-faint">{{ __('This feedback is unverified and held for review before it appears.') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="label">{{ __('Your name (optional)') }}</label><input name="guest_name" class="field"></div>
                    <div><label class="label">{{ __('Your email (optional)') }}</label><input name="guest_email" type="email" class="field"></div>
                </div>
                <div>
                    <label class="label">{{ __('Rating') }}</label>
                    <select name="rating" class="field" required>
                        @for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ $i }} {{ __('star') }}{{ $i > 1 ? 's' : '' }}</option>@endfor
                    </select>
                </div>
                <div><label class="label">{{ __('Comment (optional)') }}</label><textarea name="comment" rows="3" class="field"></textarea></div>
                <x-turnstile action="review_feedback" />
                <button class="btn btn-ghost w-full">{{ __('Submit feedback') }}</button>
            </form>
        </div>
    </div>

    <div class="mt-10 mb-4 rounded-2xl border border-app card-solid p-6 text-center">
        <h3 class="font-bold text-strong">{{ __('Ready to work with :name?', ['name' => $agent->business_name]) }}</h3>
        <p class="mt-1 text-sm text-muted">{{ __('Start a conversation from your dashboard, share your order and get a quote.') }}</p>
        <a href="{{ $contactUrl }}" class="btn btn-primary mt-4 px-6 py-2.5">{{ __('Contact') }} {{ $agent->business_name }}</a>
    </div>
</section>
@endsection
