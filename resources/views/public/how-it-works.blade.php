@extends('layouts.public')
@section('title', 'How it works · '.config('platform.name'))

@php
    // $fundSteps, $shopSteps, $promises now come from PageController::howItWorks()
    // (Admin -> Page content -> How It Works), not hardcoded here.
    $brand = setting('site_name', config('platform.name'));
@endphp

@section('content')
{{-- Breadcrumb --}}
<div class="mx-auto max-w-6xl px-4 pt-6 sm:px-6">
    <nav class="flex flex-wrap items-center gap-2 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('home') }}" class="hover:text-strong">{{ __('Home') }}</a>
        <x-img-icon name="Arrow-Button-Right-3--Streamline-Ultimate.png" class="h-3 w-3 text-faint" />
        <span class="font-semibold text-strong">{{ __('How it works') }}</span>
    </nav>
</div>

{{-- Hero --}}
<section class="mx-auto max-w-3xl px-4 pb-4 pt-10 text-center sm:px-6">
    <span class="pill surface border border-app text-body"><span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span> {{ __('The full journey') }}</span>
    <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-strong sm:text-5xl">{{ __('How :brand works', ['brand' => $brand]) }}</h1>
    <p class="mx-auto mt-4 max-w-2xl text-lg text-body">{{ __('From your local Mobile Money to a funded China wallet, and instant digital products delivered to your account. Fully automated, secure and transparent.') }}</p>
</section>

{{-- Journey 1: Funding a China wallet --}}
<section class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <div class="mb-12 text-center">
        <span class="text-xs font-bold uppercase tracking-widest text-brand-500">{{ __('Journey 1') }}</span>
        <h2 class="mt-2 text-3xl font-bold text-strong sm:text-4xl">{{ __('Funding a China wallet') }}</h2>
        <p class="mx-auto mt-3 max-w-2xl text-body">{{ __('Top up Alipay, WeChat Pay, UnionPay or QQ from anywhere in Africa, no Chinese bank account needed.') }}</p>
    </div>
    <x-journey :steps="$fundSteps" :start="__('Start')" :end="__('Wallet funded')" />
    <div class="mt-12 text-center">
        <a href="{{ route('funding.create') }}" class="btn btn-primary px-7 py-3 text-base">{{ __('Fund a wallet') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
    </div>
</section>

{{-- Journey 2: Shopping gift cards & eSIMs --}}
<section class="w-full surface border-y border-app">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="mb-12 text-center">
            <span class="text-xs font-bold uppercase tracking-widest text-brand-500">{{ __('Journey 2') }}</span>
            <h2 class="mt-2 text-3xl font-bold text-strong sm:text-4xl">{{ __('Shopping gift cards & eSIMs') }}</h2>
            <p class="mx-auto mt-3 max-w-2xl text-body">{{ __('Buy gift cards, eSIMs, top-ups and more, delivered to your account in seconds.') }}</p>
        </div>
        <x-journey :steps="$shopSteps" :start="__('Start')" :end="__('Delivered')" />
        <div class="mt-12 text-center">
            <a href="{{ route('shop.index') }}" class="btn btn-primary px-7 py-3 text-base">{{ __('Open the shop') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
    </div>
</section>

{{-- Why it works --}}
<section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-3xl font-bold text-strong sm:text-4xl">{{ __('Why it just works') }}</h2>
        <p class="mt-3 text-body">{{ __('The same promises behind every payment and every order.') }}</p>
    </div>
    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($promises as [$icon, $title, $body])
            <div class="rounded-3xl border border-app card-solid p-6 shadow-sm">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-slate-500/12 text-brand-600"><x-icon :name="$icon" class="h-6 w-6" /></span>
                <h3 class="mt-4 font-bold text-strong">{{ $title }}</h3>
                <p class="mt-1.5 text-sm text-muted">{{ $body }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- CTA --}}
<section class="w-full bg-brand-900 text-white">
    <div class="mx-auto max-w-4xl px-4 py-16 text-center sm:px-6">
        <h2 class="text-3xl font-bold sm:text-4xl">{{ __('Ready to start?') }}</h2>
        <p class="mx-auto mt-3 max-w-xl text-white/75">{{ __('Create a free account and send your first payment, or grab your first gift card, in minutes.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-base font-semibold text-brand-700 hover:bg-white/90">{{ __('Create free account') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
            <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-6 py-3 text-base font-semibold backdrop-blur hover:bg-white/25">{{ __('Talk to us') }}</a>
        </div>
    </div>
</section>

{{-- Scroll-driven road fill: the dashed line fills solid from step 1 → last as you scroll. --}}
<script>
    (function () {
        document.querySelectorAll('[data-journey]').forEach(function (box) {
            const fills = Array.prototype.slice.call(box.querySelectorAll('.journey-fill'));
            fills.forEach(function (p) {
                let len = 0;
                try { len = p.getTotalLength(); } catch (e) {}
                p._len = len;
                if (len) { p.style.strokeDasharray = len; p.style.strokeDashoffset = len; }
            });
            function update() {
                const r = box.getBoundingClientRect();
                const vh = window.innerHeight || document.documentElement.clientHeight;
                const prog = Math.max(0, Math.min(1, (vh * 0.82 - r.top) / (r.height + vh * 0.5)));
                fills.forEach(function (p) {
                    if (!p._len) { try { p._len = p.getTotalLength(); if (p._len) p.style.strokeDasharray = p._len; } catch (e) {} }
                    if (p._len) { p.style.strokeDashoffset = p._len * (1 - prog); }
                });
            }
            update();
            window.addEventListener('scroll', update, { passive: true });
            window.addEventListener('resize', update);
        });
    })();
</script>
@endsection
