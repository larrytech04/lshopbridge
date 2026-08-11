<div x-data="appDock()" x-on:open-mobile-menu.window="menu = true" x-on:close-dock-menu.window="menu = false" class="pointer-events-none fixed inset-x-0 bottom-0 z-40 flex justify-center pb-[max(0.625rem,env(safe-area-inset-bottom))] lg:hidden">
    {{-- Thin frosted strip filling the gap between the dock and the screen edge,
         so whatever scrolls beneath is blurred rather than sharply visible. --}}
    <div class="pointer-events-none absolute inset-x-0 bottom-0 z-0 backdrop-blur-md" style="height: max(0.625rem, env(safe-area-inset-bottom));"></div>

    {{-- Slide-up menu sheet --}}
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

        {{-- Same links as the desktop sidebar, grouped into the same sections, just as
             big colour-circle shortcuts instead of a plain list. Marketplace is the one
             richer tile: it opens the full-screen category drawer (see
             partials/marketplace-menu.blade.php) instead of navigating directly. --}}
        @php
            $menuSections = [
                __('Money') => [
                    [route('dashboard'), __('Dashboard'), 'House-Chimney-1--Streamline-Ultimate.png', null, '#7C5CFC'],
                    [route('wallet.index'), __('Wallet'), 'Money-Wallet-1--Streamline-Ultimate.png', null, '#3B82F6'],
                    [route('deposit.index'), __('Deposit'), 'Saving-Bank-Cash--Streamline-Ultimate.png', null, '#10B981'],
                    [route('funding.create'), __('Fund China Wallet'), 'Currency-Sign-Colon-Bag--Streamline-Ultimate.png', null, '#F97316'],
                    [route('transactions.index'), __('Transactions'), 'Receipt-Slip-1--Streamline-Ultimate.png', null, '#14B8A6'],
                    ...(\Illuminate\Support\Facades\Route::has('withdrawals.index') ? [[route('withdrawals.index'), __('Withdraw Funds'), 'Money-Bags--Streamline-Ultimate.png', null, '#DC2626']] : []),
                    ...(\Illuminate\Support\Facades\Route::has('payment-methods.index') ? [[route('payment-methods.index'), __('Saved Payment Methods'), null, 'card', '#0284C7']] : []),
                ],
                __('Shop') => [
                    [route('shop.orders.index'), __('My Orders'), null, 'receipt', '#06B6D4'],
                    ...(\Illuminate\Support\Facades\Route::has('wishlist.index') ? [[route('wishlist.index'), __('Wishlist'), null, 'heart', '#DB2777']] : []),
                    ...(\Illuminate\Support\Facades\Route::has('shop.orders.digital') ? [[route('shop.orders.digital'), __('Digital Purchases'), null, 'download', '#7C3AED']] : []),
                    ...(\Illuminate\Support\Facades\Route::has('esim.mine.index') ? [[route('esim.mine.index'), __('My eSIMs'), null, 'sim', '#059669']] : []),
                ],
                __('China Services') => [
                    [route('beneficiaries.index'), __('My China Wallets'), 'Crypto-Wallet--Streamline-Ultimate.png', null, '#8B5CF6'],
                    [route('funding.index'), __('Funding History'), null, 'clock', '#F59E0B'],
                    [route('marketplace.index'), __('Shipping Agents'), 'Shipment-Package--Streamline-Ultimate.png', null, '#0EA5E9'],
                    ...(\Illuminate\Support\Facades\Route::has('shipping-requests.index') ? [[route('shipping-requests.index'), __('Shipping Requests'), null, 'truck', '#0369A1']] : []),
                    ...(\Illuminate\Support\Facades\Route::has('shipments.track') ? [[route('shipments.track'), __('Track Shipment'), null, 'search', '#0891B2']] : []),
                ],
                __('Account') => [
                    [route('profile.edit'), __('Profile'), null, 'user', '#475569'],
                    [route('verification.index'), __('Identity Verification'), null, 'user-circle', '#F59E0B'],
                    [route('security.index'), __('Security & Devices'), null, 'shield', '#64748B'],
                    [route('notifications.index'), __('Notifications'), null, 'bell', '#D946EF'],
                    [route('referrals.index'), __('Referrals & Rewards'), null, 'users', '#22C55E'],
                ],
                __('Help & Learning') => [
                    [route('learning.index'), __('Learning Center'), 'Online-Learning-School-1--Streamline-Ultimate.png', null, '#F59E0B'],
                    [route('public.faqs'), __('Help Center'), null, 'help', '#0EA5E9'],
                    [route('disputes.index'), __('Support Tickets'), 'Headphones-Customer-Support-Human-1--Streamline-Ultimate.png', null, '#EF4444'],
                    ...(\Illuminate\Support\Facades\Route::has('refunds.index') ? [[route('refunds.index'), __('Disputes & Refunds'), null, 'refresh', '#EA580C']] : []),
                ],
            ];
        @endphp
        <div class="space-y-2.5">
            <div class="card-solid rounded-2xl border border-app p-2.5">
                <p class="px-1 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-faint">{{ __('Shop') }}</p>
                <div class="grid grid-cols-3 gap-1.5">
                    @include('partials.marketplace-menu', ['megaMenuCategories' => $megaMenuCategories ?? collect()])
                    @foreach ($menuSections[__('Shop')] as [$url, $label, $img, $icon, $tint])
                        <a href="{{ $url }}" class="group flex flex-col items-center gap-1 rounded-xl p-1 text-center text-[11px] font-medium leading-tight text-body transition hover:-translate-y-0.5">
                            <span class="grid h-9 w-9 place-items-center rounded-full text-white shadow-sm transition group-hover:shadow-lg" style="background: {{ $tint }}">
                                @if ($img)<x-img-icon :name="$img" class="h-4 w-4" />@else<x-icon :name="$icon" class="h-4 w-4" />@endif
                            </span>
                            <span class="line-clamp-2">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            @foreach ($menuSections as $section => $items)
                @continue($section === __('Shop'))
                <div class="card-solid rounded-2xl border border-app p-2.5">
                    <p class="px-1 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-faint">{{ $section }}</p>
                    <div class="grid grid-cols-3 gap-1.5">
                        @foreach ($items as [$url, $label, $img, $icon, $tint])
                            <a href="{{ $url }}" class="group flex flex-col items-center gap-1 rounded-xl p-1 text-center text-[11px] font-medium leading-tight text-body transition hover:-translate-y-0.5">
                                <span class="grid h-9 w-9 place-items-center rounded-full text-white shadow-sm transition group-hover:shadow-lg" style="background: {{ $tint }}">
                                    @if ($img)<x-img-icon :name="$img" class="h-4 w-4" />@else<x-icon :name="$icon" class="h-4 w-4" />@endif
                                </span>
                                <span class="line-clamp-2">{{ $label }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 border-t border-app pt-4">
            @auth
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost w-full text-sm"><x-icon name="logout" class="h-4 w-4" /> {{ __('Log out') }}</button></form>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary w-full text-sm">{{ __('Log in') }}</a>
            @endauth
        </div>
    </div>

    {{-- Dock --}}
    @php
        $home = auth()->check() ? route('dashboard') : route('home');
        $wallet = auth()->check() ? route('wallet.index') : route('login');
        $fund = auth()->check() ? route('funding.create') : route('login');
        $activeDock = request()->routeIs('wallet.*') ? 1
            : (request()->routeIs('funding.create') ? 2
            : (request()->routeIs('shop.*', 'cart.*') ? 3 : 0));
    @endphp
    {{-- DOM order stays Home/Wallet/Fund/Marketplace/Menu (matches $activeDock's indices
         and the JS's "last slot = Menu" assumption) — only the visual order changes, via
         `order-*`, to Home/Fund/Menu/Marketplace/Wallet. The sliding indicator positions
         itself from each slot's rendered offsetLeft, so it already follows the visual
         order without any JS changes. --}}
    <nav class="app-dock pointer-events-auto relative z-10 mx-3 flex w-[calc(100%-1.5rem)] max-w-xl items-center justify-between gap-1 rounded-full px-2 py-1.5"
         data-active="{{ $activeDock }}">
        <span data-dock-indicator class="dock-indicator"></span>
        <a data-dock-slot href="{{ $home }}" class="dock-item order-1 {{ request()->routeIs('dashboard','home') ? 'dock-item-active' : '' }}"><x-img-icon name="House-Chimney-1--Streamline-Ultimate.png" class="h-5 w-5" /> {{ __('Home') }}</a>
        <a data-dock-slot href="{{ $wallet }}" class="dock-item order-5 {{ request()->routeIs('wallet.*') ? 'dock-item-active' : '' }}"><x-img-icon name="Money-Wallet-1--Streamline-Ultimate.png" class="h-5 w-5" /> {{ __('Wallet') }}</a>
        <a data-dock-slot href="{{ $fund }}" class="dock-item order-2 {{ request()->routeIs('funding.create') ? 'dock-item-active' : '' }}"><x-img-icon name="Currency-Sign-Colon-Bag--Streamline-Ultimate.png" class="h-5 w-5" /> {{ __('Fund') }}</a>
        <a data-dock-slot href="{{ route('shop.index') }}" class="dock-item order-4 {{ request()->routeIs('shop.*','cart.*') ? 'dock-item-active' : '' }}"><x-img-icon name="Shop-Sign-Bag--Streamline-Ultimate.png" class="h-5 w-5" /> {{ __('Marketplace') }}</a>
        <button data-dock-slot @click="menu=!menu" class="dock-item order-3" :class="menu ? 'dock-item-active' : ''"><x-img-icon name="menu.png" class="h-5 w-5" /> {{ __('Menu') }}</button>
    </nav>
</div>
