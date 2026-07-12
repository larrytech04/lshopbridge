@extends('layouts.public')
@section('title', 'FAQs · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-3xl px-4 pt-20 sm:px-6">
    <div class="text-center">
        <h1 class="text-4xl font-extrabold text-strong sm:text-5xl">{{ __('Frequently asked questions') }}</h1>
        <p class="mt-4 text-lg text-body">{{ __('Everything you need to know about funding China wallets.') }}</p>
    </div>

    @forelse ($faqs as $category => $items)
        <div class="mt-12">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-brand-300">{{ ucfirst($category) }}</h2>
            <div class="space-y-3" x-data="{ open: null }">
                @foreach ($items as $faq)
                    <div class="glass rounded-2xl">
                        <button @click="open === {{ $faq->id }} ? open = null : open = {{ $faq->id }}" class="flex w-full items-center justify-between gap-4 p-5 text-left">
                            <span class="font-medium text-strong">{{ $faq->question }}</span>
                            <x-icon name="chevron-down" class="h-5 w-5 shrink-0 text-muted" ::class="open === {{ $faq->id }} ? 'rotate-180' : ''" />
                        </button>
                        <div x-show="open === {{ $faq->id }}" x-collapse style="display:none"><p class="px-5 pb-5 text-sm text-muted">{{ $faq->answer }}</p></div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="mt-12"><x-empty icon="info" title="{{ __('No FAQs yet') }}" message="Check back soon." /></div>
    @endforelse

    <div class="mt-14 text-center">
        <p class="text-muted">{{ __('Still have questions?') }}</p>
        <a href="{{ route('contact') }}" class="btn btn-ghost mt-3">{{ __('Contact support') }}</a>
    </div>
</section>
@endsection
