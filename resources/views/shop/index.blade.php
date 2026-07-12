@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', ($activeTop ? __($activeTop->name).' — ' : '').__('Digital Shop').' · '.config('platform.name'))
@section('page-title', __('Shop'))

@section('content')
@if ($activeTop && $activeTop->slug === 'gift-cards')
    @include('partials.giftcard-intro')
@endif

{{-- Breadcrumb (replaces the hero) --}}
<div class="mx-auto max-w-none px-4 pt-6 sm:px-6">
    <nav class="flex flex-wrap items-center gap-2 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('home') }}" class="hover:text-strong">{{ __('Home') }}</a>
        <x-img-icon name="Arrow-Button-Right-3--Streamline-Ultimate.png" class="h-3 w-3 text-faint" />
        <a href="{{ route('shop.index') }}" class="hover:text-strong {{ ! $activeTop ? 'font-semibold text-strong' : '' }}">{{ __('Shop') }}</a>
        @if ($activeTop)
            <x-img-icon name="Arrow-Button-Right-3--Streamline-Ultimate.png" class="h-3 w-3 text-faint" />
            <a href="{{ route('shop.category', $activeTop) }}" class="hover:text-strong {{ ! $activeSub ? 'font-semibold text-strong' : '' }}">{{ __($activeTop->name) }}</a>
        @endif
        @if ($activeSub)
            <x-img-icon name="Arrow-Button-Right-3--Streamline-Ultimate.png" class="h-3 w-3 text-faint" />
            <span class="font-semibold text-strong">{{ __($activeSub->name) }}</span>
        @endif
    </nav>
</div>

<div class="mx-auto max-w-none px-4 pb-8 pt-4 sm:px-6">
    <div class="grid gap-6 lg:grid-cols-[16rem_1fr]">

        {{-- Mobile category dropdown --}}
        <div class="lg:hidden" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                    class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm font-semibold text-white shadow-md"
                    style="background:#101a33">
                <span class="inline-flex items-center gap-2"><x-icon :name="$activeTop->icon ?? 'bag'" class="h-4 w-4" /> {{ $activeTop ? __($activeTop->name) : __('All categories') }}</span>
                <x-icon name="chevron-down" class="h-4 w-4 transition" x-bind:class="open ? 'rotate-180' : ''" />
            </button>
            <div x-show="open" x-collapse x-cloak class="glass mt-2 rounded-2xl p-2 ring-1 ring-app">
                <a href="{{ route('shop.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ ! $activeTop ? 'bg-slate-600/12 text-brand-500' : 'text-body' }}">{{ __('All categories') }}</a>
                @foreach ($topCategories as $c)
                    <a href="{{ route('shop.category', $c) }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium {{ $activeTop && $activeTop->id === $c->id ? 'bg-slate-600/12 text-brand-500' : 'text-body' }}"><x-icon :name="$c->icon" class="h-4 w-4" /> {{ __($c->name) }}</a>
                @endforeach
                @if ($subcategories->count())
                    <div class="my-1 border-t border-app"></div>
                    <a href="{{ route('shop.category', $activeTop) }}" class="block rounded-xl px-3 py-2 text-sm {{ ! $activeSub ? 'font-semibold text-brand-500' : 'text-muted' }}">{{ __('All') }} {{ __($activeTop->name) }}</a>
                    @foreach ($subcategories as $s)
                        <a href="{{ route('shop.category', $s) }}" class="block rounded-xl px-3 py-2 text-sm {{ $activeSub && $activeSub->id === $s->id ? 'font-semibold text-brand-500' : 'text-muted' }}">{{ __($s->name) }}</a>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Sidebar (desktop) — categories up, selected category's subcategories open below --}}
        <aside class="hidden lg:block">
            <div class="glass sticky top-24 max-h-[calc(100vh-7rem)] overflow-y-auto rounded-2xl p-4 ring-1 ring-app">
                <p class="px-2 text-[11px] font-bold uppercase tracking-wider text-faint">{{ __('Categories') }}</p>
                <nav class="mt-2 space-y-0.5">
                    @foreach ($topCategories as $c)
                        <a href="{{ route('shop.category', $c) }}"
                           class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ $activeTop && $activeTop->id === $c->id ? 'bg-slate-600/12 text-brand-500' : 'text-body hover:bg-slate-500/5 hover:text-strong' }}">
                            <x-icon :name="$c->icon" class="h-4 w-4 shrink-0" /> {{ __($c->name) }}
                        </a>
                    @endforeach
                </nav>

                @if ($subcategories->count())
                    <p class="mt-5 px-2 text-[11px] font-bold uppercase tracking-wider text-faint">{{ __('Subcategories') }}</p>
                    <nav class="mt-2 space-y-0.5">
                        <a href="{{ route('shop.category', $activeTop) }}"
                           class="block rounded-xl px-3 py-2 text-sm font-medium transition {{ ! $activeSub ? 'bg-slate-600/12 text-brand-500' : 'text-muted hover:bg-slate-500/5 hover:text-strong' }}">{{ __('All') }} {{ __($activeTop->name) }}</a>
                        @foreach ($subcategories as $s)
                            <a href="{{ route('shop.category', $s) }}"
                               class="block rounded-xl px-3 py-2 text-sm font-medium transition {{ $activeSub && $activeSub->id === $s->id ? 'bg-slate-600/12 text-brand-500' : 'text-muted hover:bg-slate-500/5 hover:text-strong' }}">{{ __($s->name) }}</a>
                        @endforeach
                    </nav>
                @endif
            </div>
        </aside>

        {{-- Main content --}}
        <div>
            {{-- Search + sort --}}
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                <form method="GET" action="{{ url()->current() }}" class="relative flex-1">
                    @if ($sort !== 'popular')<input type="hidden" name="sort" value="{{ $sort }}">@endif
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search all products — brands, eSIMs, countries') }}" class="field !rounded-2xl !py-2.5" style="padding-left:2.75rem">
                </form>
                <div class="inline-flex shrink-0 items-center gap-1 rounded-2xl p-1 text-xs font-semibold ring-1 ring-app" style="background: color-mix(in srgb, var(--text-strong) 5%, transparent);">
                    @foreach (['popular' => __('Popularity'), 'az' => 'A → Z', 'za' => 'Z → A'] as $key => $label)
                        <a href="{{ request()->fullUrlWithQuery(['sort' => $key === 'popular' ? null : $key]) }}"
                           class="rounded-xl px-3 py-1.5 transition {{ $sort === $key ? 'bg-app text-strong shadow-sm' : 'text-muted hover:text-strong' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            @if ($products->isEmpty())
                <x-empty icon="bag" :title="__('No products found')" :message="__('Try a different category or search.')" />
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($products as $product)
                        @include('shop._product', ['product' => $product])
                    @endforeach
                </div>
                <div class="mt-10">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
