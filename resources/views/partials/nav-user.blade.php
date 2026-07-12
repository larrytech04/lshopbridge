<p class="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Money') }}</p>
<x-nav-link :href="route('dashboard')" img="House-Chimney-1--Streamline-Ultimate.png" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-nav-link>
<x-nav-link :href="route('wallet.index')" img="Money-Wallet-1--Streamline-Ultimate.png" :active="request()->routeIs('wallet.*')">{{ __('Wallet') }}</x-nav-link>
<x-nav-link :href="route('deposit.index')" img="Saving-Bank-Cash--Streamline-Ultimate.png" :active="request()->routeIs('deposit.*')">{{ __('Deposit') }}</x-nav-link>
<x-nav-link :href="route('funding.index')" img="Currency-Sign-Colon-Bag--Streamline-Ultimate.png" :active="request()->routeIs('funding.*')">{{ __('Fund Alipay') }}</x-nav-link>
<x-nav-link :href="route('transactions.index')" img="Receipt-Slip-1--Streamline-Ultimate.png" :active="request()->routeIs('transactions.*')">{{ __('Transactions') }}</x-nav-link>

<p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Shop') }}</p>
@php $sbShopCats = \App\Models\ShopCategory::active()->topLevel()->get(['id', 'name', 'slug']); @endphp
<div x-data="{ shopOpen: @js(request()->routeIs('shop.*', 'cart.*')) }"
     @mouseenter="shopOpen = true" @mouseleave="shopOpen = @js(request()->routeIs('shop.*', 'cart.*'))">
    <button type="button" @click="shopOpen = !shopOpen" :aria-expanded="shopOpen"
            class="nav-item w-full {{ request()->routeIs('shop.*', 'cart.*') ? 'nav-item-active' : '' }}">
        <x-img-icon name="Shop-Sign-Bag--Streamline-Ultimate.png" class="h-5 w-5 shrink-0" />
        <span class="flex-1 text-left">{{ __('Shop') }}</span>
        <span class="rounded-md bg-brand-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ __('Popular') }}</span>
        <x-icon name="chevron-down" class="h-4 w-4 shrink-0 transition-transform duration-300" ::class="shopOpen ? 'rotate-180' : ''" />
    </button>
    <div x-show="shopOpen" x-collapse data-shop-sub class="ml-6 border-l border-app pl-3" style="display:none">
        <a href="{{ route('shop.index') }}" class="nav-item !py-2">{{ __('All Categories') }}</a>
        @foreach ($sbShopCats as $c)
            <a href="{{ route('shop.category', $c->slug) }}" class="nav-item !py-2">{{ __($c->name) }}</a>
        @endforeach
    </div>
</div>

<p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Account') }}</p>
<x-nav-link :href="route('beneficiaries.index')" img="Crypto-Wallet--Streamline-Ultimate.png" :active="request()->routeIs('beneficiaries.*')">{{ __('China wallets') }}</x-nav-link>
<x-nav-link :href="route('verification.index')" img="Gateway-Security--Streamline-Ultimate.png" :active="request()->routeIs('verification.*')">{{ __('Verification') }}</x-nav-link>
<x-nav-link :href="route('disputes.index')" img="Headphones-Customer-Support-Human-1--Streamline-Ultimate.png" :active="request()->routeIs('disputes.*')">{{ __('Support') }}</x-nav-link>

<p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Explore') }}</p>
<x-nav-link :href="route('marketplace.index')" img="Shipment-Package--Streamline-Ultimate.png" :active="request()->routeIs('marketplace.*')">{{ __('Shipping agents') }}</x-nav-link>
<x-nav-link :href="route('learning.index')" img="Online-Learning-School-1--Streamline-Ultimate.png" :active="request()->routeIs('learning.*')">{{ __('Learning center') }}</x-nav-link>
