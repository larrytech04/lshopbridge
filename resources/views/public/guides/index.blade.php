@extends('layouts.public')
@section('title', 'China buying academy · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-none space-y-8 px-4 pb-16 pt-10 sm:px-6">
    {{-- Hero — same banner as the dashboard Learning Center --}}
    <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-brand-900 p-8 text-white sm:p-10">
        <video class="absolute inset-0 h-full w-full object-cover" src="{{ asset('assets/'.rawurlencode('learning section.mp4')) }}" autoplay muted loop playsinline preload="auto"></video>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-brand-900 via-brand-900/85 to-brand-900/40"></div>
        <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-accent-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-12 -left-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
        <div class="relative z-10 max-w-2xl">
            <span class="pill bg-white/15 text-white ring-1 ring-white/20">{{ __('Free academy') }}</span>
            <h1 class="mt-3 text-2xl font-extrabold text-white sm:text-3xl">{{ __('The China Buying Academy') }}</h1>
            <p class="mt-2 text-sm text-white/80 sm:text-base">{{ __('A complete, plain-language course on shopping every major China platform and getting your goods home, written so a total beginner can follow along, step by step.') }}</p>
            <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm font-medium text-white/85">
                <span class="flex items-center gap-1.5"><x-icon name="book" class="h-4 w-4" /> {{ __(':n guides', ['n' => $totalGuides]) }}</span>
                <span class="flex items-center gap-1.5"><x-icon name="globe" class="h-4 w-4" /> {{ __('9 shopping platforms') }}</span>
                <span class="flex items-center gap-1.5"><x-icon name="check-circle" class="h-4 w-4" /> {{ __('No prior experience needed') }}</span>
            </div>
        </div>
    </div>

    {{-- Category filter --}}
    <div class="no-scrollbar flex items-center gap-2 overflow-x-auto pb-1">
        <a href="{{ route('guides.index') }}" class="shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-semibold transition {{ !$category ? 'border-slate-900 bg-slate-900 text-white' : 'border-app text-body hover:surface-2' }}">{{ __('All') }}</a>
        @foreach ($allCategories as $c)
            @php $meta = guide_category_meta($c); @endphp
            <a href="{{ route('guides.index', ['category' => $c]) }}" class="flex shrink-0 items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-semibold transition {{ $category === $c ? 'border-slate-900 bg-slate-900 text-white' : 'border-app text-body hover:surface-2' }}">
                <x-guide-icon :category="$c" size="h-4 w-4" :colored="false" /> {{ $meta['label'] }}
            </a>
        @endforeach
    </div>

    @php
        $sectionCopy = [
            'start' => ['title' => __('Start here'), 'subtitle' => __("New to shopping from China? Read this one first.")],
            'platforms' => ['title' => __('Choose your platform'), 'subtitle' => __('A full, step-by-step course for every major China shopping site.')],
            'payments' => ['title' => __('Set up your wallet'), 'subtitle' => __('Almost every platform needs one of these two wallets.')],
            'logistics' => ['title' => __('Shipping & customs'), 'subtitle' => __('How your goods actually get from China to your door.')],
            'safety' => ['title' => __('Shop safely'), 'subtitle' => __('Learn from other buyers\' mistakes before you make your first order.')],
            'reference' => ['title' => __('Reference'), 'subtitle' => __('Quick lookups to keep handy while you shop.')],
        ];
    @endphp

    @forelse ($grouped as $section => $guides)
        <div>
            <div class="mb-4">
                <h2 class="text-lg font-bold text-strong">{{ $sectionCopy[$section]['title'] ?? ucfirst($section) }}</h2>
                <p class="text-sm text-muted">{{ $sectionCopy[$section]['subtitle'] ?? '' }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($guides as $guide)
                    <a href="{{ route('guides.show', $guide) }}" class="group flex flex-col rounded-3xl border border-app p-5 transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start justify-between gap-3">
                            <x-guide-icon :category="$guide->category" />
                            @if ($guide->is_featured)<span class="pill bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30">{{ __('Start here') }}</span>@endif
                        </div>
                        <h3 class="mt-4 font-semibold text-strong group-hover:text-brand-500">{{ __($guide->title) }}</h3>
                        <p class="mt-1.5 line-clamp-2 flex-1 text-sm text-muted">{{ __($guide->excerpt) }}</p>
                        <div class="mt-4 flex items-center gap-3 text-xs font-medium text-faint">
                            <span class="flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ __(':n min read', ['n' => $guide->read_minutes]) }}</span>
                            @if (!empty($guide->steps))<span class="flex items-center gap-1"><x-icon name="list" class="h-3.5 w-3.5" /> {{ __(':n steps', ['n' => count($guide->steps)]) }}</span>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <x-empty icon="book" title="{{ __('No guides yet') }}" message="{{ __('Check back soon.') }}" />
    @endforelse
</section>
@endsection
