@extends('layouts.app')
@section('page-title', __('Help Center'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-strong sm:text-3xl">{{ __('Help Center') }}</h1>
        <p class="mt-2 text-muted">{{ __('Search answers about deposits, funding, orders and your account.') }}</p>
    </div>

    <form method="GET" class="mx-auto mt-6 max-w-lg">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
            <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('Search the Help Center…') }}" class="field pl-11" onchange="this.form.requestSubmit()">
        </div>
    </form>

    @if ($faqs->isEmpty())
        <div class="mt-10">
            <x-empty icon="help" :title="__('No answers found')" :message="__('Try a different search, or open a support ticket and we\'ll help directly.')">
                <a href="{{ route('disputes.index') }}" class="btn btn-primary">{{ __('Open a Support Ticket') }}</a>
            </x-empty>
        </div>
    @else
        @foreach ($faqs as $category => $items)
            <div class="mt-10">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-brand-400">{{ ucfirst($category) }}</h2>
                <div class="space-y-3" x-data="{ open: null }">
                    @foreach ($items as $faq)
                        <div class="glass rounded-2xl">
                            <button type="button" @click="open === {{ $faq->id }} ? open = null : open = {{ $faq->id }}" class="flex w-full items-center justify-between gap-4 p-5 text-left">
                                <span class="font-medium text-strong">{{ $faq->question }}</span>
                                <x-icon name="chevron-down" class="h-5 w-5 shrink-0 text-muted transition-transform duration-200" ::class="open === {{ $faq->id }} ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="open === {{ $faq->id }}" x-collapse style="display:none"><p class="px-5 pb-5 text-sm text-muted">{{ $faq->answer }}</p></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

    <div class="mt-14 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('learning.index') }}" class="glass glass-hover flex items-center gap-3 rounded-2xl p-5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl surface text-brand-400"><x-icon name="book" class="h-5 w-5" /></span>
            <span>
                <span class="block font-semibold text-strong">{{ __('Learning Center') }}</span>
                <span class="block text-sm text-muted">{{ __('Guides on buying and shipping from China') }}</span>
            </span>
        </a>
        <a href="{{ route('disputes.index') }}" class="glass glass-hover flex items-center gap-3 rounded-2xl p-5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl surface text-brand-400"><x-icon name="mail" class="h-5 w-5" /></span>
            <span>
                <span class="block font-semibold text-strong">{{ __('Support Tickets') }}</span>
                <span class="block text-sm text-muted">{{ __('Still stuck? Talk to our team') }}</span>
            </span>
        </a>
    </div>
</div>
@endsection
