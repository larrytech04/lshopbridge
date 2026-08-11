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
        ['Fund China wallet', 'Trading-Buy--Streamline-Ultimate.png', route('funding.create'), '#F97316'],
        ['Gift Cards', 'Gift-Rectangle-With-Bow--Streamline-Ultimate.png', route('shop.category', 'gift-cards'), '#EC4899'],
        ['eSIMs', 'Sim-Card-2--Streamline-Ultimate.png', route('shop.category', 'esims'), '#0EA5E9'],
        ['Digital Apps', 'Vpn-Shield--Streamline-Ultimate.png', route('shop.category', 'gc-digital-apps'), '#7C5CFC'],
        ['Games', 'Vr-360-Remote-Controller--Streamline-Ultimate.png', route('shop.category', 'gc-games'), '#D946EF'],
        ['Mobile top up', 'Cashless-Payment-Cad-Top-Up-Wallet-Add--Streamline-Ultimate.png', route('shop.category', 'mobile-topup'), '#14B8A6'],
        ['More', 'plus', route('shop.index'), '#3B82F6'],
    ];
@endphp

@section('content')
<div class="grid min-w-0 gap-6 lg:grid-cols-3">
    <div class="min-w-0 space-y-6 lg:col-span-2">
        {{-- Welcome --}}
        @php
            $meAvatar = $user->avatar_path
                ? Storage::url($user->avatar_path)
                : ($user->avatar_url ?: local_avatar($user->name));
        @endphp
        @php
            $greetName = __('Hi :name', ['name' => \Illuminate\Support\Str::before($user->name, ' ')]);
            // Rounds of two: funding → shopping an e-product (the product changes each round).
            $greetPhrases = [
                __('Funding China wallet today?'), __('Shopping gift card today?'),
                __('Funding China wallet today?'), __('Shopping eSIM today?'),
                __('Funding China wallet today?'), __('Shopping VPN today?'),
            ];
            // Landing here right after clearing the idle-session re-auth check
            // (see ReauthController) — typed as the first line instead of a
            // separate flash banner, which read as redundant next to this.
            if (session('welcome_back')) {
                array_unshift($greetPhrases, __('Welcome back!'));
            }
        @endphp
        <div class="relative z-10 mb-2 sm:-mb-3">
            <div class="min-w-0" x-data="typeGreet(@js($greetName), @js($greetPhrases))" x-init="start()">
                {{-- Profile pic, figure, "Hi Dev | ..." in one row --}}
                <div class="flex items-center gap-1">
                    <a href="{{ route('profile.edit') }}" class="relative shrink-0 self-start sm:hidden">
                        <img src="{{ $meAvatar }}" alt="{{ $user->name }}" class="h-11 w-11 rounded-full object-cover ring-2 ring-app" />
                        @if ((int) $user->kyc_level >= 2)
                            <x-verified-tick class="absolute -bottom-1 -right-1 h-4 w-4" />
                        @endif
                    </a>
                    {{-- Full figure (clean image, nothing to crop around), desktop/tablet only.
                         Nudged down so the pointing hand lines up with the "Hi :name" text. --}}
                    <img src="{{ asset('assets/'.rawurlencode('user board.png')) }}" alt=""
                         class="hidden h-20 w-auto shrink-0 translate-y-3 sm:block sm:h-24 sm:translate-y-4" />
                    <div class="min-w-0">
                        <p class="flex min-w-0 flex-wrap items-center text-xl font-bold text-strong sm:text-2xl">
                            <span x-text="fixed" class="whitespace-nowrap">{{ $greetName }}</span>
                            <span x-show="sep" class="mx-2 font-light text-brand-500">|</span>
                            <span x-text="txt" class="whitespace-nowrap text-base sm:text-2xl">{{ $greetPhrases[0] }}</span>
                            <span class="tw-caret ml-0.5 h-5 w-0.5 shrink-0 rounded bg-brand-500"></span>
                        </p>
                        <p class="-mt-0.5 text-sm text-muted">{{ __("Here's what's happening with your account today.") }}</p>
                        {{-- Small glassy badges, directly under the description, left-aligned,
                             sticky just below the header while scrolling. --}}
                        <div class="sticky top-16 z-20 mt-1.5 flex flex-wrap items-center justify-start gap-1 py-1">
                            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="glass inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[9px] font-semibold text-muted transition hover:text-strong"><x-img-icon name="Ui-Webpage-Bullets--Streamline-Ultimate.png" class="h-2.5 w-2.5" /> {{ __('Visit website') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (! $user->isPhoneVerified())
            <div class="card-solid flex flex-wrap items-center gap-4 rounded-2xl border border-app border-l-4 border-l-amber-400/60 p-4 shadow-sm">
                <x-icon name="shield" class="h-6 w-6 text-amber-400" />
                <p class="flex-1 text-sm text-body">{{ __('Verify your phone to unlock funding and higher limits.') }}</p>
                <a href="{{ route('verification.index') }}" class="btn btn-primary">{{ __('Verify now') }}</a>
            </div>
        @endif

        @if (! $user->hasTransactionPin())
            <div class="card-solid flex flex-wrap items-center gap-4 rounded-2xl border border-app border-l-4 border-l-rose-400/60 p-4 shadow-sm">
                <x-icon name="lock" class="h-6 w-6 text-rose-400" />
                <p class="flex-1 text-sm text-body">{{ __('Set up a transaction PIN to fund or withdraw, and to keep your account locked to you alone. Never share it with anyone, not even our own support staff.') }}</p>
                <a href="{{ route('security.index') }}" class="btn btn-primary">{{ __('Set up PIN') }}</a>
            </div>
        @endif

        {{-- Attention cards: every card here is driven by a real signal from NavigationBadgeService
             (no permanent/decorative cards) — the section itself disappears when nothing needs attention. --}}
        @php
            // Literal Tailwind classes only — $card['color'] is one of a small
            // fixed set chosen below, never interpolated directly into a class.
            $attentionColor = fn (string $c) => match ($c) {
                'rose' => ['border' => 'border-l-rose-400/60', 'icon' => 'text-rose-500'],
                'amber' => ['border' => 'border-l-amber-400/60', 'icon' => 'text-amber-500'],
                'sky' => ['border' => 'border-l-sky-400/60', 'icon' => 'text-sky-500'],
                default => ['border' => 'border-l-emerald-400/60', 'icon' => 'text-emerald-500'],
            };
            $attentionCards = collect([
                $navBadges['security_alert'] ?? false ? ['icon' => 'shield', 'color' => 'rose', 'text' => __('A security alert needs your attention.'), 'action' => __('Review'), 'url' => route('security.index')] : null,
                ($navBadges['support_awaiting_you'] ?? 0) > 0 ? ['icon' => 'mail', 'color' => 'amber', 'text' => __(':count support ticket(s) awaiting your reply.', ['count' => $navBadges['support_awaiting_you']]), 'action' => __('View'), 'url' => route('disputes.index')] : null,
                ($navBadges['shipping_requests_new_update'] ?? 0) > 0 && \Illuminate\Support\Facades\Route::has('shipping-requests.index') ? ['icon' => 'truck', 'color' => 'sky', 'text' => __(':count shipping request(s) have new updates.', ['count' => $navBadges['shipping_requests_new_update']]), 'action' => __('View'), 'url' => route('shipping-requests.index')] : null,
                $navBadges['referral_reward_available'] ?? false ? ['icon' => 'users', 'color' => 'emerald', 'text' => __('You have referral rewards available.'), 'action' => __('Claim'), 'url' => route('referrals.index')] : null,
            ])->filter()->values();
        @endphp
        @if ($attentionCards->isNotEmpty())
            <div class="grid gap-3 sm:mt-6 sm:grid-cols-2">
                @foreach ($attentionCards as $card)
                    @php $colorClasses = $attentionColor($card['color']); @endphp
                    <div class="card-solid flex items-center gap-2 rounded-xl border border-app border-l-4 px-3 py-1.5 shadow-sm {{ $colorClasses['border'] }}">
                        <x-icon :name="$card['icon']" class="h-3.5 w-3.5 shrink-0 {{ $colorClasses['icon'] }}" />
                        <p class="min-w-0 flex-1 truncate text-xs text-body">{{ $card['text'] }}</p>
                        <a href="{{ $card['url'] }}" class="shrink-0 text-xs font-semibold text-brand-500 hover:text-brand-400">{{ $card['action'] }}</a>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Wallet hero + recent transactions sit side by side on desktop; on mobile every
             section here stacks in a different order so "Recent transactions" lands
             directly above the graph instead of right under the balance card. --}}
        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
            <div class="order-1 min-w-0 lg:order-1">
                <x-wallet-balance-carousel :wallet="$wallet" :wallets="$wallets" />
            </div>

            <div class="order-2 min-w-0 lg:order-3 lg:col-span-2">
                {{-- Quick actions --}}
                <div class="card-solid rounded-3xl border border-app p-4 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between"><h3 class="font-semibold text-strong">{{ __('Quick actions') }}</h3><a href="{{ route('shop.index') }}" class="text-sm text-brand-400 hover:text-brand-300">{{ __('All') }}</a></div>
                    <div class="mt-5 grid grid-cols-4 gap-x-2 gap-y-4 sm:grid-cols-8 sm:gap-4">
                        @foreach ($quick as [$label, $icon, $url, $color])
                            @if ($label === 'More')
                                <button type="button" @click="window.dispatchEvent(new CustomEvent('open-mobile-menu'))" class="group flex flex-col items-center gap-2 text-center">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full text-white shadow-sm transition group-hover:-translate-y-1 sm:h-14 sm:w-14" style="background: {{ $color }}">
                                        @if (str_ends_with($icon, '.png'))<x-img-icon :name="$icon" class="h-5 w-5 sm:h-6 sm:w-6" />@else<x-icon :name="$icon" class="h-5 w-5 sm:h-6 sm:w-6" />@endif
                                    </span>
                                    <span class="line-clamp-2 text-[11px] font-medium leading-tight text-body sm:text-xs">{{ __($label) }}</span>
                                </button>
                            @else
                                <a href="{{ $url }}" class="group flex flex-col items-center gap-2 text-center">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full text-white shadow-sm transition group-hover:-translate-y-1 sm:h-14 sm:w-14" style="background: {{ $color }}">
                                        @if (str_ends_with($icon, '.png'))<x-img-icon :name="$icon" class="h-5 w-5 sm:h-6 sm:w-6" />@else<x-icon :name="$icon" class="h-5 w-5 sm:h-6 sm:w-6" />@endif
                                    </span>
                                    <span class="line-clamp-2 text-[11px] font-medium leading-tight text-body sm:text-xs">{{ __($label) }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="order-3 min-w-0 lg:order-4 lg:col-span-2">
                {{-- Accepted payment methods (same marquee as the homepage), open, no card box --}}
                <div>
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <h3 class="font-semibold text-strong">{{ __('Accepted payment methods') }}</h3>
                        <a href="{{ route('public.payment-methods') }}" class="text-sm font-semibold text-brand-400 hover:text-brand-300">{{ __('All methods') }} →</a>
                    </div>
                    @php $allPay = collect(config('payments.accepted'))->collapse(); @endphp
                    <div class="pay-marquee mt-4">
                        <div class="pay-marquee__track">
                            @for ($d = 0; $d < 2; $d++)
                                @foreach ($allPay as [$key, $name])
                                    <div class="flex w-16 shrink-0 flex-col items-center gap-2" @if($d) aria-hidden="true" @endif>
                                        <x-pay-icon :name="$key" class="h-12 w-12 shadow-sm" />
                                        <span class="whitespace-nowrap text-center text-[10px] font-semibold text-muted">{{ __($name) }}</span>
                                    </div>
                                @endforeach
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <div class="order-4 min-w-0 lg:order-2">
                <div class="card-solid rounded-3xl border border-app p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-strong">{{ __('Recent transactions') }}</h3>
                        <a href="{{ route('transactions.index') }}" class="rounded-full border border-app px-2.5 py-0.5 text-[11px] font-semibold text-body transition hover:text-strong">{{ __('View all') }}</a>
                    </div>
                    <div class="mt-3 space-y-2.5">
                        @forelse ($transactions->take(2) as $t)
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

            <div class="order-5 min-w-0 lg:order-5 lg:col-span-2">
        {{-- Transactions graph --}}
        <div class="rounded-3xl border border-app p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                <div>
                    <h3 class="font-semibold text-strong">{{ __('Transactions') }}</h3>
                    <p class="text-sm text-muted">{{ __('In vs out vs orders · last 7 days') }}</p>
                </div>
                <div class="flex items-center gap-2.5 text-xs sm:gap-4 sm:text-sm">
                    <span class="flex items-center gap-1 sm:gap-1.5"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500 sm:h-2.5 sm:w-2.5"></span><span class="text-muted">{{ __('In') }}</span> <span class="font-semibold text-strong">{{ disp($txInflow) }}</span></span>
                    <span class="flex items-center gap-1 sm:gap-1.5"><span class="h-2 w-2 shrink-0 rounded-full bg-red-500 sm:h-2.5 sm:w-2.5"></span><span class="text-muted">{{ __('Out') }}</span> <span class="font-semibold text-strong">{{ disp($txOutflow) }}</span></span>
                    <span class="flex items-center gap-1 sm:gap-1.5"><span class="h-2 w-2 shrink-0 rounded-full bg-blue-500 sm:h-2.5 sm:w-2.5"></span><span class="text-muted">{{ __('Orders') }}</span> <span class="font-semibold text-strong">{{ disp($txOrders) }}</span></span>
                </div>
            </div>

            @php
                $twN = count($txSeries);
                $twPointWidth = 56;
                $twChartWidth = max($twN * $twPointWidth, 200);
                $twChartHeight = 176;
                $twPadBottom = 6;
                $twPlotHeight = $twChartHeight - $twPadBottom;
                $twMax = max(1, $txSeries->flatMap(fn ($d) => [$d['credit'], $d['debit'], $d['orders']])->max());

                $twXFor = fn (int $i) => $twN <= 1 ? $twChartWidth / 2 : $i * $twPointWidth + $twPointWidth / 2;
                $twYFor = fn (float $v) => $twChartHeight - $twPadBottom - ($v / $twMax) * $twPlotHeight;

                // Paint order = legend/z-order (later wins where lines cross): credit, debit, orders.
                $twColors = ['credit' => '#10b981', 'debit' => '#ef4444', 'orders' => '#3b82f6'];

                $twPaths = [];
                foreach ($twColors as $key => $color) {
                    $pts = [];
                    foreach ($txSeries as $i => $d) {
                        $pts[] = [$twXFor($i), $twYFor((float) $d[$key])];
                    }
                    $twPaths[$key] = \App\Support\SmoothWavePath::build($pts);
                }

                $twChartPoints = $txSeries->map(fn ($d, $i) => [
                    'label' => $d['date'], 'x' => $twXFor($i),
                    'yCredit' => $twYFor((float) $d['credit']), 'creditDisp' => disp($d['credit']),
                    'yDebit' => $twYFor((float) $d['debit']), 'debitDisp' => disp($d['debit']),
                    'yOrders' => $twYFor((float) $d['orders']), 'ordersDisp' => disp($d['orders']),
                ])->values();

                $twBaselineY = $twChartHeight - $twPadBottom;
            @endphp

            <div class="relative mt-5" x-data="financialWaveChart(@js($twChartPoints), '')">
                <svg viewBox="0 0 {{ $twChartWidth }} {{ $twChartHeight }}" preserveAspectRatio="none" class="h-44 w-full touch-none"
                     @mousemove="handleMove($event)" @mouseleave="clear()" @touchstart.passive="handleTouch($event)" @touchmove.passive="handleTouch($event)">
                    <defs>
                        <filter id="tx-wave-glow" x="-20%" y="-75%" width="140%" height="250%">
                            <feGaussianBlur stdDeviation="4" />
                        </filter>
                        <clipPath id="tx-wave-plot">
                            <rect x="0" y="0" width="{{ $twChartWidth }}" height="{{ $twBaselineY }}" />
                        </clipPath>
                    </defs>

                    <line x1="0" y1="{{ $twBaselineY }}" x2="{{ $twChartWidth }}" y2="{{ $twBaselineY }}" stroke="var(--border)" stroke-width="1" />

                    <line x-show="hover !== null" x-cloak :x1="activeX" :x2="activeX" y1="0" y2="{{ $twBaselineY }}"
                          stroke="var(--border)" stroke-width="1.5" stroke-dasharray="3,3" />

                    <g clip-path="url(#tx-wave-plot)" filter="url(#tx-wave-glow)" opacity="0.55">
                        @foreach ($twPaths as $key => $d)
                            <path d="{{ $d }}" fill="none" stroke="{{ $twColors[$key] }}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                        @endforeach
                    </g>

                    @foreach ($twPaths as $key => $d)
                        <path d="{{ $d }}" fill="none" stroke="{{ $twColors[$key] }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    @endforeach

                    <g x-show="active" x-cloak>
                        <circle :cx="activeX" :cy="active ? active.yCredit : 0" r="3.5" fill="{{ $twColors['credit'] }}" stroke="var(--glass-strong-bg)" stroke-width="1.5" />
                        <circle :cx="activeX" :cy="active ? active.yDebit : 0" r="3.5" fill="{{ $twColors['debit'] }}" stroke="var(--glass-strong-bg)" stroke-width="1.5" />
                        <circle :cx="activeX" :cy="active ? active.yOrders : 0" r="3.5" fill="{{ $twColors['orders'] }}" stroke="var(--glass-strong-bg)" stroke-width="1.5" />
                    </g>
                </svg>

                <div class="glass-strong pointer-events-none absolute top-0 z-10 -translate-x-1/2 whitespace-nowrap rounded-xl px-3 py-2 text-[11px] shadow-lg"
                     style="min-width: 8rem;" x-show="active" x-cloak :style="{ left: activeX + 'px' }">
                    <p class="mb-1 font-semibold text-strong" x-text="active ? active.label : ''"></p>
                    <div class="flex items-center justify-between gap-3"><span class="flex items-center gap-1.5 text-muted"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>{{ __('In') }}</span><span class="font-semibold text-strong" x-text="active ? active.creditDisp : ''"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="flex items-center gap-1.5 text-muted"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-red-500"></span>{{ __('Out') }}</span><span class="font-semibold text-strong" x-text="active ? active.debitDisp : ''"></span></div>
                    <div class="flex items-center justify-between gap-3"><span class="flex items-center gap-1.5 text-muted"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-blue-500"></span>{{ __('Orders') }}</span><span class="font-semibold text-strong" x-text="active ? active.ordersDisp : ''"></span></div>
                </div>

                <div class="mt-1 flex">
                    @foreach ($txSeries as $d)
                        <span class="flex-1 text-center text-[11px] text-faint">{{ $d['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
            </div>
        </div>

    </div>

    {{-- Right rail --}}
    <div class="flex min-w-0 flex-col gap-6">
        {{-- Fixed slate-200 like the gift-card box/button below, deliberately not theme
             tokens, so it keeps the same ash-grey tone in every theme. --}}
        <div class="relative overflow-hidden rounded-3xl border border-slate-300 bg-slate-200 p-4 shadow-sm">
            <div class="pointer-events-none absolute -right-6 -top-8 h-28 w-28 rounded-full bg-white/40 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-10 -left-8 h-24 w-24 rounded-full bg-white/30 blur-2xl"></div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-white"><x-img-icon name="Crypto-Currency-Litecoin--Streamline-Ultimate.png" class="h-5 w-5 coin-spin" /></span>
                    <div><p class="font-semibold text-slate-900">{{ config('platform.name') }} {{ __('Coins') }}</p><p class="text-xs text-slate-600">{{ __('Earn on every order') }}</p></div>
                </div>
                <span class="pill {{ $tierColor }} text-white">{{ $tier }}</span>
            </div>
            <div class="relative mt-3 flex items-end justify-between gap-3">
                <p class="text-2xl font-extrabold text-slate-900">{{ number_format($points) }}</p>
                <p class="mb-0.5 text-xs text-slate-600">{{ __(':n pts to next tier', ['n' => max(0, $nextTier - $points)]) }}</p>
            </div>
            <div class="relative mt-2 h-1.5 overflow-hidden rounded-full bg-slate-400/30"><div class="h-full rounded-full bg-brand-600" style="width: {{ min(100, ($points / $nextTier) * 100) }}%"></div></div>
        </div>

        <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-brand-900 p-6 text-white">
            {{-- Decorative accents: two soft glows + a faint dotted texture --}}
            <div class="animate-pulse-glow absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-accent-500/30 blur-3xl"></div>
            <div class="absolute -left-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(currentColor 1px, transparent 1px); background-size: 14px 14px;"></div>

            <div class="relative flex items-center gap-4">
                <div class="min-w-0 flex-1">
                    {{-- Heading + description: solid opaque white, always-dark text.
                         Deliberately NOT the theme's `surface`/`text-strong` tokens, those
                         are near-transparent tints meant for the page's own background, and
                         this box sits on a fixed dark-maroon card regardless of site theme,
                         so it needs guaranteed light-bg/dark-text contrast in every theme. --}}
                    <div class="rounded-2xl bg-slate-200 p-3">
                        <h3 class="text-lg font-bold text-slate-900">{{ __('Give the perfect gift') }}</h3>
                        <p class="mt-1 text-sm text-slate-700">{{ __('Gift cards for every occasion, Amazon, Apple, Steam & more.') }}</p>
                    </div>
                    <a href="{{ route('shop.category', 'gift-cards') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-300">{{ __('Shop now') }} <x-img-icon name="Shop-Sign-Bag--Streamline-Ultimate.png" class="h-4 w-4" /></a>
                </div>
                {{-- Small person mascot, sat in its own soft inset glow --}}
                <div class="relative shrink-0">
                    <div class="absolute inset-4 -z-10 rounded-full bg-white/15 blur-xl"></div>
                    <img src="{{ asset('assets/'.rawurlencode('gift card small guy1.png')) }}" alt="" class="h-24 w-auto sm:h-28" loading="lazy">
                </div>
            </div>
        </div>

        <div class="flex flex-1 flex-col rounded-3xl border border-app bg-transparent p-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-strong">{{ __('Recent orders') }}</h3>
                <a href="{{ route('shop.orders.index') }}" class="rounded-full border border-app px-2.5 py-0.5 text-[11px] font-semibold text-body transition hover:text-strong">{{ __('View all') }}</a>
            </div>
            <div class="mt-3 flex flex-1 flex-col space-y-2.5 {{ $recentOrders->isEmpty() ? 'items-center justify-center' : '' }}">
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
                    <div class="text-center">
                        <p class="text-sm text-muted">{{ __('No orders yet') }}</p>
                        <a href="{{ route('shop.index') }}" class="mt-2 inline-block text-sm font-semibold text-brand-500">{{ __('Browse the shop') }} →</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Popular right now, full width. Ranked by the "Popularity rank" (sort) field
     admins set on each product, among products marked Featured. --}}
@if ($popular->isNotEmpty())
    <div class="mt-6">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-strong">{{ __('Popular right now') }}</h3>
            <a href="{{ route('shop.index') }}" class="text-sm font-semibold text-brand-400 hover:text-brand-300">{{ __('See all') }}</a>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
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

{{-- Shop by category, full width, open (no card box), grey icon tiles, bigger/bolder
     as the dashboard's most-focused section. --}}
@if ($shopCategories->isNotEmpty())
    @php
        // Real PNG icons where a good match exists in /assets; Flights/Stays keep
        // their built-in glyphs since no matching asset exists yet.
        $catIconOverrides = [
            'gift-cards' => 'Gift-Rectangle-With-Bow--Streamline-Ultimate.png',
            'mobile-topup' => 'Cashless-Payment-Cad-Top-Up-Wallet-Add--Streamline-Ultimate.png',
            'esims' => 'Sim-Card-2--Streamline-Ultimate.png',
            'bill-payments' => 'Receipt-Slip-1--Streamline-Ultimate.png',
        ];
    @endphp
    <div class="mt-6">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-strong">{{ __('Shop by category') }}</h3>
                <p class="text-sm text-muted">{{ __('Buy gift cards, eSIMs, top-ups & more, right here.') }}</p>
            </div>
            <a href="{{ route('shop.index') }}" class="text-sm font-semibold text-brand-400 hover:text-brand-300">{{ __('All') }}</a>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ($shopCategories as $cat)
                @php $catIcon = $catIconOverrides[$cat->slug] ?? $cat->icon ?? 'bag'; @endphp
                <a href="{{ route('shop.category', $cat) }}" class="group flex items-center gap-3 rounded-2xl p-3 transition hover:surface-2">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-slate-500/12 text-slate-500 transition group-hover:-translate-y-0.5 group-hover:bg-slate-500/18">
                        @if (str_ends_with($catIcon, '.png'))<x-img-icon :name="$catIcon" class="h-7 w-7" />@else<x-icon :name="$catIcon" class="h-7 w-7" />@endif
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-base font-bold text-strong group-hover:text-brand-500">{{ __($cat->name) }}</p>
                        @if ($cat->tagline)<p class="truncate text-xs text-muted">{{ __($cat->tagline) }}</p>@endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif

@include('partials.esim-carousel', ['esimProducts' => $esimProducts, 'transparent' => true])
@endsection
