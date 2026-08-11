@php
    $navLabel = fn (string $key, string $label) => ['key' => $key, 'label' => $label];

    // Accordion: exactly one of these five sections is open at a time.
    // Whichever one contains the current page is the one that starts open —
    // no localStorage, no per-section memory, nothing to get out of sync.
    // Every full page navigation recomputes this fresh from the URL.
    $sectionRoutePatterns = [
        'money' => ['wallet.*', 'deposit.*', 'funding.create', 'transactions.*', 'withdrawals.*', 'payment-methods.*'],
        'shop' => ['shop.*', 'cart.*', 'wishlist.*', 'esim.mine.*'],
        'china' => ['beneficiaries.*', 'funding.index', 'funding.show', 'marketplace.*', 'shipping-requests.*', 'shipments.track'],
        'account' => ['profile.edit', 'verification.*', 'security.*', 'notifications.*', 'referrals.*'],
        'help' => ['learning.*', 'public.faqs', 'disputes.*', 'refunds.*'],
    ];
    $activeSection = null;
    foreach ($sectionRoutePatterns as $sectionKey => $patterns) {
        if (request()->routeIs(...$patterns)) {
            $activeSection = $sectionKey;
            break;
        }
    }
@endphp

<p class="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-wider text-slate-500" data-nav-section>{{ __('Overview') }}</p>
<x-nav-link :href="route('dashboard')" img="House-Chimney-1--Streamline-Ultimate.png" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-nav-link>

<div x-data="{ openSection: @js($activeSection) }">
    @php $s = $navLabel('money', __('Money')); @endphp
    <button type="button" @click="openSection = (openSection === '{{ $s['key'] }}' ? null : '{{ $s['key'] }}')"
            class="flex w-full items-center justify-between px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500" data-nav-section>
        <span>{{ $s['label'] }}</span>
        <x-icon name="chevron-down" class="h-3 w-3 shrink-0 transition-transform duration-200" ::class="openSection === '{{ $s['key'] }}' ? '' : '-rotate-90'" />
    </button>
    <div x-show="openSection === '{{ $s['key'] }}' || sbc" x-collapse data-nav-items>
        <x-nav-link :href="route('wallet.index')" img="Money-Wallet-1--Streamline-Ultimate.png" :active="request()->routeIs('wallet.*')">{{ __('Wallet') }}</x-nav-link>
        <x-nav-link :href="route('deposit.index')" img="Saving-Bank-Cash--Streamline-Ultimate.png" :active="request()->routeIs('deposit.*')">{{ __('Deposit') }}</x-nav-link>
        <x-nav-link :href="route('funding.create')" img="Currency-Sign-Colon-Bag--Streamline-Ultimate.png" :active="request()->routeIs('funding.create')">{{ __('Fund China Wallet') }}</x-nav-link>
        <x-nav-link :href="route('transactions.index')" img="Receipt-Slip-1--Streamline-Ultimate.png" :active="request()->routeIs('transactions.*')">{{ __('Transactions') }}</x-nav-link>
        @if (\Illuminate\Support\Facades\Route::has('withdrawals.index'))
            <x-nav-link :href="route('withdrawals.index')" img="Money-Bags--Streamline-Ultimate.png" class="nav-item-ash" :active="request()->routeIs('withdrawals.*')">{{ __('Withdraw Funds') }}</x-nav-link>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('payment-methods.index'))
            <x-nav-link :href="route('payment-methods.index')" icon="card" :active="request()->routeIs('payment-methods.*')">{{ __('Saved Payment Methods') }}</x-nav-link>
        @endif
    </div>

    @php $s = $navLabel('shop', __('Shop')); @endphp
    <button type="button" @click="openSection = (openSection === '{{ $s['key'] }}' ? null : '{{ $s['key'] }}')"
            class="flex w-full items-center justify-between px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500" data-nav-section>
        <span>{{ $s['label'] }}</span>
        <x-icon name="chevron-down" class="h-3 w-3 shrink-0 transition-transform duration-200" ::class="openSection === '{{ $s['key'] }}' ? '' : '-rotate-90'" />
    </button>
    <div x-show="openSection === '{{ $s['key'] }}' || sbc" x-collapse data-nav-items>
        @php
            // Marketplace expands inline (hover on desktop, pinned open while
            // browsing shop routes). Categories come live from
            // CategoryNavigationService via the layout's $megaMenuCategories —
            // never hardcoded here.
            $onMarketplace = request()->routeIs('shop.*', 'cart.*', 'wishlist.*');
            $mpCategories = $megaMenuCategories ?? collect();
            $mpBadgeClass = fn (?string $s) => match ($s) {
                'emerald' => 'bg-emerald-500',
                'amber' => 'bg-amber-500',
                'rose' => 'bg-rose-500',
                'slate' => 'bg-slate-500',
                default => 'bg-brand-500',
            };
        @endphp
        <div x-data="{ mp: {{ $onMarketplace ? 'true' : 'false' }} }"
             @mouseenter="mp = true" @mouseleave="mp = {{ $onMarketplace ? 'true' : 'false' }}" @focusin="mp = true">
            <x-nav-link :href="route('shop.index')" img="Shop-Sign-Bag--Streamline-Ultimate.png" id="nav-trigger-marketplace"
                ::aria-expanded="mp.toString()" aria-controls="marketplace-subnav"
                :active="$onMarketplace">
                {{ __('Marketplace') }}
                <x-slot:trailing>
                    <x-icon name="chevron-down" class="h-3.5 w-3.5 shrink-0 text-faint transition-transform duration-200" ::class="mp ? 'rotate-180' : ''" />
                </x-slot:trailing>
            </x-nav-link>
            <div x-show="mp && !sbc" x-collapse id="marketplace-subnav" class="ml-6 space-y-0.5 border-l border-app pl-2.5 pt-0.5" style="display:none">
                <a href="{{ route('shop.index') }}" class="nav-item !py-1.5 text-sm {{ request()->routeIs('shop.index') && ! request('filter') ? 'nav-item-active' : '' }}">
                    <span class="min-w-0 flex-1 truncate">{{ __('All Categories') }}</span>
                </a>
                @foreach ($mpCategories as $mpCategory)
                    @php
                        $mpActive = request()->routeIs('shop.category') && request()->route('category') && (
                            request()->route('category')->id === $mpCategory->id
                            || $mpCategory->children->contains('id', request()->route('category')->id)
                        );
                    @endphp
                    <a href="{{ route('shop.category', $mpCategory->slug) }}" class="nav-item !py-1.5 text-sm {{ $mpActive ? 'nav-item-active' : '' }}">
                        <span class="min-w-0 flex-1 truncate">{{ __($mpCategory->name) }}</span>
                        @if ($mpCategory->navigation_badge)
                            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase text-white {{ $mpBadgeClass($mpCategory->navigation_badge_style) }}">{{ $mpCategory->navigation_badge }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
        <x-nav-link :href="route('shop.orders.index')" icon="receipt" :active="request()->routeIs('shop.orders.index', 'shop.orders.show')">{{ __('My Orders') }}</x-nav-link>
        @if (\Illuminate\Support\Facades\Route::has('wishlist.index'))
            <x-nav-link :href="route('wishlist.index')" icon="heart" :active="request()->routeIs('wishlist.*')">{{ __('Wishlist') }}</x-nav-link>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('shop.orders.digital'))
            <x-nav-link :href="route('shop.orders.digital')" icon="download" :active="request()->routeIs('shop.orders.digital')">{{ __('Digital Purchases') }}</x-nav-link>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('esim.mine.index'))
            <x-nav-link :href="route('esim.mine.index')" icon="sim" :active="request()->routeIs('esim.mine.*')">{{ __('My eSIMs') }}</x-nav-link>
        @endif
    </div>

    @php $s = $navLabel('china', __('China Services')); @endphp
    <button type="button" @click="openSection = (openSection === '{{ $s['key'] }}' ? null : '{{ $s['key'] }}')"
            class="flex w-full items-center justify-between px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500" data-nav-section>
        <span>{{ $s['label'] }}</span>
        <x-icon name="chevron-down" class="h-3 w-3 shrink-0 transition-transform duration-200" ::class="openSection === '{{ $s['key'] }}' ? '' : '-rotate-90'" />
    </button>
    <div x-show="openSection === '{{ $s['key'] }}' || sbc" x-collapse data-nav-items>
        <x-nav-link :href="route('beneficiaries.index')" img="Crypto-Wallet--Streamline-Ultimate.png" :active="request()->routeIs('beneficiaries.*')">{{ __('My China Wallets') }}</x-nav-link>
        <x-nav-link :href="route('funding.index')" icon="clock" :active="request()->routeIs('funding.index', 'funding.show')">{{ __('Funding History') }}</x-nav-link>
        <x-nav-link :href="route('marketplace.index')" img="Shipment-Package--Streamline-Ultimate.png" :active="request()->routeIs('marketplace.*')">{{ __('Shipping Agents') }}</x-nav-link>
        @if (\Illuminate\Support\Facades\Route::has('shipping-requests.index'))
            <x-nav-link :href="route('shipping-requests.index')" icon="truck" :active="request()->routeIs('shipping-requests.*')">
                {{ __('Shipping Requests') }}
                @if (($navBadges['shipping_requests_new_update'] ?? 0) > 0)
                    <x-slot:trailing><span class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $navBadges['shipping_requests_new_update'] > 9 ? '9+' : $navBadges['shipping_requests_new_update'] }}</span></x-slot:trailing>
                @endif
            </x-nav-link>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('shipments.track'))
            <x-nav-link :href="route('shipments.track')" icon="search" :active="request()->routeIs('shipments.track')">{{ __('Track Shipment') }}</x-nav-link>
        @endif
    </div>

    @php $s = $navLabel('account', __('Account')); @endphp
    <button type="button" @click="openSection = (openSection === '{{ $s['key'] }}' ? null : '{{ $s['key'] }}')"
            class="flex w-full items-center justify-between px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500" data-nav-section>
        <span>{{ $s['label'] }}</span>
        <x-icon name="chevron-down" class="h-3 w-3 shrink-0 transition-transform duration-200" ::class="openSection === '{{ $s['key'] }}' ? '' : '-rotate-90'" />
    </button>
    <div x-show="openSection === '{{ $s['key'] }}' || sbc" x-collapse data-nav-items>
        <x-nav-link :href="route('profile.edit')" icon="user" :active="request()->routeIs('profile.edit')">{{ __('Profile') }}</x-nav-link>
        <x-nav-link :href="route('verification.index')" icon="user-circle" :active="request()->routeIs('verification.*')">
            {{ __('Identity Verification') }}
            @if ($navBadges['verification_action_required'] ?? false)
                <x-slot:trailing><span class="h-2 w-2 shrink-0 rounded-full bg-amber-500" title="{{ __('Action required') }}"></span></x-slot:trailing>
            @endif
        </x-nav-link>
        <x-nav-link :href="route('security.index')" icon="shield" :active="request()->routeIs('security.*')">
            {{ __('Security & Devices') }}
            @if ($navBadges['security_alert'] ?? false)
                <x-slot:trailing><span class="h-2 w-2 shrink-0 rounded-full bg-rose-500" title="{{ __('Security alert') }}"></span></x-slot:trailing>
            @endif
        </x-nav-link>
        <x-nav-link :href="route('notifications.index')" icon="bell" :active="request()->routeIs('notifications.*')">
            {{ __('Notifications') }}
            <x-slot:trailing>
                @if ($unread)<span class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
            </x-slot:trailing>
        </x-nav-link>
        <x-nav-link :href="route('referrals.index')" icon="users" :active="request()->routeIs('referrals.*')">
            {{ __('Referrals & Rewards') }}
            @if ($navBadges['referral_reward_available'] ?? false)
                <x-slot:trailing><span class="shrink-0 rounded-md bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ __('Reward') }}</span></x-slot:trailing>
            @endif
        </x-nav-link>
    </div>

    @php $s = $navLabel('help', __('Help & Learning')); @endphp
    <button type="button" @click="openSection = (openSection === '{{ $s['key'] }}' ? null : '{{ $s['key'] }}')"
            class="flex w-full items-center justify-between px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500" data-nav-section>
        <span>{{ $s['label'] }}</span>
        <x-icon name="chevron-down" class="h-3 w-3 shrink-0 transition-transform duration-200" ::class="openSection === '{{ $s['key'] }}' ? '' : '-rotate-90'" />
    </button>
    <div x-show="openSection === '{{ $s['key'] }}' || sbc" x-collapse data-nav-items>
        <x-nav-link :href="route('learning.index')" img="Online-Learning-School-1--Streamline-Ultimate.png" :active="request()->routeIs('learning.*')">{{ __('Learning Center') }}</x-nav-link>
        <x-nav-link :href="route('public.faqs')" icon="help" :active="request()->routeIs('public.faqs')">{{ __('Help Center') }}</x-nav-link>
        <x-nav-link :href="route('disputes.index')" img="Headphones-Customer-Support-Human-1--Streamline-Ultimate.png" :active="request()->routeIs('disputes.*')">
            {{ __('Support Tickets') }}
            @if (($navBadges['support_awaiting_you'] ?? 0) > 0)
                <x-slot:trailing><span class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $navBadges['support_awaiting_you'] > 9 ? '9+' : $navBadges['support_awaiting_you'] }}</span></x-slot:trailing>
            @endif
        </x-nav-link>
        @if (\Illuminate\Support\Facades\Route::has('refunds.index'))
            <x-nav-link :href="route('refunds.index')" icon="refresh" :active="request()->routeIs('refunds.*')">{{ __('Disputes & Refunds') }}</x-nav-link>
        @endif
    </div>
</div>
