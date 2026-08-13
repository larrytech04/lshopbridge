<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Dashboard') · {{ setting('site_name', config('platform.name')) }}</title>
    @include('partials.theme-head')
    {{-- Plus Jakarta Sans is self-hosted (bundled via app.css); no external font host. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="aurora pb-ash-theme min-h-screen overflow-x-hidden"
      x-data="{
        sbc: localStorage.getItem('pb-sbc') === '1',
        edgeHover: false,
        ctx: null,
        openCtx(name) { this.ctx = name; },
        closeCtx() { this.ctx = null; },
      }"
      @keydown.escape.window="closeCtx()">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg">{{ __('Skip to content') }}</a>
    @include('partials.pull-to-refresh')
    @include('partials.page-skeleton')

    @php
        $user = auth()->user();
        $isAgentArea = $user->isAgent();
        $unread = $user->unreadNotifications()->count();
        $navBadges = app(\App\Services\Navigation\NavigationBadgeService::class)->forUser($user);
        $megaMenuCategories = $isAgentArea ? collect() : app(\App\Services\Shop\CategoryNavigationService::class)->visibleTopLevel(region()['iso'] ?? null);
        $hdrCountries = \App\Models\Country::active()->get(['id', 'name', 'iso2', 'flag_emoji']);
        $hdrRegion = region();
        $hdrAvatar = $user->avatar_path
            ? Storage::url($user->avatar_path)
            : ($user->avatar_url ?: local_avatar($user->name));
    @endphp

    @if (session('impersonator_id'))
        <div class="sticky top-0 z-[60] flex items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-center text-sm font-semibold text-amber-950">
            <x-icon name="user-circle" class="h-4 w-4 shrink-0" />
            {{ __('You are viewing the site as :name.', ['name' => $user->name]) }}
            <form method="POST" action="{{ route('impersonate.stop') }}">
                @csrf
                <button class="rounded-full bg-amber-950 px-3 py-1 text-xs font-bold text-amber-50 hover:bg-amber-900">{{ __('Return to admin') }}</button>
            </form>
        </div>
    @endif

    {{-- Dashboard shell: primary sidebar / main content as real CSS Grid columns
         (see .dashboard-shell in app.css). No floating panels, no manual margins
         on individual pages — this wrapper owns the whole layout. --}}
    <div class="dashboard-shell transition-[grid-template-columns] duration-200" :class="{ 'shell-sbc': sbc }">

        {{-- Primary sidebar, desktop-only persistent nav. No mobile drawer/hamburger: the
             bottom dock's "Menu" sheet covers the same links on phones. --}}
        <aside class="shell-col-primary hidden border-r border-app lg:flex"
               style="background: var(--sidebar-bg); backdrop-filter: blur(18px);"
               :class="sbc ? 'sb-mini' : ''"
               @mouseenter="edgeHover = true" @mouseleave="edgeHover = false">
            <div class="flex h-16 shrink-0 items-center px-5">
                <a href="{{ $isAgentArea ? route('agent.dashboard') : route('dashboard') }}" class="inline-flex items-center">
                    <img src="{{ site_logo() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="h-9 w-auto" :class="sbc ? 'lg:hidden' : ''" />
                    <img src="{{ site_favicon() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="hidden h-8 w-8 object-contain" :class="sbc ? 'lg:!block' : ''" />
                </a>
            </div>
            <nav class="min-h-0 flex-1 overflow-y-auto px-3 pb-6">
                @include($isAgentArea ? 'partials.nav-agent' : 'partials.nav-user')
            </nav>
        </aside>

        {{-- Sidebar edge controls, small social trigger + collapse arrow riding the primary
             sidebar's own border. Only shown while the mouse is over the sidebar or header. --}}
        <div class="edge-ctl pointer-events-none fixed top-4 z-[60] hidden -translate-x-1/2 flex-col items-center gap-2 transition-[left,opacity] duration-300 lg:flex"
             :class="{ 'edge-ctl-sbc': sbc, 'opacity-100 pointer-events-auto': edgeHover, 'opacity-0': !edgeHover }"
             @mouseenter="edgeHover = true" @mouseleave="edgeHover = false">
            @include('partials.social-dock')
            <button type="button" @click="sbc = !sbc; localStorage.setItem('pb-sbc', sbc ? '1' : '0')"
                    :aria-expanded="!sbc" aria-label="{{ __('Toggle sidebar') }}"
                    class="grid h-6 w-6 place-items-center text-muted transition hover:scale-110 hover:text-strong">
                <x-img-icon name="Arrow-Button-Right-3--Streamline-Ultimate.png" class="h-4 w-4 transition-transform duration-300" ::class="sbc ? '' : 'rotate-180'" />
            </button>
        </div>

        <div class="shell-col-main">
        {{-- Top bar --}}
        <header class="header-glass sticky top-0 z-30 flex min-h-16 items-center gap-3 !border-x-0 !border-t-0 px-4 sm:px-6"
                @mouseenter="edgeHover = true" @mouseleave="edgeHover = false">
            {{-- Phone: social trigger (grey circle) + logo; desktop keeps the page title --}}
            <div class="lg:hidden">@include('partials.social-dock', ['circled' => true])</div>
            <a href="{{ $isAgentArea ? route('agent.dashboard') : route('dashboard') }}" class="lg:hidden">
                <img src="{{ site_logo() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="h-6 w-auto sm:h-7" />
            </a>
            {{-- Search + command palette: one Alpine scope shared by the mobile trigger button,
                 the desktop field, and the dropdown, so typing always happens in the field
                 you see (not a second hidden input) on every breakpoint. --}}
            @php
                $paletteTabs = [
                    ['key' => 'overview', 'label' => __('Overview'), 'url' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
                    ['key' => 'orders', 'label' => __('Orders'), 'url' => route('shop.orders.index'), 'active' => request()->routeIs('shop.orders.*')],
                    ['key' => 'wallet', 'label' => __('Wallet'), 'url' => route('wallet.index'), 'active' => request()->routeIs('wallet.*')],
                    ['key' => 'transactions', 'label' => __('Transactions'), 'url' => route('transactions.index'), 'active' => request()->routeIs('transactions.*')],
                    ['key' => 'profile', 'label' => __('Profile'), 'url' => route('profile.edit'), 'active' => request()->routeIs('profile.edit')],
                    ['key' => 'settings', 'label' => __('Settings'), 'url' => route('profile.edit'), 'active' => false],
                ];
                $paletteMostUsed = [
                    ['icon' => 'wallet', 'title' => __('Wallet'), 'description' => __('Top up & balance'), 'url' => route('wallet.index')],
                    ['icon' => 'cart', 'title' => __('Orders'), 'description' => __('Recent purchases'), 'url' => route('shop.orders.index')],
                    ['icon' => 'chart', 'title' => __('Transactions'), 'description' => __('Activity history'), 'url' => route('transactions.index')],
                    ['icon' => 'cog', 'title' => __('Settings'), 'description' => __('Account information'), 'url' => route('profile.edit')],
                    ['icon' => 'shield', 'title' => __('Security'), 'description' => __('Password & sessions'), 'url' => route('security.index')],
                ];
            @endphp
            <div class="relative min-w-0 flex-1 lg:max-w-xl"
                 x-data="commandPalette(@js($paletteTabs), @js($paletteMostUsed))"
                 x-on:open-command-palette.window="openPalette()" x-on:close-overlays.window="close()" x-on:keydown.window="onGlobalKey($event)"
                 @click.outside="close()">

                {{-- Mobile: round icon button opens the same palette --}}
                <button type="button" @click="openPalette()" aria-label="{{ __('Search') }}" class="grid h-9 w-9 place-items-center rounded-full text-muted transition hover:surface-2 hover:text-strong lg:hidden">
                    <x-icon name="search" class="h-4.5 w-4.5" />
                </button>

                {{-- Desktop/tablet: a real, live search field, typing here drives the dropdown below it --}}
                <form action="{{ route('shop.index') }}" method="GET" class="relative hidden w-full lg:block" @submit.prevent>
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                    <input name="q" x-ref="input" x-model="q" @focus="openPalette()" @input.debounce.250ms="search()" data-shortcut-search autocomplete="off"
                           placeholder="{{ __('Search brands, categories, countries…') }}"
                           class="field pb-ash-field !rounded-full !py-2 pl-11 pr-20 text-sm">
                    <kbd class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full border border-app px-2 py-0.5 text-[10px] font-semibold uppercase text-faint">{{ __('Ctrl /') }}</kbd>
                </form>

                @include('partials.command-palette')
            </div>

            <div class="ml-auto flex items-center gap-1.5 sm:gap-2.5">

                {{-- Country selector (compact round flag) --}}
                <div class="pb-ts-header pb-ts-country block">
                    <select data-pbselect="country" data-pbselect-trigger="onboarding" data-nav="{{ route('region.set', '__VALUE__') }}"
                            data-empty="{{ __('No matches') }}" data-search="{{ __('Search…') }}" aria-label="{{ __('Country') }}" autocomplete="off">
                        @foreach ($hdrCountries as $c)
                            <option value="{{ $c->iso2 }}" @selected($hdrRegion['iso'] === $c->iso2)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <x-theme-toggle bare />

                <div x-data="notificationBell({
                        userId: {{ $user->id }},
                        unread: {{ $unread }},
                        items: @js($user->notifications()->take(6)->get()->map(fn ($n) => [
                            'id' => $n->id,
                            'title' => $n->data['title'] ?? 'Notification',
                            'message' => $n->data['message'] ?? '',
                            'url' => $n->data['url'] ?? '#',
                            'unread' => is_null($n->read_at),
                            'time' => $n->created_at->diffForHumans(),
                        ])),
                    })" class="relative">
                    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true" aria-label="{{ __('Notifications') }}" class="relative grid h-9 w-9 place-items-center rounded-full text-muted transition hover:surface-2 hover:text-strong">
                        <x-icon name="bell" class="h-5 w-5" />
                        <span x-show="unread > 0" x-text="unread > 9 ? '9+' : unread" style="display:none" class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"></span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition style="display:none" class="card-solid absolute right-0 mt-2 w-80 rounded-2xl border border-app p-2 shadow-lg">
                        <div class="flex items-center justify-between px-3 py-2">
                            <span class="flex items-center gap-2 text-sm font-semibold text-strong">
                                {{ __('Notifications') }}
                                <span x-show="unread > 0" x-text="unread + ' {{ __('new') }}'" style="display:none" class="rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-bold text-white"></span>
                            </span>
                            <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-brand-400">{{ __('View all') }}</a>
                        </div>
                        <div class="max-h-80 space-y-1 overflow-y-auto">
                            <template x-for="n in items" :key="n.id">
                                <a :href="n.url" class="flex items-start gap-2.5 rounded-xl px-3 py-2 hover:surface">
                                    <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full" :class="n.unread ? 'bg-brand-600 text-white' : 'surface-2 text-muted'"><x-icon name="bell" class="h-4 w-4" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-center gap-1.5"><span class="truncate text-sm font-medium text-strong" x-text="n.title"></span><span x-show="n.unread" style="display:none" class="h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span></span>
                                        <span class="block truncate text-xs text-muted" x-text="n.message"></span>
                                        <span class="block text-[11px] text-faint" x-text="n.time"></span>
                                    </span>
                                </a>
                            </template>
                            <p x-show="items.length === 0" class="px-3 py-6 text-center text-sm text-faint">{{ __('You\'re all caught up.') }}</p>
                        </div>
                        <form method="POST" action="{{ route('notifications.readAll') }}" x-show="unread > 0" style="display:none" class="border-t border-app pt-2" @submit="unread = 0; items.forEach(n => n.unread = false)">
                            @csrf
                            <button class="w-full rounded-full bg-brand-600 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Mark all read') }}</button>
                        </form>
                    </div>
                </div>

                <div x-data="{ open: false }" class="relative hidden sm:block"
                     @mouseenter="open = true" @mouseleave="open = false">
                    <button @click="open = !open" aria-label="{{ __('Account') }}" class="relative block rounded-full transition hover:scale-105">
                        <img src="{{ $hdrAvatar }}" alt="{{ $user->name }}" class="h-9 w-9 rounded-full object-cover ring-2 ring-app" />
                        @if ((int) $user->kyc_level >= 2)
                            <x-verified-tick class="absolute -bottom-1 -right-1 h-3.5 w-3.5" />
                        @endif
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition style="display:none" class="card-solid absolute right-0 mt-2 w-64 rounded-2xl border border-app p-2 shadow-lg">
                        <div class="flex items-center gap-3 border-b border-app px-3 py-2.5">
                            <div class="relative shrink-0">
                                <img src="{{ $hdrAvatar }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover ring-2 ring-app" />
                                @if ((int) $user->kyc_level >= 2)
                                    <x-verified-tick class="absolute -bottom-1 -right-1 h-4 w-4" />
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="flex items-center gap-1.5 truncate text-sm font-semibold text-strong">
                                    {{ $user->name }}
                                </p>
                                <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                            </div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="mt-1 flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-body hover:surface"><x-icon name="user" class="h-4 w-4" /> {{ __('My Profile') }}</a>
                        <button type="button" @click="open = false; window.dispatchEvent(new CustomEvent('open-theme-menu'))" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-body hover:surface"><x-icon name="cog" class="h-4 w-4" /> {{ __('Appearance') }}</button>
                        <a href="{{ route('profile.edit') }}#account-settings" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-body hover:surface"><x-icon name="doc" class="h-4 w-4" /> {{ __('Account Settings') }}</a>
                        @if ($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-body hover:surface"><x-icon name="gauge" class="h-4 w-4" /> {{ __('Switch to Admin Panel') }}</a>
                        @endif
                        <a href="{{ route('home') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-body hover:surface"><x-icon name="globe" class="h-4 w-4" /> {{ __('Visit Public Website') }}</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-app mt-1 pt-1">@csrf<button class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-rose-400 hover:surface"><x-icon name="logout" class="h-4 w-4" /> {{ __('Log Out') }}</button></form>
                    </div>
                </div>
            </div>
        </header>

        {{-- flex column + flex-1 content wrapper is what makes the footer a
             genuine "sticky footer": pinned to the bottom of the viewport on
             short pages, pushed below the fold normally on long ones — never
             a position:sticky/fixed overlay sitting on top of content. --}}
        <main id="main-content" tabindex="-1" class="mx-auto flex w-full max-w-none flex-1 flex-col px-4 py-6 pb-28 sm:px-6 lg:py-8 lg:pb-10">
            <x-flash />
            <div class="flex-1">
                @yield('content')
            </div>
            @include('partials.dashboard-footer')
        </main>
        </div>
    </div>

    @include('partials.bottom-dock')
    @include('partials.onboarding')
    @include('partials.welcome-intro')
    @include('partials.shortcuts')
</body>
</html>
