{{-- Mobile/tablet admin nav: same dock + slide-up sheet pattern as the user dashboard
     (partials/bottom-dock.blade.php), reusing the exact icons/routes/badges already
     defined in the desktop sidebar (layouts/admin.blade.php). --}}
<div x-data="appDock()" x-on:open-mobile-menu.window="menu = true" class="pointer-events-none fixed inset-x-0 bottom-0 z-40 flex justify-center pb-[max(1rem,env(safe-area-inset-bottom))] lg:hidden">
    <div x-show="menu" x-cloak @click="menu=false" class="pointer-events-auto fixed inset-0 bg-black/40" x-transition.opacity></div>
    <div x-show="menu" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-6"
         class="glass-strong pointer-events-auto fixed inset-x-3 bottom-24 z-50 mx-auto max-h-[75vh] max-w-md overflow-y-auto rounded-3xl p-2.5 shadow-2xl" style="display:none">
        <div class="sticky top-0 mx-auto mb-1.5 h-1.5 w-10 rounded-full surface-2"></div>

        @php
            $adminMenuSections = [
                'People' => [
                    [route('admin.users.index'), 'Users', 'Multiple-Users-1--Streamline-Ultimate.svg', '#3B82F6', 0],
                    [route('admin.kyc.index'), 'KYC review', 'Work-Pending-For-Review--Streamline-Bangalore.png', '#F59E0B', $q['kyc']],
                    [route('admin.agents.index'), 'Agents', 'Delivery-Package-Give--Streamline-Freehand.png', '#0EA5E9', $q['agents']],
                    [route('admin.beneficiaries.index'), 'China wallets', 'Yuan--Streamline-Plump.png', '#8B5CF6', $q['beneficiaries']],
                ],
                'Money' => [
                    [route('admin.deposits.index'), 'Deposits', 'Credit-Card-Receive--Streamline-Sharp-Streamline-Material.png', '#10B981', $q['deposits']],
                    [route('admin.funding.index'), 'Funding', 'Real-Estate-Insurance-Dollar-Hand-House--Streamline-Freehand.png', '#F97316', $q['funding']],
                    [route('admin.rates.index'), 'Exchange rates', 'Cash-Exchange-Rate--Streamline-Flex.png', '#14B8A6', 0],
                    [route('admin.fees.index'), 'Fees', 'Incognito-Mode--Streamline-Core-Remix.png', '#D946EF', 0],
                ],
                'Shop' => [
                    [route('admin.shop.products.index'), 'Products', 'Shop-Open--Streamline-Freehand.svg', '#EC4899', 0],
                    [route('admin.shop.categories.index'), 'Categories', 'Products-Shopping-Bags--Streamline-Ultimate.png', '#F97316', 0],
                    [route('admin.shop.orders.index'), 'Shop orders', 'Receipt-Slip-1--Streamline-Ultimate.png', '#06B6D4', $q['shop']],
                ],
                'Config' => [
                    [route('admin.methods.index'), 'Payment methods', 'Credit-Card--Streamline-Ultimate.png', '#3B82F6', 0],
                    [route('admin.providers.index'), 'Providers', 'Trading-Website-Network--Streamline-Ultimate.png', '#8B5CF6', 0],
                    [route('admin.channels.index'), 'Deposit accounts', 'Saving-Bank-Cash--Streamline-Ultimate.png', '#10B981', 0],
                    [route('admin.countries.index'), 'Countries', 'Earth-Search--Streamline-Ultimate.png', '#0EA5E9', 0],
                ],
                'Content' => [
                    [route('admin.guides.index'), 'Guides', 'Study-Book--Streamline-Ultimate.png', '#F59E0B', 0],
                    [route('admin.faqs.index'), 'FAQs', 'Information-Desk-Question-Help--Streamline-Ultimate.png', '#14B8A6', 0],
                    [route('admin.banners.index'), 'Banners', 'Picture-Polaroid-Album--Streamline-Ultimate.png', '#EC4899', 0],
                    [route('admin.pages.index'), 'Legal pages', 'Text-Image-Left-2--Streamline-Ultimate.png', '#64748B', 0],
                    [route('admin.content.index'), 'Page content', 'Ui-Webpage-Bullets--Streamline-Ultimate.png', '#7C5CFC', 0],
                ],
                'Trust & safety' => [
                    [route('admin.reviews.index'), 'Reviews', 'Leave-Review-1--Streamline-Brooklyn.png', '#F59E0B', $q['reviews']],
                    [route('admin.disputes.index'), 'Disputes', 'Customer-Relationship-Management-Call-Center-Support--Streamline-Ultimate.png', '#EF4444', $q['disputes']],
                    [route('admin.risk.index'), 'Risk & fraud', 'Identity-Theft--Streamline-Brooklyn.png', '#DC2626', $q['risk']],
                    [route('admin.webhooks.index'), 'Webhook logs', 'Customer-Relationship-Management-Performance-Metrics--Streamline-Ultimate.png', '#06B6D4', 0],
                ],
                'System' => [
                    [route('admin.integrations.index'), 'Integrations', 'Password-Key--Streamline-Ultimate.png', '#7C5CFC', 0],
                    [route('admin.settings.index'), 'Settings', 'Settings-User--Streamline-Ultimate.png', '#64748B', 0],
                    [route('admin.audit.index'), 'Audit logs', 'Analyze-Data-4--Streamline-Brooklyn.png', '#0EA5E9', 0],
                ],
            ];
        @endphp
        <div class="space-y-2.5">
            @foreach ($adminMenuSections as $section => $items)
                <div class="card-solid rounded-2xl border border-app p-2.5">
                    <p class="px-1 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-faint">{{ $section }}</p>
                    <div class="grid grid-cols-3 gap-1.5">
                        @foreach ($items as [$url, $label, $img, $tint, $count])
                            <a href="{{ $url }}" class="group relative flex flex-col items-center gap-1 rounded-xl p-1 text-center text-[11px] font-medium leading-tight text-body transition hover:-translate-y-0.5">
                                <span class="relative grid h-9 w-9 place-items-center rounded-full text-white shadow-sm transition group-hover:shadow-lg" style="background: {{ $tint }}">
                                    <x-img-icon :name="$img" class="h-4 w-4" />
                                    @if ($count > 0)<span class="absolute -right-1 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white ring-2" style="--tw-ring-color: var(--surface-1)">{{ $count }}</span>@endif
                                </span>
                                <span class="line-clamp-2">{{ __($label) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 border-t border-app pt-4">
            <a href="{{ route('dashboard') }}" class="btn btn-ghost mb-2 w-full text-sm"><x-icon name="home" class="h-4 w-4" /> {{ __('User dashboard') }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost w-full text-sm"><x-icon name="logout" class="h-4 w-4" /> {{ __('Log out') }}</button></form>
        </div>
    </div>

    {{-- Dock --}}
    @php
        $adminActiveDock = request()->routeIs('admin.deposits.*') ? 1
            : (request()->routeIs('admin.funding.*') ? 2
            : (request()->routeIs('admin.users.*') ? 3 : 0));
    @endphp
    <nav class="app-dock pointer-events-auto relative mx-3 flex w-[calc(100%-1.5rem)] max-w-xl items-center justify-between gap-1 rounded-full px-2 py-2"
         data-active="{{ $adminActiveDock }}">
        <span data-dock-indicator class="dock-indicator"></span>
        <a data-dock-slot href="{{ route('admin.dashboard') }}" class="dock-item {{ request()->routeIs('admin.dashboard') ? 'dock-item-active' : '' }}"><x-img-icon name="Dashboard-3--Streamline-Plump.png" class="h-5 w-5" /> {{ __('Overview') }}</a>
        <a data-dock-slot href="{{ route('admin.deposits.index') }}" class="dock-item {{ request()->routeIs('admin.deposits.*') ? 'dock-item-active' : '' }}"><x-img-icon name="Credit-Card-Receive--Streamline-Sharp-Streamline-Material.png" class="h-5 w-5" /> {{ __('Deposits') }}</a>
        <a data-dock-slot href="{{ route('admin.funding.index') }}" class="dock-item {{ request()->routeIs('admin.funding.*') ? 'dock-item-active' : '' }}"><x-img-icon name="Real-Estate-Insurance-Dollar-Hand-House--Streamline-Freehand.png" class="h-5 w-5" /> {{ __('Funding') }}</a>
        <a data-dock-slot href="{{ route('admin.users.index') }}" class="dock-item {{ request()->routeIs('admin.users.*') ? 'dock-item-active' : '' }}"><x-img-icon name="Multiple-Users-1--Streamline-Ultimate.svg" class="h-5 w-5" /> {{ __('Users') }}</a>
        <button data-dock-slot @click="menu=!menu" class="dock-item" :class="menu ? 'dock-item-active' : ''"><x-img-icon name="menu.png" class="h-5 w-5" /> {{ __('Menu') }}</button>
    </nav>
</div>
