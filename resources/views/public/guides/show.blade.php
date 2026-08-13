@extends('layouts.public')
@section('title', ($guide->meta_title ?: $guide->title).' · '.config('platform.name'))
@section('meta_description', $guide->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($guide->excerpt ?: $guide->body ?: ''), 160))
@section('og_image', $guide->cover_image_path ? Storage::url($guide->cover_image_path) : null)

@push('structured-data')
    {!! \App\Services\Seo\StructuredDataBuilder::scriptTag($breadcrumbSchema) !!}
    {!! \App\Services\Seo\StructuredDataBuilder::scriptTag($articleSchema) !!}
@endpush

@php $meta = guide_category_meta($guide->category); @endphp

@section('content')
<div class="mx-auto max-w-[1200px] px-4 pt-10 pb-16 sm:px-6">
    <a href="{{ route('guides.index') }}" class="text-sm text-brand-500 hover:text-brand-600">← {{ __('Back to academy') }}</a>
    <div class="mt-3"><x-breadcrumbs :items="$breadcrumbs" /></div>

    <div class="mt-4 grid gap-6 lg:grid-cols-[1fr_18rem]">
        {{-- Reading column --}}
        <article class="min-w-0 space-y-8">
            {{-- Header --}}
            <div>
                <div class="flex items-center gap-3">
                    <x-guide-icon :category="$guide->category" size="h-12 w-12" />
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-faint">{{ $meta['label'] }}</p>
                        <h1 class="text-2xl font-extrabold text-strong sm:text-3xl">{{ $guide->title }}</h1>
                    </div>
                </div>
                @if ($guide->excerpt)<p class="mt-4 max-w-2xl text-lg text-body">{{ $guide->excerpt }}</p>@endif
                <div class="mt-4 flex flex-wrap items-center gap-4 text-xs font-medium text-faint">
                    <span class="flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ __(':n min read', ['n' => $guide->read_minutes]) }}</span>
                    @if (!empty($guide->steps))<span class="flex items-center gap-1"><x-icon name="list" class="h-3.5 w-3.5" /> {{ __(':n steps', ['n' => count($guide->steps)]) }}</span>@endif
                    <span class="flex items-center gap-1"><x-icon name="eye" class="h-3.5 w-3.5" /> {{ __(':n reads', ['n' => number_format($guide->views)]) }}</span>
                </div>
            </div>

            @if ($guide->cover_image_path)<img src="{{ Storage::url($guide->cover_image_path) }}" class="w-full rounded-3xl" alt="{{ $guide->title }}" loading="lazy">@endif

            @if ($guide->video_url)
                <div class="aspect-video overflow-hidden rounded-3xl">
                    <iframe src="{{ $guide->video_url }}" class="h-full w-full" frameborder="0" allowfullscreen></iframe>
                </div>
            @endif

            @if ($guide->body)
                <p id="intro" class="scroll-mt-24 max-w-2xl rounded-2xl border border-app bg-brand-500/5 p-5 leading-relaxed text-body">{{ $guide->body }}</p>
            @endif

            {{-- Step-by-step "chapters" --}}
            @if (!empty($guide->steps))
                <div class="space-y-5">
                    @foreach ($guide->steps as $i => $step)
                        <div id="step-{{ $i+1 }}" class="scroll-mt-24 rounded-3xl border border-app p-6">
                            <div class="flex gap-4">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-600 font-bold text-white">{{ $i + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-lg font-bold text-strong">{{ $step['title'] ?? '' }}</h3>
                                    <p class="mt-2 max-w-2xl leading-relaxed text-body">{{ $step['body'] ?? '' }}</p>
                                    @if (!empty($step['tip']))
                                        <div class="mt-3 flex max-w-2xl gap-2.5 rounded-xl border border-amber-400/30 bg-amber-500/10 p-3.5 text-sm text-amber-700">
                                            <x-icon name="lightbulb" class="mt-0.5 h-4 w-4 shrink-0" />
                                            <p><span class="font-semibold">{{ __('Tip:') }}</span> {{ $step['tip'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- FAQs --}}
            @if (!empty($guide->faqs))
                <div id="faqs" class="scroll-mt-24 rounded-3xl border border-app p-6">
                    <h3 class="font-bold text-strong">{{ __('Frequently asked questions') }}</h3>
                    <div class="mt-3 divide-y divide-app">
                        @foreach ($guide->faqs as $faq)
                            <details class="group py-3">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-strong">
                                    {{ $faq['q'] ?? '' }}
                                    <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-faint transition group-open:rotate-180" />
                                </summary>
                                <p class="mt-2 text-sm leading-relaxed text-muted">{{ $faq['a'] ?? '' }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($guide->cta_label && $guide->cta_url)
                <div class="rounded-2xl border border-app bg-slate-500/15 p-6 text-center">
                    <a href="{{ $guide->cta_url }}" class="btn btn-primary px-6 py-3">{{ $guide->cta_label }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
                </div>
            @endif

            @include('public.guides._feedback', ['guide' => $guide, 'alreadyVoted' => $alreadyVoted])

            {{-- Prev / next, turn the page --}}
            @if ($prev || $next)
                <div class="grid gap-3 sm:grid-cols-2">
                    @if ($prev)
                        <a href="{{ route('guides.show', $prev) }}" class="rounded-2xl border border-app p-4 transition hover:surface-2">
                            <p class="text-xs font-semibold text-faint">← {{ __('Previous') }}</p>
                            <p class="mt-1 font-semibold text-strong">{{ $prev->title }}</p>
                        </a>
                    @else
                        <span></span>
                    @endif
                    @if ($next)
                        <a href="{{ route('guides.show', $next) }}" class="rounded-2xl border border-app p-4 text-right transition hover:surface-2">
                            <p class="text-xs font-semibold text-faint">{{ __('Next') }} →</p>
                            <p class="mt-1 font-semibold text-strong">{{ $next->title }}</p>
                        </a>
                    @endif
                </div>
            @endif

            @if ($related->isNotEmpty())
                <div>
                    <h3 class="mb-3 font-bold text-strong">{{ __('More in :category', ['category' => $meta['label']]) }}</h3>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ($related as $r)
                            <a href="{{ route('guides.show', $r) }}" class="rounded-2xl border border-app p-4 transition hover:surface-2">
                                <p class="text-sm font-semibold text-strong">{{ $r->title }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </article>

        {{-- Contents sidebar (desktop) --}}
        <aside class="hidden lg:block">
            <div class="sticky top-24 space-y-4 rounded-3xl border border-app p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-faint">{{ __('On this page') }}</p>
                <nav class="space-y-2.5 text-sm">
                    @if ($guide->body)<a href="#intro" class="block text-body hover:text-brand-500">{{ __('Overview') }}</a>@endif
                    @foreach ($guide->steps ?? [] as $i => $step)
                        <a href="#step-{{ $i+1 }}" class="block truncate text-body hover:text-brand-500">{{ $i+1 }}. {{ $step['title'] ?? '' }}</a>
                    @endforeach
                    @if (!empty($guide->faqs))<a href="#faqs" class="block text-body hover:text-brand-500">{{ __('FAQs') }}</a>@endif
                </nav>
            </div>
        </aside>
    </div>
</div>
@endsection
