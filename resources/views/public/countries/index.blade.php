@extends('layouts.public')
@section('title', __('Supported Countries').' · '.config('platform.name'))
@section('meta_description', __('Countries where :name offers China wallet funding, digital products and shipping services.', ['name' => config('platform.name')]))

@push('structured-data')
    {!! \App\Services\Seo\StructuredDataBuilder::scriptTag($breadcrumbSchema) !!}
@endpush

@section('content')
<div class="mx-auto max-w-6xl px-4 pt-8 pb-16 sm:px-6">
    <div class="mb-5"><x-breadcrumbs :items="$breadcrumbs" /></div>

    <x-page-header :title="__('Supported Countries')" :subtitle="__('Where you can fund China wallets, shop digital products and ship goods through :name.', ['name' => config('platform.name')])" />

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($countries as $country)
            <a href="{{ route('countries.show', $country) }}" class="flex items-center gap-2.5 rounded-2xl border border-app p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                @if ($country->flag_emoji)<span class="text-2xl" aria-hidden="true">{{ $country->flag_emoji }}</span>@endif
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-strong">{{ $country->name }}</span>
                    @if ($country->hasRealPaymentInfrastructure())
                        <span class="block text-xs text-emerald-600">{{ __('Fully supported') }}</span>
                    @else
                        <span class="block text-xs text-faint">{{ __('Coming soon') }}</span>
                    @endif
                </span>
            </a>
        @endforeach
    </div>
</div>
@endsection
