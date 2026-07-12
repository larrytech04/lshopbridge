<div x-data="appDock()" class="pointer-events-none fixed inset-x-0 bottom-0 z-40 flex justify-center pb-[max(1rem,env(safe-area-inset-bottom))] md:hidden">
    {{-- Slide-up menu sheet --}}
    <div x-show="menu" x-cloak @click="menu=false" class="pointer-events-auto fixed inset-0 bg-black/40" x-transition.opacity></div>
    <div x-show="menu" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-6"
         class="glass-strong pointer-events-auto fixed inset-x-3 bottom-24 z-50 mx-auto max-w-md rounded-3xl p-5 shadow-2xl" style="display:none">
        <div class="mx-auto mb-4 h-1.5 w-10 rounded-full surface-2"></div>
        @php $links = [
            ['dashboard','home','Dashboard', auth()->check(), 'House-Chimney-1--Streamline-Ultimate.png', '#7C5CFC'],
            ['deposit.index','deposit','Deposit', auth()->check(), 'Business-Piggy-Bank-Broken--Streamline-Ultimate.png', '#10B981'],
            ['funding.create','fund','Fund Alipay', auth()->check(), 'Money-Bag-Euro--Streamline-Ultimate.png', '#F97316'],
            ['shop.index','bag','Shop', true, 'Shop-Assistant--Streamline-Ultimate.png', '#EC4899'],
            ['shop.orders.index','receipt','My orders', auth()->check(), 'Shop-Arrow--Streamline-Ultimate.png', '#14B8A6'],
            ['disputes.index','info','Support', auth()->check(), 'Contact-Us-Customer-Support-Chat--Streamline-Ultimate.png', '#D946EF'],
            ['guides.index','book','Academy', true, 'Online-Learning-Student-4--Streamline-Ultimate.png', '#F59E0B'],
            ['agents.index','truck','Agents', true, 'Shipment-Lift--Streamline-Ultimate.png', '#0EA5E9'],
            ['profile.edit','cog','Settings', auth()->check(), null, '#64748B'],
        ]; @endphp
        <div class="grid grid-cols-3 gap-2.5">
            @foreach ($links as [$route, $icon, $label, $show, $img, $tint])
                @if ($show)
                    <a href="{{ route($route) }}" class="group flex flex-col items-center gap-2 rounded-2xl p-3 text-center text-xs font-medium text-body transition hover:-translate-y-0.5">
                        <span class="grid h-12 w-12 place-items-center rounded-full text-white shadow-sm transition group-hover:shadow-lg" style="background: {{ $tint }}">
                            @if ($img)<x-img-icon :name="$img" class="h-5 w-5" />@else<x-icon :name="$icon" class="h-5 w-5" />@endif
                        </span>
                        {{ __($label) }}
                    </a>
                @endif
            @endforeach
        </div>
        <div class="mt-4 flex items-center justify-between border-t border-app pt-4">
            <x-theme-toggle variant="full" />
            @auth
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost text-xs"><x-icon name="logout" class="h-4 w-4" /> {{ __('Log out') }}</button></form>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary text-xs">{{ __('Log in') }}</a>
            @endauth
        </div>
    </div>

    {{-- Dock --}}
    @php
        $home = auth()->check() ? route('dashboard') : route('home');
        $wallet = auth()->check() ? route('wallet.index') : route('login');
        $history = auth()->check() ? route('transactions.index') : route('login');
        $activeDock = request()->routeIs('transactions.*') ? 1
            : (request()->routeIs('shop.*', 'cart.*') ? 2
            : (request()->routeIs('wallet.*') ? 3 : 0));
    @endphp
    <nav class="app-dock pointer-events-auto relative mx-3 flex w-[calc(100%-1.5rem)] max-w-xl items-end justify-between gap-1 rounded-[1.6rem] px-3 py-1.5"
         data-active="{{ $activeDock }}">
        <span data-dock-indicator class="dock-indicator"></span>
        <a data-dock-slot href="{{ $home }}" class="dock-item {{ request()->routeIs('dashboard','home') ? 'dock-item-active' : '' }}"><x-img-icon name="House-Chimney-1--Streamline-Ultimate.png" class="h-5 w-5" /> {{ __('Home') }}</a>
        <a data-dock-slot href="{{ $history }}" class="dock-item {{ request()->routeIs('transactions.*') ? 'dock-item-active' : '' }}"><x-img-icon name="Receipt-Slip-1--Streamline-Ultimate.png" class="h-5 w-5" /> {{ __('History') }}</a>
        <a data-dock-slot href="{{ route('shop.index') }}" class="relative z-10 flex flex-1 flex-col items-center gap-0.5">
            <span class="dock-fab"><x-img-icon name="Shop-Sign-Bag--Streamline-Ultimate.png" class="h-6 w-6" /></span>
            <span class="text-xs font-medium {{ request()->routeIs('shop.*','cart.*') ? 'text-brand-400' : 'text-muted' }}">{{ __('Shop') }}</span>
        </a>
        <a data-dock-slot href="{{ $wallet }}" class="dock-item {{ request()->routeIs('wallet.*') ? 'dock-item-active' : '' }}"><x-img-icon name="Money-Wallet-1--Streamline-Ultimate.png" class="h-5 w-5" /> {{ __('Wallet') }}</a>
        <button data-dock-slot @click="menu=!menu" class="dock-item" :class="menu ? 'dock-item-active' : ''"><x-img-icon name="menu.png" class="h-5 w-5" /> {{ __('Menu') }}</button>
    </nav>
</div>
