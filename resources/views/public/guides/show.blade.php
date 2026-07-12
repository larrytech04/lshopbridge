@extends('layouts.public')
@section('title', $guide->title.' · '.config('platform.name'))

@section('content')
<article class="mx-auto max-w-3xl px-4 pt-16 sm:px-6">
    <a href="{{ route('guides.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← {{ __('Back to academy') }}</a>
    <div class="mt-4 flex items-center gap-2 text-xs text-faint">
        <span class="pill surface text-brand-200 ring-1 ring-white/10">{{ ucfirst($guide->category) }}</span> · {{ $guide->read_minutes }} min read · {{ number_format($guide->views) }} views
    </div>
    <h1 class="mt-4 text-3xl font-extrabold text-strong sm:text-4xl">{{ $guide->title }}</h1>
    @if ($guide->excerpt)<p class="mt-3 text-lg text-body">{{ $guide->excerpt }}</p>@endif

    @if ($guide->cover_image_path)
        <img src="{{ Storage::url($guide->cover_image_path) }}" class="mt-8 w-full rounded-2xl" alt="">
    @endif

    @if ($guide->video_url)
        <div class="mt-8 aspect-video overflow-hidden rounded-2xl">
            <iframe src="{{ $guide->video_url }}" class="h-full w-full" frameborder="0" allowfullscreen></iframe>
        </div>
    @endif

    @if ($guide->body)
        <div class="prose-invert mt-8 space-y-4 text-body leading-relaxed">{!! nl2br(e($guide->body)) !!}</div>
    @endif

    @if (!empty($guide->steps))
        <div class="mt-10 space-y-4">
            <h2 class="text-xl font-bold text-strong">{{ __('Step by step') }}</h2>
            @foreach ($guide->steps as $i => $step)
                <div class="glass flex gap-4 rounded-2xl p-5">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-600 font-bold text-strong">{{ $i + 1 }}</span>
                    <div>
                        <h3 class="font-semibold text-strong">{{ $step['title'] ?? 'Step' }}</h3>
                        <p class="mt-1 text-sm text-muted">{{ $step['body'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if (!empty($guide->faqs))
        <div class="mt-10 space-y-3" x-data="{ open: null }">
            <h2 class="text-xl font-bold text-strong">{{ __('FAQs') }}</h2>
            @foreach ($guide->faqs as $i => $f)
                <div class="glass rounded-2xl">
                    <button @click="open === {{ $i }} ? open = null : open = {{ $i }}" class="flex w-full items-center justify-between gap-4 p-5 text-left">
                        <span class="font-medium text-strong">{{ $f['q'] ?? '' }}</span>
                        <x-icon name="chevron-down" class="h-5 w-5 text-muted" ::class="open === {{ $i }} ? 'rotate-180' : ''" />
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse style="display:none"><p class="px-5 pb-5 text-sm text-muted">{{ $f['a'] ?? '' }}</p></div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($guide->cta_label && $guide->cta_url)
        <div class="mt-10 rounded-2xl border border-app bg-slate-500/15 p-6 text-center">
            <a href="{{ $guide->cta_url }}" class="btn btn-primary px-6 py-3">{{ $guide->cta_label }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
    @endif

    @if ($related->isNotEmpty())
        <div class="mt-14">
            <h2 class="text-xl font-bold text-strong">{{ __('Related guides') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                @foreach ($related as $r)
                    <a href="{{ route('guides.show', $r) }}" class="glass glass-hover rounded-2xl p-4">
                        <p class="text-sm font-semibold text-strong">{{ $r->title }}</p>
                        <p class="mt-1 text-xs text-muted">{{ $r->read_minutes }} min read</p>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</article>
@endsection
