@extends('layouts.app')
@section('page-title', 'Dashboard')

@php
    $points = (int) $user->points;
    $tier = $points >= 1000 ? 'Gold' : ($points >= 250 ? 'Silver' : 'Bronze');
    $tierColor = $tier === 'Gold' ? 'bg-amber-500' : ($tier === 'Silver' ? 'bg-slate-400' : 'bg-amber-700');
    $nextTier = $points >= 1000 ? 1000 : ($points >= 250 ? 1000 : 250);
    // Vivid solid circles with white icons (like the reference app grid). 4th item = circle colour.
    $quick = [
        ['Deposit', 'Saving-Safe-Open--Streamline-Ultimate.png', route('deposit.index'), '#10B981'],
        ['Fund Alipay', 'Trading-Buy--Streamline-Ultimate.png', route('funding.create'), '#F97316'],
        ['Gift Cards', 'Gift-Rectangle-With-Bow--Streamline-Ultimate.png', route('shop.category', 'gift-cards'), '#EC4899'],
        ['eSIMs', 'Sim-Card-2--Streamline-Ultimate.png', route('shop.category', 'esims'), '#0EA5E9'],
        ['Digital Apps', 'Vpn-Shield--Streamline-Ultimate.png', route('shop.category', 'gc-digital-apps'), '#7C5CFC'],
        ['Games', 'Vr-360-Remote-Controller--Streamline-Ultimate.png', route('shop.category', 'gc-games'), '#D946EF'],
        ['More', 'plus', route('shop.index'), '#3B82F6'],
    ];
    $maxBar = max(1, $txSeries->max(fn ($d) => max($d['credit'], $d['debit'])));
@endphp

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        {{-- Welcome --}}
        @php
            $meAvatar = $user->avatar_path
                ? Storage::url($user->avatar_path)
                : ($user->avatar_url ?: 'https://api.dicebear.com/9.x/avataaars/svg?seed='.urlencode($user->name));
        @endphp
        @php
            $greetName = __('Hi :name', ['name' => \Illuminate\Support\Str::before($user->name, ' ')]);
            // Rounds of two: funding → shopping an e-product (the product changes each round).
            $greetPhrases = [
                __('Funding China wallet today?'), __('Shopping gift card today?'),
                __('Funding China wallet today?'), __('Shopping eSIM today?'),
                __('Funding China wallet today?'), __('Shopping VPN today?'),
            ];
        @endphp
        <div class="relative z-10 -mb-3">
            <div class="min-w-0" x-data="typeGreet(@js($greetName), @js($greetPhrases))" x-init="start()">
                {{-- Profile pic — figure — "Hi Dev | ..." in one row --}}
                <div class="flex items-center gap-1">
                    <a href="{{ route('profile.edit') }}" class="shrink-0 sm:hidden">
                        <img src="{{ $meAvatar }}" alt="{{ $user->name }}" class="h-11 w-11 rounded-full object-cover" />
                    </a>
                    {{-- Figure cropped from user-board.png: width trimmed well before the board's
                         white edge (no shifted/negative-margin content) so nothing of it shows. --}}
                    <div class="h-28 w-[60px] shrink-0 overflow-hidden sm:h-32 sm:w-[68px]">
                        <img src="{{ asset('assets/'.rawurlencode('user board.png')) }}" alt=""
                             class="block h-28 w-auto max-w-none sm:h-32" />
                    </div>
                    <p class="flex min-w-0 flex-wrap items-center text-xl font-bold text-strong sm:text-2xl">
                        <span x-text="fixed" class="whitespace-nowrap">{{ $greetName }}</span>
                        <span x-show="sep" class="mx-2 font-light text-brand-500">|</span>
                        <span x-text="txt" class="whitespace-nowrap text-base sm:text-2xl">{{ $greetPhrases[0] }}</span>
                        <span class="tw-caret ml-0.5 h-5 w-0.5 shrink-0 rounded bg-brand-500"></span>
                    </p>
                </div>
                <p class="-mt-0.5 ml-12 text-sm text-muted sm:ml-0">{{ __("Here's what's happening with your account today.") }}</p>
            </div>

            {{-- Small glassy badges, below the greeting --}}
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                <a href="{{ route('verification.index') }}" class="glass inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold text-muted transition hover:text-strong"><x-img-icon name="Recruiting-Employee-Target-Validated-Check-2--Streamline-Ultimate.png" class="h-3 w-3" /> {{ __($level->name ?? 'Registered') }} · L{{ $user->kyc_level }}</a>
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="glass inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold text-muted transition hover:text-strong"><x-icon name="globe" class="h-3 w-3" /> {{ __('Visit website') }}</a>
            </div>
        </div>

        @if (! $user->isPhoneVerified())
            <div class="card-solid flex flex-wrap items-center gap-4 rounded-2xl border border-app border-l-4 border-l-amber-400/60 p-4 shadow-sm">
                <x-icon name="shield" class="h-6 w-6 text-amber-400" />
                <p class="flex-1 text-sm text-body">{{ __('Verify your phone to unlock funding and higher limits.') }}</p>
                <a href="{{ route('verification.index') }}" class="btn btn-primary">{{ __('Verify now') }}</a>
            </div>
        @endif

        {{-- Wallet hero + recent orders --}}
        <div class="grid gap-5 sm:grid-cols-2">
            @php
                // Swipeable balance cards: the visitor's country-driven display currency
                // first, then a curated set. Each currency gets its own solid card colour.
                $allCur = config('platform.currencies');
                $balCodes = collect([display_currency()['code'], 'XAF', 'NGN', 'GHS', 'KES', 'USD', 'EUR', 'CNY'])
                    ->unique()->filter(fn ($c) => isset($allCur[$c]))->values();
                $curColors = [
                    'XAF' => '#840a20', 'NGN' => '#047857', 'GHS' => '#b45309', 'KES' => '#0f766e',
                    'USD' => '#334155', 'EUR' => '#6d28d9', 'CNY' => '#c2410c',
                ];
            @endphp
            {{-- min-w-0 is load-bearing: without it the 7-card scroller's intrinsic width blows out the grid on phones --}}
            <div class="relative min-w-0"
                 x-data="{
                    hideBal: localStorage.getItem('pb-hide-bal') === '1',
                    cur: 0, drag: false, sx: 0, sl: 0,
                    snap() { const el = $refs.balRow; el.scrollTo({ left: Math.round(el.scrollLeft / el.clientWidth) * el.clientWidth, behavior: 'smooth' }) },
                 }">
                {{-- Swipe left/right between currency cards --}}
                <div x-ref="balRow" @scroll.debounce.60ms="cur = Math.round($refs.balRow.scrollLeft / $refs.balRow.clientWidth)"
                     @pointerdown="if ($event.pointerType === 'mouse') { drag = true; sx = $event.clientX; sl = $refs.balRow.scrollLeft }"
                     @pointermove="if (drag) { $refs.balRow.scrollLeft = sl - ($event.clientX - sx) }"
                     @pointerup="if (drag) { drag = false; snap() }"
                     @pointerleave="if (drag) { drag = false; snap() }"
                     class="no-scrollbar flex cursor-grab select-none snap-x snap-mandatory overflow-x-auto rounded-3xl active:cursor-grabbing"
                     :class="drag ? 'snap-none' : ''">
                    @foreach ($balCodes as $code)
                        @php $cfg = $allCur[$code]; $val = (float) $wallet->balance * ($cfg['rate'] ?? 1); @endphp
                        @php $tint = $curColors[$code] ?? '#3f3f46'; @endphp
                        <div class="relative w-full shrink-0 snap-center overflow-hidden rounded-3xl p-4 sm:p-5"
                             style="background: color-mix(in srgb, {{ $tint }} 14%, #ffffff); color: {{ $tint }}">
                            <p class="text-[11px] font-bold uppercase tracking-wider">{{ __('Total wallet balance') }}</p>
                            <div class="mt-1 flex items-center gap-2">
                                <p class="min-w-0 truncate text-2xl font-extrabold tracking-tight sm:text-3xl">
                                    <span x-show="!hideBal">{{ $cfg['symbol'] }} {{ number_format($val, $cfg['decimals'] ?? 0) }}</span>
                                    <span x-show="hideBal" x-cloak>••••••</span>
                                </p>
                                <button type="button" @click="hideBal = !hideBal; localStorage.setItem('pb-hide-bal', hideBal ? '1' : '0')"
                                        class="grid h-6 w-6 shrink-0 place-items-center rounded-full transition hover:bg-black/5"
                                        :aria-label="hideBal ? '{{ __('Show balance') }}' : '{{ __('Hide balance') }}'">
                                    <svg x-show="!hideBal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.6"/></svg>
                                    <svg x-show="hideBal" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M3 3l18 18M10.7 5.1A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4M6.6 6.6C3.8 8.5 2 12 2 12s3.5 7 10 7c1.8 0 3.4-.5 4.8-1.3M9.9 9.9a3 3 0 1 0 4.2 4.2"/></svg>
                                </button>
                            </div>
                            <p class="mt-0.5 text-xs opacity-80">{{ $code }} · {{ __('Available') }}</p>
                            <div class="mt-4 flex justify-end">
                                <a href="{{ route('deposit.index') }}" class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-bold shadow-sm transition hover:shadow-md" style="color: {{ $tint }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="h-4 w-4"><path d="M12 5v14M5 12h14"/></svg> {{ __('Top up') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Dots with back/forward arrows at each end --}}
                <div class="mt-2.5 flex items-center justify-center gap-1.5">
                    <button type="button" aria-label="{{ __('Previous') }}"
                            @click="$refs.balRow.scrollTo({ left: Math.max(0, cur - 1) * $refs.balRow.clientWidth, behavior: 'smooth' })"
                            class="mr-1 grid h-5 w-5 place-items-center rounded-full border border-app surface text-muted transition hover:text-strong"
                            :class="cur === 0 ? 'pointer-events-none opacity-40' : ''">
                        <x-icon name="chevron-right" class="h-3 w-3 rotate-180" />
                    </button>
                    @foreach ($balCodes as $i => $code)
                        <button type="button" aria-label="{{ $code }}"
                                @click="$refs.balRow.scrollTo({ left: {{ $i }} * $refs.balRow.clientWidth, behavior: 'smooth' })"
                                class="h-1.5 rounded-full transition-all duration-300 surface-2"
                                :class="cur === {{ $i }} ? 'w-5 !bg-brand-600' : 'w-1.5'"></button>
                    @endforeach
                    <button type="button" aria-label="{{ __('Next') }}"
                            @click="$refs.balRow.scrollTo({ left: Math.min({{ $balCodes->count() - 1 }}, cur + 1) * $refs.balRow.clientWidth, behavior: 'smooth' })"
                            class="ml-1 grid h-5 w-5 place-items-center rounded-full border border-app surface text-muted transition hover:text-strong"
                            :class="cur === {{ $balCodes->count() - 1 }} ? 'pointer-events-none opacity-40' : ''">
                        <x-icon name="chevron-right" class="h-3 w-3" />
                    </button>
                </div>
            </div>

            <div class="card-solid rounded-3xl border border-app p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-strong">{{ __('Recent transactions') }}</h3>
                    <a href="{{ route('transactions.index') }}" class="rounded-full border border-app px-2.5 py-0.5 text-[11px] font-semibold text-body transition hover:text-strong">{{ __('View all') }}</a>
                </div>
                <div class="mt-3 space-y-2.5">
                    @forelse ($transactions->take(3) as $t)
                        <div class="flex items-center justify-between gap-2.5">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg {{ $t->type === 'credit' ? 'bg-emerald-500/12 text-emerald-500' : 'bg-rose-500/12 text-rose-500' }}"><x-icon :name="$t->type === 'credit' ? 'arrow-up' : 'arrow-right'" class="h-4 w-4 {{ $t->type === 'credit' ? '' : 'rotate-45' }}" /></span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-strong">{{ \Illuminate\Support\Str::limit($t->description ?: ucfirst($t->category), 18) }}</p>
                                    <p class="text-xs text-muted">{{ disp($t->amount) }}</p>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="inline-block rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 {{ $t->type === 'credit' ? 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/30' : 'bg-rose-500/10 text-rose-600 ring-rose-500/30' }}">{{ $t->type === 'credit' ? __('In') : __('Out') }}</span>
                                <p class="mt-0.5 text-[11px] text-faint">{{ $t->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="py-5 text-center text-sm text-muted">{{ __('No transactions yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Transactions graph --}}
        <div class="card-solid rounded-3xl border border-app p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-strong">{{ __('Transactions') }}</h3>
                    <p class="text-sm text-muted">{{ __('Inflow vs outflow · last 7 days') }}</p>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span><span class="text-muted">{{ __('In') }}</span> <span class="font-semibold text-strong">{{ disp($txInflow) }}</span></span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span><span class="text-muted">{{ __('Out') }}</span> <span class="font-semibold text-strong">{{ disp($txOutflow) }}</span></span>
                </div>
            </div>
            <div class="mt-6 flex h-48 items-end gap-2 sm:gap-4">
                @foreach ($txSeries as $d)
                    <div class="group flex flex-1 flex-col items-center gap-2">
                        <div class="flex h-full w-full items-end justify-center gap-1">
                            <div class="w-1/2 max-w-3 rounded-t-md bg-emerald-500 transition-all duration-500" style="height: {{ max(2, ($d['credit'] / $maxBar) * 100) }}%" title="{{ __('In') }} {{ disp($d['credit']) }} · {{ $d['date'] }}"></div>
                            <div class="w-1/2 max-w-3 rounded-t-md bg-rose-500 transition-all duration-500" style="height: {{ max(2, ($d['debit'] / $maxBar) * 100) }}%" title="{{ __('Out') }} {{ disp($d['debit']) }} · {{ $d['date'] }}"></div>
                        </div>
                        <span class="text-xs text-faint">{{ $d['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="card-solid rounded-3xl border border-app p-6 shadow-sm">
            <div class="flex items-center justify-between"><h3 class="font-semibold text-strong">{{ __('Quick actions') }}</h3><a href="{{ route('shop.index') }}" class="text-sm text-brand-400 hover:text-brand-300">{{ __('All') }}</a></div>
            <div class="mt-5 grid grid-cols-4 gap-4 sm:grid-cols-8">
                @foreach ($quick as [$label, $icon, $url, $color])
                    <a href="{{ $url }}" class="group flex flex-col items-center gap-2 text-center">
                        <span class="grid h-14 w-14 place-items-center rounded-full text-white shadow-sm transition group-hover:-translate-y-1" style="background: {{ $color }}">
                            @if (str_ends_with($icon, '.png'))<x-img-icon :name="$icon" class="h-6 w-6" />@else<x-icon :name="$icon" class="h-6 w-6" />@endif
                        </span>
                        <span class="text-xs font-medium text-body">{{ __($label) }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Shop by category --}}
        @if ($shopCategories->isNotEmpty())
            <div class="card-solid rounded-3xl border border-app p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-strong">{{ __('Shop by category') }}</h3>
                        <p class="text-sm text-muted">{{ __('Buy gift cards, eSIMs, top-ups & more — right here.') }}</p>
                    </div>
                    <a href="{{ route('shop.index') }}" class="text-sm text-brand-400 hover:text-brand-300">{{ __('All') }}</a>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($shopCategories as $cat)
                        <a href="{{ route('shop.category', $cat) }}" class="group flex items-center gap-3 rounded-2xl border border-app surface p-3 hover:surface-2">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-600"><x-icon :name="$cat->icon ?? 'bag'" class="h-5 w-5" /></span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-strong group-hover:text-brand-500">{{ __($cat->name) }}</p>
                                @if ($cat->tagline)<p class="truncate text-xs text-muted">{{ __($cat->tagline) }}</p>@endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Popular products --}}
        @if ($popular->isNotEmpty())
            <div>
                <div class="mb-4 flex items-center justify-between"><h3 class="font-semibold text-strong">{{ __('Popular right now') }}</h3><a href="{{ route('shop.index') }}" class="text-sm text-brand-400 hover:text-brand-300">{{ __('See all') }}</a></div>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($popular as $p)
                        <a href="{{ route('shop.show', $p) }}" class="card-solid glass-hover rounded-2xl border border-app p-4 shadow-sm">
                            @if ($pImg = ($p->image_path ?? $p->logo_path))
                                <span class="grid h-14 w-full place-items-center overflow-hidden rounded-xl bg-white ring-1 ring-app"><img src="{{ Storage::url($pImg) }}" class="max-h-12 w-auto object-contain" alt="{{ $p->name }}" loading="lazy"></span>
                            @endif
                            <p class="{{ $pImg ? 'mt-3' : '' }} line-clamp-1 text-sm font-semibold text-strong">{{ $p->name }}</p>
                            <p class="text-xs text-muted">{{ __('From') }} {{ disp(optional($p->fromPrice())->price ?? 0) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Right rail --}}
    <div class="space-y-6">
        <div class="card-solid rounded-3xl border border-app p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-600 text-white"><x-img-icon name="Crypto-Currency-Litecoin--Streamline-Ultimate.png" class="h-6 w-6 coin-spin" /></span>
                    <div><p class="font-semibold text-strong">{{ config('platform.name') }} {{ __('Coins') }}</p><p class="text-xs text-faint">{{ __('Earn on every order') }}</p></div>
                </div>
                <span class="pill {{ $tierColor }} text-white">{{ $tier }}</span>
            </div>
            <p class="mt-4 text-3xl font-extrabold text-strong">{{ number_format($points) }}</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full surface-2"><div class="h-full rounded-full bg-brand-600" style="width: {{ min(100, ($points / $nextTier) * 100) }}%"></div></div>
            <p class="mt-2 text-xs text-muted">{{ __(':n pts to next tier', ['n' => max(0, $nextTier - $points)]) }}</p>
        </div>

        <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-brand-900 p-6 text-white">
            <div class="animate-pulse-glow absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-accent-500/30 blur-3xl"></div>
            <h3 class="relative text-lg font-bold">{{ __('Give the perfect gift 🎁') }}</h3>
            <p class="relative mt-1 text-sm text-white/70">{{ __('Gift cards for every occasion — Amazon, Apple, Steam & more.') }}</p>
            <a href="{{ route('shop.category', 'gift-cards') }}" class="relative mt-4 inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2 text-sm font-semibold backdrop-blur hover:bg-white/25">{{ __('Shop now') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>

        <div class="card-solid rounded-3xl border border-app p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-strong">{{ __('Recent orders') }}</h3>
                <a href="{{ route('shop.orders.index') }}" class="rounded-full border border-app px-2.5 py-0.5 text-[11px] font-semibold text-body transition hover:text-strong">{{ __('View all') }}</a>
            </div>
            <div class="mt-3 space-y-2.5">
                @forelse ($recentOrders as $o)
                    <a href="{{ route('shop.orders.show', $o) }}" class="flex items-center justify-between gap-2.5">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg surface-2 text-brand-500"><x-img-icon name="Shop-Arrow--Streamline-Ultimate.png" class="h-4.5 w-4.5" /></span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-strong">{{ $o->items->first()->name ?? __('Order') }}</p>
                                <p class="text-xs text-muted">{{ disp($o->total) }}</p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <x-status-badge :status="$o->status" />
                            <p class="mt-0.5 text-[11px] text-faint">{{ $o->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                @empty
                    <div class="py-6 text-center">
                        <p class="text-sm text-muted">{{ __('No orders yet') }}</p>
                        <a href="{{ route('shop.index') }}" class="mt-2 inline-block text-sm font-semibold text-brand-500">{{ __('Browse the shop') }} →</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
