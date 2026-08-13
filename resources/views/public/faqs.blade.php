@extends('layouts.public')
@section('title', __('Help Center').' · '.config('platform.name'))
@section('meta_description', __('Search answers about your account, wallet, deposits, China wallet funding, marketplace purchases, shipping, security and every other LshopBridge service.'))

@if ($faqSchema)
    @push('structured-data')
        {!! \App\Services\Seo\StructuredDataBuilder::scriptTag($faqSchema) !!}
    @endpush
@endif

@php
    $allFaqs = $faqs->map(fn ($f) => [
        'id' => $f->id,
        'question' => $f->question,
        'answer' => $f->answer,
        'category' => $f->category,
        'categoryLabel' => data_get($categories->firstWhere('key', $f->category), 'label', ucfirst($f->category)),
    ])->values();

    // Real example searches (first question in each category), never fabricated popularity data.
    $suggested = $faqsByCategory->map(fn ($items) => $items->first()->question)->values()->take(4);
@endphp

@section('content')
<div x-data="helpCenter({ faqs: @js($allFaqs) })">

    {{-- ============================================================ HERO / SEARCH --}}
    <section class="mx-auto max-w-none px-4 pt-16 text-center sm:px-6">
        <p class="text-xs font-bold uppercase tracking-widest text-brand-500">{{ __('Help Center') }}</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-strong sm:text-5xl">{{ __('How can we help?') }}</h1>
        <p class="mx-auto mt-4 max-w-3xl text-lg leading-relaxed text-body">{{ __('Search answers about your account, wallet, deposits, China wallet funding, marketplace purchases, shipping, security and every other LshopBridge service.') }}</p>

        <div class="mx-auto mt-8 max-w-3xl text-left">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-faint" />
                <input x-ref="searchInput" x-model="query"
                       @keydown.down.prevent="onArrow(1)" @keydown.up.prevent="onArrow(-1)" @keydown.enter.prevent="onEnter()"
                       @keydown.escape="query = ''; $refs.searchInput.blur()"
                       type="search" inputmode="search" autocomplete="off"
                       placeholder="{{ __('Search deposits, Alipay funding, verification, refunds, orders and more...') }}"
                       aria-label="{{ __('Search the Help Center') }}"
                       class="field w-full py-3.5 pl-12 pr-16 text-base">
                <kbd class="pointer-events-none absolute right-4 top-1/2 hidden -translate-y-1/2 rounded-md border border-app px-1.5 py-0.5 text-[11px] font-medium text-faint sm:inline-block">Ctrl K</kbd>
            </div>
            <p class="mt-2 min-h-[1rem] text-xs text-muted" x-show="isSearching" x-text="resultCountLabel" aria-live="polite" x-cloak></p>

            @if ($suggested->isNotEmpty())
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    <span class="text-xs text-faint">{{ __('Try:') }}</span>
                    @foreach ($suggested as $s)
                        <button type="button" @click="query = @js($s); $refs.searchInput.focus()" class="pill surface border border-app px-3 py-1.5 text-xs text-body transition hover:border-brand-400/50">{{ $s }}</button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ============================================================ SIDEBAR + CONTENT --}}
    <section class="mx-auto mt-14 max-w-none px-4 pb-20 sm:px-6">
        <div class="grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)] lg:items-start">

            {{-- Desktop: sticky category sidebar --}}
            <aside class="hidden lg:block">
                <nav class="sticky top-24 max-h-[calc(100vh-7rem)] space-y-1 overflow-y-auto pr-2" aria-label="{{ __('Help categories') }}">
                    <button type="button" @click="selectCategory('all')"
                            :class="activeCategory === 'all' && !isSearching ? 'bg-brand-500/10 font-semibold text-brand-600' : 'text-body hover:bg-slate-400/10'"
                            class="flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-left text-sm transition">
                        <span class="flex items-center gap-2.5"><x-icon name="list" class="h-4 w-4" /> {{ __('All Topics') }}</span>
                        <span class="text-xs text-faint">{{ $faqs->count() }}</span>
                    </button>
                    @foreach ($categories as $c)
                        <button type="button" @click="selectCategory('{{ $c['key'] }}')"
                                :class="activeCategory === '{{ $c['key'] }}' && !isSearching ? 'bg-brand-500/10 font-semibold text-brand-600' : 'text-body hover:bg-slate-400/10'"
                                class="flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-left text-sm transition">
                            <span class="flex items-center gap-2.5"><x-icon :name="$c['icon']" class="h-4 w-4" /> {{ $c['label'] }}</span>
                            <span class="text-xs text-faint">{{ $c['count'] }}</span>
                        </button>
                    @endforeach
                </nav>
            </aside>

            {{-- Mobile: horizontal category filter --}}
            <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 lg:hidden">
                <button type="button" @click="selectCategory('all')"
                        :class="activeCategory === 'all' && !isSearching ? 'bg-brand-500/10 border-brand-400/50 font-semibold text-brand-600' : 'surface border-app text-body'"
                        class="pill shrink-0 border px-3.5 py-2">{{ __('All') }}</button>
                @foreach ($categories as $c)
                    <button type="button" @click="selectCategory('{{ $c['key'] }}')"
                            :class="activeCategory === '{{ $c['key'] }}' && !isSearching ? 'bg-brand-500/10 border-brand-400/50 font-semibold text-brand-600' : 'surface border-app text-body'"
                            class="pill shrink-0 border px-3.5 py-2">{{ $c['label'] }}</button>
                @endforeach
            </div>

            {{-- Main FAQ content --}}
            <div>
                <template x-if="filtered.length === 0">
                    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-app px-6 py-14 text-center">
                        <span class="grid h-14 w-14 place-items-center rounded-2xl surface-2 text-muted ring-1 ring-app"><x-icon name="search" class="h-6 w-6" /></span>
                        <p class="mt-4 text-lg font-semibold text-strong">{{ __('No exact answer found') }}</p>
                        <p class="mt-1 max-w-sm text-sm text-muted">{{ __('Try different words, browse the categories, or contact support.') }}</p>
                        <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                            <button type="button" @click="query = ''; activeCategory = 'all'" class="btn btn-ghost text-sm">{{ __('Browse Categories') }}</button>
                            <a :href="'{{ route('support.guest.create') }}?subject=' + encodeURIComponent(query)" class="btn btn-primary text-sm">{{ __('Create Support Ticket') }}</a>
                        </div>
                    </div>
                </template>

                <div class="space-y-3">
                    <template x-for="(faq, i) in filtered" :key="faq.id">
                        <div :id="'help-result-' + i" class="glass overflow-hidden rounded-2xl transition" :class="highlighted === i ? 'ring-2 ring-brand-400' : ''">
                            <button type="button" @click="toggle(faq.id)" class="flex w-full items-start justify-between gap-4 p-5 text-left" :aria-expanded="(openId === faq.id).toString()">
                                <span class="min-w-0">
                                    <span class="block font-medium text-strong" x-text="faq.question"></span>
                                    <span class="mt-1 inline-block pill bg-slate-400/10 text-[11px] text-muted" x-text="faq.categoryLabel"></span>
                                </span>
                                <x-icon name="chevron-down" class="h-5 w-5 shrink-0 text-muted" ::class="openId === faq.id ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="openId === faq.id" x-collapse style="display:none">
                                <p class="px-5 pb-5 text-sm leading-relaxed text-muted" x-text="faq.answer"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================ CONTACT ESCALATION --}}
    <section class="mx-auto max-w-none px-4 pb-20 sm:px-6">
        <div class="mx-auto max-w-4xl rounded-2xl border border-dashed border-app p-6 text-center">
            <p class="font-semibold text-strong">{{ __('Still need help?') }}</p>
            <p class="mt-1 text-sm text-muted">{{ __("Can't find what you're looking for? Our support team is ready to help.") }}</p>
            <a href="{{ route('support.guest.create') }}" class="btn btn-primary mt-4 text-sm">{{ __('Create Support Ticket') }}</a>
        </div>
    </section>
</div>
@endsection
