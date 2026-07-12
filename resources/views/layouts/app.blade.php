<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Dashboard') · {{ setting('site_name', config('platform.name')) }}</title>
    @include('partials.theme-head')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="aurora min-h-screen overflow-x-hidden" x-data="{ sidebar: false, sbc: localStorage.getItem('pb-sbc') === '1' }">

    @php
        $user = auth()->user();
        $isAgentArea = $user->isAgent();
        $unread = $user->unreadNotifications()->count();
        $hdrCountries = \App\Models\Country::active()->get(['id', 'name', 'iso2', 'flag_emoji']);
        $hdrRegion = region();
        $hdrAvatar = $user->avatar_path
            ? Storage::url($user->avatar_path)
            : ($user->avatar_url ?: 'https://api.dicebear.com/9.x/avataaars/svg?seed='.urlencode($user->name));
    @endphp

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-72 transform border-r border-app transition-[transform,width] duration-300 lg:translate-x-0"
           style="background: var(--sidebar-bg); backdrop-filter: blur(18px);"
           :class="(sidebar ? 'translate-x-0' : '-translate-x-full') + (sbc ? ' sb-mini' : '')">
        <div class="flex h-16 items-center justify-between px-5">
            <a href="{{ $isAgentArea ? route('agent.dashboard') : route('dashboard') }}" class="inline-flex items-center">
                <img src="{{ site_logo() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="h-9 w-auto" :class="sbc ? 'lg:hidden' : ''" />
                <img src="{{ site_favicon() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="hidden h-8 w-8 object-contain" :class="sbc ? 'lg:!block' : ''" />
            </a>
            <button @click="sidebar = false" class="rounded-lg p-1.5 text-muted hover:surface-2 lg:hidden"><x-icon name="x" class="h-5 w-5" /></button>
        </div>
        <nav class="h-[calc(100vh-4rem)] overflow-y-auto px-3 pb-6">
            @include($isAgentArea ? 'partials.nav-agent' : 'partials.nav-user')
        </nav>
    </aside>

    <div x-show="sidebar" @click="sidebar = false" x-transition.opacity class="fixed inset-0 z-40 bg-black/50 lg:hidden" style="display:none"></div>

    {{-- Sidebar edge controls — small social trigger + collapse arrow riding the border line --}}
    <div class="fixed top-4 z-[60] hidden -translate-x-1/2 flex-col items-center gap-2 transition-[left] duration-300 lg:flex"
         :class="sbc ? 'left-20' : 'left-72'">
        @include('partials.social-dock')
        <button type="button" @click="sbc = !sbc; localStorage.setItem('pb-sbc', sbc ? '1' : '0')"
                :aria-expanded="!sbc" aria-label="{{ __('Toggle sidebar') }}"
                class="grid h-6 w-6 place-items-center text-muted transition hover:scale-110 hover:text-strong">
            <x-img-icon name="Arrow-Button-Right-3--Streamline-Ultimate.png" class="h-4 w-4 transition-transform duration-300" ::class="sbc ? '' : 'rotate-180'" />
        </button>
    </div>

    <div class="lg:pl-72 transition-[padding] duration-300" :class="sbc ? 'lg:!pl-20' : ''">
        {{-- Top bar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-app px-4 sm:px-6" style="background: var(--header-bg); backdrop-filter: blur(16px);">
            {{-- Phone: social trigger (grey circle) + logo; desktop keeps the page title --}}
            <div class="lg:hidden">@include('partials.social-dock', ['circled' => true])</div>
            <a href="{{ $isAgentArea ? route('agent.dashboard') : route('dashboard') }}" class="lg:hidden">
                <img src="{{ site_logo() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="h-6 w-auto sm:h-7" />
            </a>
            {{-- Desktop/tablet: command search replaces the page title --}}
            <form action="{{ route('shop.index') }}" method="GET" class="relative hidden w-full max-w-xl lg:block">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Search brands, categories, countries…') }}"
                       class="field !rounded-full !py-2 pl-11 pr-20 text-sm">
                <kbd class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full border border-app px-2 py-0.5 text-[10px] font-semibold uppercase text-faint">{{ __('Ctrl K') }}</kbd>
            </form>

            <div class="ml-auto flex items-center gap-1.5 sm:gap-2.5">
                {{-- Search (round, YouTube-style) --}}
                <a href="{{ route('shop.index') }}" aria-label="{{ __('Search') }}" class="grid h-9 w-9 place-items-center rounded-full text-muted transition hover:surface-2 hover:text-strong lg:hidden">
                    <x-icon name="search" class="h-4.5 w-4.5" />
                </a>

                {{-- Country selector (compact round flag) --}}
                <div class="pb-ts-header pb-ts-country block">
                    <select data-pbselect="country" data-nav="{{ route('region.set', '__VALUE__') }}"
                            data-empty="{{ __('No matches') }}" data-search="{{ __('Search…') }}" aria-label="{{ __('Country') }}" autocomplete="off">
                        @foreach ($hdrCountries as $c)
                            <option value="{{ $c->iso2 }}" @selected($hdrRegion['iso'] === $c->iso2)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <x-theme-toggle bare />

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="relative grid h-9 w-9 place-items-center rounded-full text-muted transition hover:surface-2 hover:text-strong">
                        <x-icon name="bell" class="h-5 w-5" />
                        @if ($unread)<span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition style="display:none" class="card-solid absolute right-0 mt-2 w-80 rounded-2xl border border-app p-2 shadow-lg">
                        <div class="flex items-center justify-between px-3 py-2"><span class="text-sm font-semibold text-strong">{{ __('Notifications') }}</span><a href="{{ route('notifications.index') }}" class="text-xs text-brand-400">{{ __('View all') }}</a></div>
                        <div class="max-h-80 space-y-1 overflow-y-auto">
                            @forelse ($user->unreadNotifications()->take(6)->get() as $n)
                                <a href="{{ $n->data['url'] ?? '#' }}" class="block rounded-xl px-3 py-2 hover:surface"><p class="text-sm font-medium text-strong">{{ $n->data['title'] ?? 'Notification' }}</p><p class="text-xs text-muted">{{ \Illuminate\Support\Str::limit($n->data['message'] ?? '', 70) }}</p></a>
                            @empty
                                <p class="px-3 py-6 text-center text-sm text-faint">{{ __('You\'re all caught up.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div x-data="{ open: false }" class="relative hidden sm:block">
                    <button @click="open = !open" aria-label="{{ __('Account') }}" class="block rounded-full transition hover:scale-105">
                        <img src="{{ $hdrAvatar }}" alt="{{ $user->name }}" class="h-9 w-9 rounded-full object-cover" />
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition style="display:none" class="card-solid absolute right-0 mt-2 w-56 rounded-2xl border border-app p-2 shadow-lg">
                        <div class="border-b border-app px-3 py-2"><p class="text-sm font-semibold text-strong">{{ $user->name }}</p><p class="truncate text-xs text-muted">{{ $user->email }}</p></div>
                        <a href="{{ route('profile.edit') }}" class="mt-1 flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-body hover:surface"><x-icon name="cog" class="h-4 w-4" /> {{ __('Profile & security') }}</a>
                        <a href="{{ route('shop.orders.index') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-body hover:surface"><x-icon name="receipt" class="h-4 w-4" /> {{ __('My orders') }}</a>
                        @if ($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-body hover:surface"><x-icon name="gauge" class="h-4 w-4" /> {{ __('Admin panel') }}</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-rose-400 hover:surface"><x-icon name="logout" class="h-4 w-4" /> {{ __('Log out') }}</button></form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-none px-4 py-6 pb-28 sm:px-6 lg:py-8 lg:pb-10">
            <x-flash />
            @yield('content')
        </main>
    </div>

    @include('partials.bottom-dock')
    @include('partials.onboarding')
</body>
</html>
