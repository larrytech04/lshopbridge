@php
    $cartCount = app(\App\Services\Shop\CartService::class)->count();
    $region = region();
    $regionCountries = \App\Models\Country::active()->get(['id', 'name', 'iso2', 'flag_emoji']);

    // Curated primary menu. The 4th item ($vis) is the display utility: 'flex'
    // shows always (mobile included); 'hidden sm:flex' etc. reveal on bigger
    // screens so the bar stays short and never scrolls on phones.
    // 3rd item is the icon: a *.png uses the uploaded asset (CSS-masked), anything
    // else falls back to a built-in SVG. About has no fitting asset, so it keeps 'info'.
    $menu = [
        ['Home', route('home'), 'House-Chimney-1--Streamline-Ultimate.png', 'flex'],
        ['Fund Alipay', route('public.fund'), 'Trading-Currency-Exchange--Streamline-Ultimate.png', 'flex'],
        ['Shop', route('shop.index'), 'Shop-Sign-Bag--Streamline-Ultimate.png', 'flex'],
        ['eSIM', \Illuminate\Support\Facades\Route::has('esim.index') ? route('esim.index') : route('shop.category', 'esims'), 'Sim-Card-2--Streamline-Ultimate.png', 'flex'],
        ['Gift Cards', route('shop.category', 'gift-cards'), 'Gift-Rectangle-With-Bow--Streamline-Ultimate.png', 'hidden sm:flex'],
        ['Guides', route('guides.index'), 'Study-Book--Streamline-Ultimate.png', 'hidden sm:flex'],
        ['About', route('pages.show', 'about'), 'Information-Desk-Question-Help--Streamline-Ultimate.png', 'hidden md:flex'],
        ['Payment methods', route('public.payment-methods'), 'Credit-Card--Streamline-Ultimate.png', 'hidden lg:flex'],
        ['Agents', route('agents.index'), 'Shipment-International--Streamline-Ultimate.png', 'hidden lg:flex'],
    ];
@endphp

<header class="sticky top-[env(safe-area-inset-top)] z-40">
    {{-- Utility strip, always visible (the whole header sticks; only the mid row collapses) --}}
    <div class="relative z-50 border-b border-app" style="background: var(--header-bg); backdrop-filter: blur(12px);">
        <div class="mx-auto flex max-w-none items-center justify-between gap-3 px-4 py-1.5 text-xs sm:px-6">
            {{-- Flipping social-proof badge: Google ⇄ Trustpilot, every 3.5s --}}
            <div class="review-flip text-muted" x-data="reviewFlip()" :class="{ 'is-flipped': flipped }" aria-label="{{ __('Customer reviews') }}">
                <div class="review-flip__inner">
                    {{-- Google --}}
                    <div class="review-flip__face review-flip__front">
                        <span class="g-wordmark font-bold tracking-tight"><span style="color:#4285F4">G</span><span style="color:#EA4335">o</span><span style="color:#FBBC05">o</span><span style="color:#4285F4">g</span><span style="color:#34A853">l</span><span style="color:#EA4335">e</span></span>
                        <span class="hidden sm:inline-flex"><x-stars variant="amber" :rating="4.8" size="h-3 w-3" /></span>
                        <span class="font-semibold text-body">4.8</span>
                    </div>
                    {{-- Trustpilot --}}
                    <div class="review-flip__face review-flip__back">
                        <span class="inline-flex items-center gap-1" style="color:#00b67a"><x-icon name="star" class="h-3.5 w-3.5 fill-current" /><span class="font-bold text-strong">Trustpilot</span></span>
                        <span class="hidden sm:inline-flex"><x-stars variant="trustpilot" :rating="4.5" size="h-3 w-3" inner="h-2 w-2" /></span>
                        <span class="font-semibold text-body">4.5</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-1.5 text-muted sm:gap-2">
                {{-- Country selector (searchable), compact flag on mobile, full pill on larger screens --}}
                <div class="pb-ts-header pb-ts-country block">
                    <select data-pbselect="country" data-pbselect-trigger="onboarding" data-nav="{{ route('region.set', '__VALUE__') }}"
                            data-empty="{{ __('No matches') }}" data-search="{{ __('Search…') }}" aria-label="{{ __('Country') }}" autocomplete="off">
                        @foreach ($regionCountries as $c)
                            <option value="{{ $c->iso2 }}" @selected($region['iso'] === $c->iso2)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Language selector (searchable), globe-only on mobile, globe + name on larger screens --}}
                <div class="pb-ts-header pb-ts-lang block">
                    <select data-pbselect="lang" data-pbselect-trigger="onboarding" data-nav="{{ route('locale.set', '__VALUE__') }}"
                            data-empty="{{ __('No matches') }}" data-search="{{ __('Search…') }}" aria-label="{{ __('Language') }}" autocomplete="off">
                        @foreach (supported_locales() as $code => $label)
                            <option value="{{ $code }}" @selected(current_locale() === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Help (icon badge + label) --}}
                <a href="{{ route('public.faqs') }}" class="inline-flex items-center gap-2.5 rounded-full px-2 py-1 transition hover:surface-2">
                    <span class="grid h-6 w-6 place-items-center rounded-full border border-app surface text-body"><x-img-icon name="Phone-Actions-24-Support-1--Streamline-Ultimate.png" class="h-3.5 w-3.5" /></span>
                    <span class="hidden font-medium text-body sm:inline">{{ __('Help') }}</span>
                </a>

                <x-theme-toggle size="sm" />
            </div>
        </div>
    </div>

    {{-- Main bar, sticks directly under the utility strip. On scroll-down (past the hero)
         only the middle row (logo/search/actions) collapses; the menu row stays visible.
         Scrolling up brings the middle row back. --}}
    <div data-autohide-header class="relative z-40 border-b border-app" style="background: var(--header-bg); backdrop-filter: blur(16px);"
         x-data="{ mobileSearch: false }"
         @keydown.window="if (($event.ctrlKey || $event.metaKey) && $event.key.toLowerCase() === 'k') { $event.preventDefault(); $refs.headerSearchInput?.focus(); }">
        <div class="header-mid">
        <div class="mx-auto flex max-w-none items-center gap-3 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center">
                <img src="{{ site_logo() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="h-9 w-auto sm:h-10" />
            </a>

            {{-- Command search --}}
            <form action="{{ route('shop.index') }}" method="GET" class="relative mx-auto hidden w-full max-w-xl md:block">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                <input x-ref="headerSearchInput" name="q" value="{{ request('q') }}" placeholder="{{ __('Search brands, categories, countries…') }}"
                       class="field !rounded-full pl-11 pr-16">
                <kbd class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md border border-app px-1.5 py-0.5 text-[10px] text-faint">{{ __('Ctrl K') }}</kbd>
            </form>

            <div class="ml-auto flex items-center gap-2">
                <button @click="mobileSearch = !mobileSearch" class="grid h-9 w-9 place-items-center rounded-xl border border-app surface text-muted md:hidden"><x-icon name="search" class="h-5 w-5" /></button>

                @auth
                    <a href="{{ route('dashboard') }}" class="grid h-9 w-9 place-items-center rounded-xl border border-app surface text-muted hover:text-strong"><x-img-icon name="User-Story--Streamline-Ultimate.png" class="h-5 w-5" /></a>
                @else
                    <a href="{{ route('login') }}" aria-label="{{ __('Log in') }}" class="grid h-9 w-9 place-items-center rounded-xl border border-app surface text-muted hover:text-strong sm:hidden"><x-icon name="user-circle" class="h-5 w-5" /></a>
                    <a href="{{ route('login') }}" class="hidden rounded-full border border-app surface px-4 py-2 text-sm font-semibold text-strong sm:block">{{ __('Log in') }}</a>
                    <a href="{{ route('register') }}" class="btn btn-primary hidden sm:inline-flex">{{ __('Get started') }}</a>
                @endauth

                <div class="relative" @if ($cartCount === 0) x-data="{ peek: false }" @mouseenter="peek = true" @mouseleave="peek = false" @endif>
                    <a href="{{ route('cart.index') }}" class="relative grid h-9 w-9 place-items-center rounded-xl border border-app surface text-muted hover:text-strong">
                        <x-img-icon name="Cashless-Payment-Camera-Product-Scanning-Cart--Streamline-Ultimate.png" class="h-5 w-5" />
                        @if ($cartCount > 0)<span class="absolute -right-1 -top-1 grid h-4.5 min-w-4.5 place-items-center rounded-full bg-brand-600 px-1 text-[10px] font-bold text-white">{{ $cartCount }}</span>@endif
                    </a>
                    @if ($cartCount === 0)
                        {{-- Empty-cart peek: a small box that slides in from the side on hover --}}
                        <div x-show="peek" x-cloak
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-x-8"
                             x-transition:enter-end="opacity-100 translate-x-0"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 translate-x-0"
                             x-transition:leave-end="opacity-0 translate-x-8"
                             class="glass-strong absolute right-0 z-50 mt-2 w-56 rounded-2xl p-4 text-center" style="display:none">
                            <x-empty-cart img="h-24" src="shop cart.jpg" />
                            <p class="mt-2 text-sm font-semibold text-strong">{{ __('Your cart is empty') }}</p>
                            <a href="{{ route('shop.index') }}" class="btn btn-primary mt-3 w-full !py-1.5 text-xs">{{ __('Browse the shop') }}</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Mobile search --}}
        <div x-show="mobileSearch" x-collapse class="px-4 pb-3 md:hidden" style="display:none">
            <form action="{{ route('shop.index') }}" method="GET" class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search products…') }}" class="field !rounded-full pl-11">
            </form>
        </div>
        </div>{{-- /header-mid --}}

        {{-- Primary menu, full menu on every screen; scrolls horizontally (no bar) until it fits, then centers. --}}
        <div class="mx-auto max-w-none px-4 sm:px-6">
            <nav class="no-scrollbar flex flex-nowrap items-center gap-x-5 overflow-x-auto pb-3 text-sm lg:flex-wrap lg:justify-center lg:gap-x-6 lg:overflow-visible">
                @foreach ($menu as [$label, $url, $icon, $vis])
                    <a href="{{ $url }}" class="flex shrink-0 items-center gap-2 font-medium {{ request()->fullUrlIs($url) || request()->url() === $url ? 'text-strong' : 'text-muted hover:text-strong' }}">
                        @if (str_ends_with($icon, '.png'))<x-img-icon :name="$icon" class="h-4 w-4" />@else<x-icon :name="$icon" class="h-4 w-4" />@endif {{ __($label) }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</header>
{{-- Compensates the mid-row collapse so page height stays constant (prevents scroll-feedback shaking) --}}
<div data-header-spacer class="header-spacer" aria-hidden="true"></div>
