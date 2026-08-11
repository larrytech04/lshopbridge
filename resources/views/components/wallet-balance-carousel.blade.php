@props(['wallet', 'wallets'])

{{-- Swipeable when the user holds more than one currency wallet; otherwise
     just the plain single card, unchanged. Shared by the dashboard home page
     and the wallet page so both behave identically. --}}
@if ($wallets->count() > 1)
    <div x-data="{ active: 0 }">
        <div class="no-scrollbar flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth pb-1"
             @scroll="active = Math.round($el.scrollLeft / $el.clientWidth)">
            @foreach ($wallets as $w)
                <div class="w-full shrink-0 snap-center">
                    <x-wallet-balance-card :wallet="$w" :native="! $loop->first" />
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex items-center justify-center gap-1.5">
            @foreach ($wallets as $i => $w)
                <span class="h-1.5 rounded-full transition-all" :class="active === {{ $i }} ? 'w-4 bg-brand-500' : 'w-1.5 bg-slate-300'"></span>
            @endforeach
        </div>
    </div>
@else
    <x-wallet-balance-card :wallet="$wallet" />
@endif
