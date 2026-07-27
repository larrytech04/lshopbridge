<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Admin') · {{ setting('site_name', config('platform.name')) }}</title>
    @include('partials.theme-head')
    {{-- Plus Jakarta Sans is self-hosted (bundled via app.css); no external font host. --}}
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
            'withdrawals' => \App\Models\WithdrawalRequest::whereIn('status', ['pending', 'approved'])->count(),
            'funding' => \App\Models\FundingRequest::whereIn('status', ['manual_review', 'funding_processing'])->count(),
            'reviews' => \App\Models\Review::where('status', 'pending')->count(),
            'disputes' => \App\Models\Dispute::whereIn('status', ['open', 'in_progress'])->count(),
            'risk' => \App\Models\RiskFlag::where('status', 'open')->count(),
            'shop' => \App\Models\ShopOrder::where('status', 'pending')->count(),
            'esim' => \App\Models\EsimProvisioning::where('status', 'pending_provisioning')->count(),
            'referral_leads' => \App\Models\ReferralLead::where('status', 'new')->count(),
            'guest_support' => \App\Models\GuestSupportTicket::where('status', 'open')->count(),
            'spam_review' => \App\Models\SpamReviewCase::where('status', 'pending_review')->count(),
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
            <x-admin-nav route="admin.referral-leads.*" :href="route('admin.referral-leads.index')" icon="user-circle" :badge="$q['referral_leads']">Referral leads</x-admin-nav>
            <x-admin-nav route="admin.beneficiaries.*" :href="route('admin.beneficiaries.index')" img="Yuan--Streamline-Plump.png" raw :badge="$q['beneficiaries']">China wallets</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Money</p>
            <x-admin-nav route="admin.deposits.*" :href="route('admin.deposits.index')" img="Credit-Card-Receive--Streamline-Sharp-Streamline-Material.png" raw :badge="$q['deposits']">Deposits</x-admin-nav>
            <x-admin-nav route="admin.withdrawals.*" :href="route('admin.withdrawals.index')" img="Money-Bags--Streamline-Ultimate.png" raw class="nav-item-ash" :badge="$q['withdrawals']">Withdrawals</x-admin-nav>
            <x-admin-nav route="admin.funding.*" :href="route('admin.funding.index')" img="Real-Estate-Insurance-Dollar-Hand-House--Streamline-Freehand.png" raw :badge="$q['funding']">Funding</x-admin-nav>
            <x-admin-nav route="admin.rates.*" :href="route('admin.rates.index')" img="Cash-Exchange-Rate--Streamline-Flex.png" raw>Exchange rates</x-admin-nav>
            <x-admin-nav route="admin.fees.*" :href="route('admin.fees.index')" img="Incognito-Mode--Streamline-Core-Remix.png" raw>Fees</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Commerce</p>
            <x-admin-nav route="admin.shop.products.*" :href="route('admin.shop.products.index')" img="Shop-Open--Streamline-Freehand.svg" raw>Products</x-admin-nav>
            <x-admin-nav route="admin.shop.categories.*" :href="route('admin.shop.categories.index')" img="Products-Shopping-Bags--Streamline-Ultimate.png" raw>Categories</x-admin-nav>
            <x-admin-nav route="admin.shop.orders.*" :href="route('admin.shop.orders.index')" img="Receipt-Slip-1--Streamline-Ultimate.png" raw :badge="$q['shop']">Shop orders</x-admin-nav>
            <x-admin-nav route="admin.esim.*" :href="route('admin.esim.provisioning.index')" img="Sim-Card-5g--Streamline-Ultimate.png" raw :badge="$q['esim']">eSIM operations</x-admin-nav>
            <x-admin-nav route="admin.shop.imports.*" :href="route('admin.shop.imports.index')" img="Trading-Website-Network--Streamline-Ultimate.png" raw>Import Center</x-admin-nav>
            <x-admin-nav route="admin.shop.suppliers.*" :href="route('admin.shop.suppliers.index')" img="Delivery-Package-Give--Streamline-Freehand.png" raw>Suppliers</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Platform Configuration · Payments</p>
            <x-admin-nav route="admin.methods.*" :href="route('admin.methods.index')" img="Credit-Card--Streamline-Ultimate.png" raw>Payment methods</x-admin-nav>
            <x-admin-nav route="admin.providers.*" :href="route('admin.providers.index')" img="Trading-Website-Network--Streamline-Ultimate.png" raw>Payment providers</x-admin-nav>
            <x-admin-nav route="admin.channels.*" :href="route('admin.channels.index')" img="Saving-Bank-Cash--Streamline-Ultimate.png" raw>Deposit accounts</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Platform Configuration · Regions &amp; Currencies</p>
            <x-admin-nav route="admin.countries.*" :href="route('admin.countries.index')" img="Earth-Search--Streamline-Ultimate.png" raw>Countries &amp; regions</x-admin-nav>
            <x-admin-nav route="admin.currencies.*" :href="route('admin.currencies.index')" img="Cash-Exchange-Rate--Streamline-Flex.png" raw>Currencies</x-admin-nav>
            <x-admin-nav route="admin.china-wallet-types.*" :href="route('admin.china-wallet-types.index')" img="Yuan--Streamline-Plump.png" raw>China wallet types</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Content</p>
            <x-admin-nav route="admin.guides.*" :href="route('admin.guides.index')" img="Study-Book--Streamline-Ultimate.png" raw>Guides</x-admin-nav>
            <x-admin-nav route="admin.faqs.*" :href="route('admin.faqs.index')" img="Information-Desk-Question-Help--Streamline-Ultimate.png" raw>FAQs</x-admin-nav>
            <x-admin-nav route="admin.banners.*" :href="route('admin.banners.index')" img="Picture-Polaroid-Album--Streamline-Ultimate.png" raw>Banners</x-admin-nav>
            <x-admin-nav route="admin.pages.*" :href="route('admin.pages.index')" img="Text-Image-Left-2--Streamline-Ultimate.png" raw>Legal pages</x-admin-nav>
            <x-admin-nav route="admin.content.*" :href="route('admin.content.index')" img="Ui-Webpage-Bullets--Streamline-Ultimate.png" raw>Page content</x-admin-nav>
            <x-admin-nav route="admin.newsletter.*" :href="route('admin.newsletter.index')" icon="mail">Newsletter</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">Trust &amp; safety</p>
            <x-admin-nav route="admin.reviews.*" :href="route('admin.reviews.index')" img="Leave-Review-1--Streamline-Brooklyn.png" raw :badge="$q['reviews']">Reviews</x-admin-nav>
            <x-admin-nav route="admin.disputes.*" :href="route('admin.disputes.index')" img="Customer-Relationship-Management-Call-Center-Support--Streamline-Ultimate.png" raw :badge="$q['disputes']">Disputes</x-admin-nav>
            <x-admin-nav route="admin.support-tickets.*" :href="route('admin.support-tickets.index')" icon="help" :badge="$q['guest_support']">Guest support</x-admin-nav>
            <x-admin-nav route="admin.risk.*" :href="route('admin.risk.index')" img="Identity-Theft--Streamline-Brooklyn.png" raw :badge="$q['risk']">Risk &amp; fraud</x-admin-nav>
            <x-admin-nav route="admin.security-events.*" :href="route('admin.security-events.index')" icon="shield" :badge="$q['spam_review']">Security events</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">System &amp; Operations &middot; Overview</p>
            <x-admin-nav route="admin.system.index" :href="route('admin.system.index')" icon="gauge">System overview</x-admin-nav>
            <x-admin-nav route="admin.api-health.*" :href="route('admin.api-health.index')" img="Customer-Relationship-Management-Performance-Metrics--Streamline-Ultimate.png" raw>API &amp; provider health</x-admin-nav>
            <x-admin-nav route="admin.integrations.*" :href="route('admin.integrations.index')" img="Password-Key--Streamline-Ultimate.png" raw>Integrations hub</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">System &amp; Operations &middot; Operations</p>
            <x-admin-nav route="admin.webhooks.*" :href="route('admin.webhooks.index')" img="Customer-Relationship-Management-Performance-Metrics--Streamline-Ultimate.png" raw>Webhook monitor</x-admin-nav>
            <x-admin-nav route="admin.queues.*" :href="route('admin.queues.index')" icon="clock">Jobs &amp; queues</x-admin-nav>
            <x-admin-nav route="admin.scheduler.*" :href="route('admin.scheduler.index')" icon="refresh">Scheduler &amp; cron</x-admin-nav>
            <x-admin-nav route="admin.storage.*" :href="route('admin.storage.index')" icon="doc">Storage &amp; files</x-admin-nav>
            <x-admin-nav route="admin.cache.*" :href="route('admin.cache.index')" icon="cog">Cache management</x-admin-nav>

            <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-faint">System &amp; Operations &middot; Administration</p>
            <x-admin-nav route="admin.settings.*" :href="route('admin.settings.index')" img="Settings-User--Streamline-Ultimate.png" raw>Platform settings</x-admin-nav>
            <x-admin-nav route="admin.audit.*" :href="route('admin.audit.index')" img="Analyze-Data-4--Streamline-Brooklyn.png" raw>Audit logs</x-admin-nav>
            <x-admin-nav route="admin.system-info.*" :href="route('admin.system-info.index')" icon="info">System information</x-admin-nav>
        </nav>
    </aside>

    <div x-show="sidebar" @click="sidebar = false" x-transition.opacity class="fixed inset-0 z-40 bg-black/50 lg:hidden" style="display:none"></div>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 px-4 sm:px-6" style="background: var(--header-bg);">
            <button @click="sidebar = true" class="rounded-lg p-2 text-muted hover:surface-2 lg:hidden"><x-icon name="menu" class="h-6 w-6" /></button>
            <h1 class="hidden text-base font-semibold text-strong sm:block sm:text-lg">@yield('page-title', 'Overview')</h1>

            {{-- Same search-bar pattern as the user dashboard header, targeting the admin users search. --}}
            <form action="{{ route('admin.users.index') }}" method="GET" class="relative mx-auto hidden max-w-md flex-1 sm:block">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                <input name="q" value="{{ request()->routeIs('admin.users.*') ? request('q') : '' }}" placeholder="{{ __('Search users by name, email, phone…') }}"
                       class="field pb-ash-field !rounded-full !py-2 pl-11 pr-16 text-sm">
                <kbd class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full border border-app px-2 py-0.5 text-[10px] font-semibold uppercase text-faint">{{ __('Enter') }}</kbd>
            </form>
            <a href="{{ route('admin.users.index') }}" aria-label="{{ __('Search') }}" class="grid h-9 w-9 place-items-center rounded-full text-muted transition hover:surface-2 hover:text-strong sm:hidden">
                <x-icon name="search" class="h-5 w-5" />
            </a>

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

        <main class="mx-auto max-w-none px-4 py-6 pb-28 sm:px-6 lg:py-8 lg:pb-10">
            <x-flash />
            @yield('content')
        </main>
    </div>

    @include('partials.admin-bottom-dock')
    @include('partials.shortcuts')
    @stack('scripts')
</body>
</html>
