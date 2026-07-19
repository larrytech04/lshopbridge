<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Admin') · {{ setting('site_name', config('platform.name')) }}</title>
    @include('partials.theme-head')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="aurora pb-ash-theme min-h-screen" x-data="{ sidebar: false }">
    @include('partials.page-skeleton')

    @php
        $q = [
            'kyc' => \App\Models\KycVerification::where('status', 'pending')->count(),
            'agents' => \App\Models\Agent::where('status', 'pending')->count(),
            'beneficiaries' => \App\Models\BeneficiaryAccount::where('status', 'pending')->count(),
            'deposits' => \App\Models\Deposit::whereIn('status', ['pending', 'under_review'])->count(),
            'funding' => \App\Models\FundingRequest::whereIn('status', ['manual_review', 'funding_processing'])->count(),
            'reviews' => \App\Models\Review::where('status', 'pending')->count(),
            'disputes' => \App\Models\Dispute::whereIn('status', ['open', 'in_progress'])->count(),
            'risk' => \App\Models\RiskFlag::where('status', 'open')->count(),
            'shop' => \App\Models\ShopOrder::where('status', 'pending')->count(),
        ];
    @endphp

    <aside class="fixed inset-y-0 left-0 z-50 w-72 transform border-r border-app transition-transform duration-300 lg:translate-x-0"
           style="background: var(--sidebar-bg); backdrop-filter: blur(18px);" :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-16 items-center justify-between px-5">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2">
                <img src="{{ site_logo() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="h-9 w-auto" />
                <span class="text-sm font-extrabold text-brand-400">Admin</span>
            </a>
            <button @click="sidebar = false" class="rounded-lg p-1.5 text-muted hover:surface-2 lg:hidden"><x-icon name="x" class="h-5 w-5" /></button>
        </div>
        <nav class="h-[calc(100vh-4rem)] space-y-0.5 overflow-y-auto px-3 pb-8 text-sm">
            <x-nav-link :href="route('admin.dashboard')" img="Dashboard-3--Streamline-Plump.png" raw :active="request()->routeIs('admin.dashboard')">Overview</x-nav-link>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">People</p>
            <x-admin-nav route="admin.users.*" :href="route('admin.users.index')" img="Multiple-Users-1--Streamline-Ultimate.svg" raw>Users</x-admin-nav>
            <x-admin-nav route="admin.kyc.*" :href="route('admin.kyc.index')" img="Work-Pending-For-Review--Streamline-Bangalore.png" raw :badge="$q['kyc']">KYC review</x-admin-nav>
            <x-admin-nav route="admin.agents.*" :href="route('admin.agents.index')" img="Delivery-Package-Give--Streamline-Freehand.png" raw :badge="$q['agents']">Agents</x-admin-nav>
            <x-admin-nav route="admin.beneficiaries.*" :href="route('admin.beneficiaries.index')" img="Yuan--Streamline-Plump.png" raw :badge="$q['beneficiaries']">China wallets</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Money</p>
            <x-admin-nav route="admin.deposits.*" :href="route('admin.deposits.index')" img="Credit-Card-Receive--Streamline-Sharp-Streamline-Material.png" raw :badge="$q['deposits']">Deposits</x-admin-nav>
            <x-admin-nav route="admin.funding.*" :href="route('admin.funding.index')" img="Real-Estate-Insurance-Dollar-Hand-House--Streamline-Freehand.png" raw :badge="$q['funding']">Funding</x-admin-nav>
            <x-admin-nav route="admin.rates.*" :href="route('admin.rates.index')" img="Cash-Exchange-Rate--Streamline-Flex.png" raw>Exchange rates</x-admin-nav>
            <x-admin-nav route="admin.fees.*" :href="route('admin.fees.index')" img="Incognito-Mode--Streamline-Core-Remix.png" raw>Fees</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Shop</p>
            <x-admin-nav route="admin.shop.products.*" :href="route('admin.shop.products.index')" img="Shop-Open--Streamline-Freehand.svg" raw>Products</x-admin-nav>
            <x-admin-nav route="admin.shop.categories.*" :href="route('admin.shop.categories.index')" icon="list">Categories</x-admin-nav>
            <x-admin-nav route="admin.shop.orders.*" :href="route('admin.shop.orders.index')" icon="receipt" :badge="$q['shop']">Shop orders</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Config</p>
            <x-admin-nav route="admin.methods.*" :href="route('admin.methods.index')" icon="card">Payment methods</x-admin-nav>
            <x-admin-nav route="admin.providers.*" :href="route('admin.providers.index')" icon="webhook">Providers</x-admin-nav>
            <x-admin-nav route="admin.channels.*" :href="route('admin.channels.index')" icon="building">Deposit accounts</x-admin-nav>
            <x-admin-nav route="admin.countries.*" :href="route('admin.countries.index')" icon="globe">Countries</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Content</p>
            <x-admin-nav route="admin.guides.*" :href="route('admin.guides.index')" icon="book">Guides</x-admin-nav>
            <x-admin-nav route="admin.faqs.*" :href="route('admin.faqs.index')" icon="info">FAQs</x-admin-nav>
            <x-admin-nav route="admin.banners.*" :href="route('admin.banners.index')" icon="sparkles">Banners</x-admin-nav>
            <x-admin-nav route="admin.pages.*" :href="route('admin.pages.index')" icon="doc">Legal pages</x-admin-nav>
            <x-admin-nav route="admin.content.*" :href="route('admin.content.index')" icon="monitor">Page content</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Trust &amp; safety</p>
            <x-admin-nav route="admin.reviews.*" :href="route('admin.reviews.index')" icon="star" :badge="$q['reviews']">Reviews</x-admin-nav>
            <x-admin-nav route="admin.disputes.*" :href="route('admin.disputes.index')" icon="info" :badge="$q['disputes']">Disputes</x-admin-nav>
            <x-admin-nav route="admin.risk.*" :href="route('admin.risk.index')" icon="flag" :badge="$q['risk']">Risk &amp; fraud</x-admin-nav>
            <x-admin-nav route="admin.webhooks.*" :href="route('admin.webhooks.index')" icon="webhook">Webhook logs</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">System</p>
            <x-admin-nav route="admin.integrations.*" :href="route('admin.integrations.index')" icon="webhook">Integrations / API keys</x-admin-nav>
            <x-admin-nav route="admin.settings.*" :href="route('admin.settings.index')" icon="cog">Settings</x-admin-nav>
            <x-admin-nav route="admin.audit.*" :href="route('admin.audit.index')" icon="list">Audit logs</x-admin-nav>
        </nav>
    </aside>

    <div x-show="sidebar" @click="sidebar = false" x-transition.opacity class="fixed inset-0 z-40 bg-black/50 lg:hidden" style="display:none"></div>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 px-4 sm:px-6" style="background: var(--header-bg);">
            <button @click="sidebar = true" class="rounded-lg p-2 text-muted hover:surface-2 lg:hidden"><x-icon name="menu" class="h-6 w-6" /></button>
            <h1 class="text-base font-semibold text-strong sm:text-lg">@yield('page-title', 'Overview')</h1>
            <div class="ml-auto flex items-center gap-3">
                <a href="{{ route('home') }}" target="_blank" class="hidden text-sm text-muted hover:text-strong sm:block">View site ↗</a>
                <x-theme-toggle />
                <div x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false">
                    <button @click="open = !open" class="flex items-center gap-2 rounded-xl border border-app surface p-1.5 pr-3 text-body hover:text-strong">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-brand-600 text-xs font-bold text-white">{{ auth()->user()->initials() }}</span>
                        <x-icon name="chevron-down" class="h-4 w-4" />
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition style="display:none" class="glass-strong absolute right-0 mt-2 w-52 rounded-2xl p-2">
                        <p class="px-3 py-2 text-xs text-muted">{{ auth()->user()->role->label() }}</p>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-body hover:surface"><x-icon name="home" class="h-4 w-4" /> User dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-rose-400 hover:surface"><x-icon name="logout" class="h-4 w-4" /> Log out</button></form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-none px-4 py-6 sm:px-6 lg:py-8">
            <x-flash />
            @yield('content')
        </main>
    </div>
</body>
</html>
