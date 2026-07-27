@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'Travel eSIMs · '.config('platform.name'))
@section('page-title', __('Travel eSIMs'))

@php
    $scopeTabs = ['local' => __('Local'), 'regional' => __('Regional'), 'global' => __('Global')];
@endphp

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">{{ __('Stay connected while you travel') }}</h1>
            <p class="mt-1 text-sm text-muted">{{ __('Pick a destination and install your eSIM in minutes, no physical SIM card needed.') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('esim.compatibility.index') }}" class="btn btn-ghost text-sm"><x-icon name="sim" class="h-4 w-4" /> {{ __('Check device compatibility') }}</a>
            @auth
                <a href="{{ route('esim.mine.index') }}" class="btn btn-ghost text-sm"><x-icon name="receipt" class="h-4 w-4" /> {{ __('My eSIMs') }}</a>
            @endauth
        </div>
    </div>

    <form method="GET" class="mt-6">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
            <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('Search a country, e.g. China, USA…') }}" class="field pl-11">
        </div>
        @if ($scope)
            <input type="hidden" name="scope" value="{{ $scope }}">
        @endif
    </form>

    @if ($destinations->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($destinations as $d)
                <a href="{{ route('esim.index', ['q' => $d->name]) }}" class="pill bg-slate-500/10 text-body hover:bg-slate-500/20">
                    {{ $d->flag_emoji ?? '🌍' }} {{ $d->name }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="no-scrollbar mt-6 flex gap-1.5 overflow-x-auto rounded-2xl border border-app p-1.5">
        <a href="{{ route('esim.index', array_filter(['q' => $q ?: null])) }}"
           class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-medium {{ ! $scope ? 'bg-brand-500 text-white' : 'text-muted hover:surface-2' }}">
            {{ __('All') }} <span class="opacity-70">({{ array_sum($scopeCounts) }})</span>
        </a>
        @foreach ($scopeTabs as $key => $label)
            <a href="{{ route('esim.index', array_filter(['scope' => $key, 'q' => $q ?: null])) }}"
               class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-medium {{ $scope === $key ? 'bg-brand-500 text-white' : 'text-muted hover:surface-2' }}">
                {{ $label }} <span class="opacity-70">({{ $scopeCounts[$key] }})</span>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($products as $product)
            @include('shop._product', ['product' => $product])
        @empty
            <div class="sm:col-span-2 lg:col-span-3">
                <x-empty icon="sim" title="{{ __('No eSIM plans match your search') }}" message="{{ __('Try a different destination, or browse all plans.') }}">
                    <x-slot:action>
                        <a href="{{ route('esim.index') }}" class="btn btn-primary">{{ __('View all eSIM plans') }}</a>
                    </x-slot:action>
                </x-empty>
            </div>
        @endforelse
    </div>
</div>
@endsection
