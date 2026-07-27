<p class="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Agent') }}</p>
<x-nav-link :href="route('agent.dashboard')" icon="home" :active="request()->routeIs('agent.dashboard')">{{ __('Dashboard') }}</x-nav-link>
<x-nav-link :href="route('agent.profile')" icon="building" :active="request()->routeIs('agent.profile')">{{ __('Business profile') }}</x-nav-link>
<x-nav-link :href="route('agent.verification')" icon="shield" :active="request()->routeIs('agent.verification')">{{ __('Verification') }}</x-nav-link>
<x-nav-link :href="route('agent.rates.index')" icon="truck" :active="request()->routeIs('agent.rates.*')">{{ __('Shipping rates') }}</x-nav-link>
<x-nav-link :href="route('agent.leads.index')" icon="list" :active="request()->routeIs('agent.leads.*')">{{ __('Orders / leads') }}</x-nav-link>
<x-nav-link :href="route('agent.shipping-requests.index')" icon="truck" :active="request()->routeIs('agent.shipping-requests.*')">{{ __('Shipping requests') }}</x-nav-link>
<x-nav-link :href="route('agent.reviews.index')" icon="star" :active="request()->routeIs('agent.reviews.*')">{{ __('Reviews') }}</x-nav-link>

<p class="px-3 pb-2 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Wallet') }}</p>
<x-nav-link :href="route('wallet.index')" icon="wallet" :active="request()->routeIs('wallet.*')">{{ __('Wallet') }}</x-nav-link>
<x-nav-link :href="route('learning.index')" icon="book" :active="request()->routeIs('learning.*')">{{ __('Learning center') }}</x-nav-link>
