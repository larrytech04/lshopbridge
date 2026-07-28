@extends('layouts.public')
@section('title', setting('site_name', config('platform.name')).', Fund Alipay & China wallets from Africa')

@section('content')
@php
    $dc = display_currency();
    $dispRate = $dc['rate'] ?: 1;
    $effRate = $rate / $dispRate;          // display currency -> CNY (today's admin rate)
    $defaultAmount = max(1, round(50000 * $dispRate));
@endphp

{{-- Wraps BOTH leopard-bg sections (hero + services carousel) so the floating glass
     bubbles can roam across the whole leopard-skin area, not just the hero box. --}}
<div class="relative overflow-hidden">
    {{-- Floating glass bubbles: drift on their own, "glass" (blur/brighten) whatever they
         pass over via backdrop-filter, and nudge away from the cursor when it gets close. --}}
    <div class="bubble-float-1 pointer-events-none absolute left-[6%] top-10 z-20 hidden sm:block" aria-hidden="true">
        <div x-data="pushBubble()" @mousemove.window="push($event)" :style="`transform: translate(${dx}px, ${dy}px)`"
             class="glass-bubble h-[90px] w-[90px] overflow-hidden rounded-full transition-transform duration-500 ease-out">
            <img src="{{ asset('assets/'.rawurlencode('hero glassy.jpeg')) }}" alt="" class="h-full w-full object-cover" loading="lazy">
        </div>
    </div>

{{-- HERO --}}
<section data-hero class="leopard relative mx-auto max-w-none px-4 pt-6 sm:px-6 lg:pt-10">
    <div class="animate-fade-up relative z-10 mx-auto flex max-w-3xl flex-col items-center text-center">
        @php
            // Built from fragments so the line breaks differ by viewport (both 3 lines):
            //   desktop → "Your All-in-One Hub for" / "China Funding &" / "Digital Shop"
            //   mobile  → "Your All-in-One Hub" / "for China Funding &" / "Digital Shop"
            $brM = '<br class="sm:hidden">';       // mobile-only break
            $brD = '<br class="hidden sm:block">'; // desktop-only break
            $heroHtml = e(__('Your All-in-One Hub')).' '.$brM
                      . e(__('for')).$brD.' '
                      . e(__('China')).' '.e(__('Funding &')).'<br>'
                      . e(__('Digital Shop'));
        @endphp
        <h1 class="hero-spot relative cursor-default text-center text-[2.05rem] font-black leading-[1.15] tracking-tight text-strong sm:text-[3.25rem] sm:leading-[1.1] lg:text-[4.25rem]"
            x-data="{ mx: '-200px', my: '-200px' }"
            @mousemove="mx = $event.offsetX + 'px'; my = $event.offsetY + 'px'"
            @mouseleave="mx = '-200px'; my = '-200px'"
            :style="`--mx:${mx}; --my:${my}`">{!! $heroHtml !!}<span class="hero-spot-red" aria-hidden="true">{!! $heroHtml !!}</span></h1>
        <p class="mx-auto mt-3 line-clamp-2 max-w-[19rem] text-sm text-body sm:mt-5 sm:line-clamp-none sm:max-w-xl sm:text-lg">
            {{ $hero ? __($hero->subtitle) : __('Top up with MoMo, bank, card or crypto and we deliver to any China wallet automatically, plus shop gift cards, eSIMs, VPN & more, delivered in minutes.') }}
        </p>
        <div class="mt-6 flex flex-nowrap items-center justify-center gap-2 sm:mt-8 sm:gap-3">
            <a href="{{ $hero->cta_url ?? route('register') }}"
               class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-2xl px-4 py-2.5 text-sm font-bold text-white shadow-lg transition duration-300 hover:-translate-y-0.5 sm:gap-2 sm:px-6 sm:py-3 sm:text-base"
               style="background: color-mix(in srgb, var(--color-brand-600) 90%, transparent); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
                <x-img-icon name="Trading-Currency-Exchange--Streamline-Ultimate.png" class="h-4 w-4 sm:h-5 sm:w-5" /> {{ $hero ? __($hero->cta_label) : __('Start funding') }} <x-icon name="arrow-right" class="hidden h-4 w-4 sm:block" />
            </a>
            <a href="{{ route('shop.index') }}" class="glass inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-2xl px-4 py-2.5 text-sm font-semibold text-body transition duration-300 hover:-translate-y-0.5 hover:text-strong sm:gap-2 sm:px-6 sm:py-3 sm:text-base">
                <x-img-icon name="Shop-Sign-Bag--Streamline-Ultimate.png" class="h-4 w-4 sm:h-5 sm:w-5" /> {{ __('Browse the shop') }}
            </a>
        </div>

        {{-- Mobile/tablet: same target graphic used on desktop, in the ring video's old spot.
             Tap to reveal the labels (no hover on touch), same copy/positions as desktop. --}}
        <div class="relative z-0 mx-auto mt-8 w-56 cursor-pointer sm:mt-10 sm:w-64 lg:hidden"
             x-data="{ open: false }" @click="open = !open">
            <div class="relative transition-transform duration-500 ease-out" :class="open ? '-translate-y-1 scale-105' : ''">
                <img src="{{ asset('assets/'.rawurlencode('hero pinpoint.jpeg')) }}" alt=""
                     class="h-auto w-full select-none" draggable="false" @contextmenu.prevent
                     style="-webkit-touch-callout: none;" loading="lazy">
                <span class="absolute -translate-y-1/2 scale-75 px-2 text-center text-[9px] font-bold leading-tight text-orange-600 transition-all duration-300" :class="open ? 'scale-100 opacity-100' : 'opacity-0'" style="top: 56.5%; left: 68%; right: 11%;">{{ __('Instant funding') }}</span>
                <span class="absolute -translate-y-1/2 scale-75 px-2 text-center text-[9px] font-bold leading-tight text-teal-600 transition-all delay-75 duration-300" :class="open ? 'scale-100 opacity-100' : 'opacity-0'" style="top: 67%; left: 68%; right: 11%;">{{ __('Digital shop') }}</span>
                <span class="absolute -translate-y-1/2 scale-75 px-2 text-center text-[9px] font-bold leading-tight text-slate-700 transition-all delay-150 duration-300" :class="open ? 'scale-100 opacity-100' : 'opacity-0'" style="top: 77.2%; left: 68%; right: 11%;">{{ __('Verified agents') }}</span>
                <span class="absolute -translate-y-1/2 scale-75 px-2 text-center text-[9px] font-bold leading-tight text-sky-600 transition-all delay-200 duration-300" :class="open ? 'scale-100 opacity-100' : 'opacity-0'" style="top: 87.5%; left: 68%; right: 11%;">{{ __('Secure & fast') }}</span>
            </div>
        </div>
    </div>

    {{-- Decorative "pinpoint" graphic, bottom-right accent (desktop+), no card. The target
         pops up and each label reveals in a stagger on hover; positions are % of the image
         itself so they track correctly at any size. --}}
    <div class="group absolute bottom-0 right-0 hidden lg:block lg:w-[420px] xl:w-[480px]">
        <div class="relative transition-transform duration-500 ease-out group-hover:-translate-y-2 group-hover:scale-105">
            <img src="{{ asset('assets/'.rawurlencode('hero pinpoint.jpeg')) }}" alt=""
                 class="h-auto w-full select-none" draggable="false" @contextmenu.prevent
                 style="-webkit-touch-callout: none;" loading="lazy">
            <span class="delay-100 absolute -translate-y-1/2 scale-75 px-2 text-center text-[11px] font-bold leading-tight text-orange-600 opacity-0 transition-all duration-300 group-hover:scale-100 group-hover:opacity-100" style="top: 56.5%; left: 68%; right: 11%;">{{ __('Instant funding') }}</span>
            <span class="delay-200 absolute -translate-y-1/2 scale-75 px-2 text-center text-[11px] font-bold leading-tight text-teal-600 opacity-0 transition-all duration-300 group-hover:scale-100 group-hover:opacity-100" style="top: 67%; left: 68%; right: 11%;">{{ __('Digital shop') }}</span>
            <span class="delay-300 absolute -translate-y-1/2 scale-75 px-2 text-center text-[11px] font-bold leading-tight text-slate-700 opacity-0 transition-all duration-300 group-hover:scale-100 group-hover:opacity-100" style="top: 77.2%; left: 68%; right: 11%;">{{ __('Verified agents') }}</span>
            <span class="absolute -translate-y-1/2 scale-75 px-2 text-center text-[11px] font-bold leading-tight text-sky-600 opacity-0 transition-all delay-500 duration-300 group-hover:scale-100 group-hover:opacity-100" style="top: 87.5%; left: 68%; right: 11%;">{{ __('Secure & fast') }}</span>
        </div>
    </div>

    {{-- Ring video, desktop: bottom-left corner accent. The .webm carries a real alpha
         channel (bg keyed out in post), so the spinning ring renders alone on any theme.
         Safari doesn't support that codec and falls back to the plain .mp4, whose own
         background can't be cleanly keyed via CSS (it overlaps in colour with the ring's
         own highlights), so .hero-vid styles that fallback as a deliberate card instead. --}}
    <div class="pointer-events-none absolute bottom-0 left-0 z-0 hidden lg:block lg:w-[300px] xl:w-[360px]">
        <video x-data :class="$el.canPlayType('video/webm;codecs=vp9') ? 'hero-vid-alpha' : 'hero-vid'" class="h-auto w-full" autoplay muted loop playsinline preload="auto">
            <source src="{{ asset('assets/herovid2-alpha.webm') }}" type="video/webm">
            <source src="{{ asset('assets/herovid2.mp4') }}" type="video/mp4">
        </video>
    </div>
</section>

{{-- SERVICES CAROUSEL, sits on the leopard bg (no box), reacts on entry with a delay --}}
@php
    $svc = collect([
        ['Trading-Currency-Exchange--Streamline-Ultimate.png', __('Fund Alipay, WeChat Pay and more'), __('Send to any China wallet, delivered automatically.'), route('public.fund'), 'E-Wallet-Transaction--Streamline-Brooklyn.png'],
        ['Earth-Search--Streamline-Ultimate.png', __('210+ countries covered'), __('Travel eSIMs and local top-ups in over 210 destinations.'), route('shop.category', 'esims'), 'Currencies-World--Streamline-Brooklyn.png'],
        ['Gift-Rectangle-With-Bow--Streamline-Ultimate.png', __('Gift cards'), __('Amazon, Apple, Steam & more, delivered instantly.'), route('shop.category', 'gift-cards'), 'Gift-Cards-1--Streamline-Brooklyn.png'],
        ['Vpn-Shield--Streamline-Ultimate.png', __('Secure VPN'), __('Fast, private VPN for all your devices.'), route('shop.category', 'gc-digital-apps'), 'Security-3--Streamline-Brooklyn.png'],
        ['Products-Shopping-Bags--Streamline-Ultimate.png', __('Digital Marketplace'), __('Gift cards, data, gaming & streaming.'), route('shop.index'), 'Market-1--Streamline-Brooklyn.png'],
        ['Leave-Review-1--Streamline-Brooklyn.png', __('Trusted reviews'), __('Real ratings & reviews from verified buyers.'), route('agents.index'), 'Testimonial-2--Streamline-Brooklyn.png'],
    ])->map(fn ($s) => ['img' => asset('assets/'.$s[0]), 'title' => $s[1], 'desc' => $s[2], 'url' => $s[3], 'img2' => asset('assets/'.$s[4])])->values();
@endphp
<section class="leopard relative mx-auto max-w-none px-4 py-10 sm:px-6"
         x-data="serviceCarousel(@js($svc))" x-intersect.once="shown = true; setTimeout(() => start(), 600)"
         @mouseenter="stop()" @mouseleave="play()">
    {{-- Desktop: full row, active service expands to show its text --}}
    <div class="mx-auto hidden max-w-6xl items-center justify-center gap-3 sm:flex sm:gap-5" :class="shown ? 'carousel-soft' : 'opacity-0'">
        <template x-for="(s, i) in services" :key="i">
            <a :href="s.url" :aria-current="i === active" :title="s.title"
               class="group flex items-center overflow-hidden rounded-full transition-all duration-700 ease-out"
               :class="i === active ? 'flex-1 glass-strong px-3 py-2.5 ring-1 ring-app' : 'shrink-0'">
                <span class="relative grid h-20 w-20 shrink-0 place-items-center rounded-full icon-orb transition-transform duration-700"
                      :class="i === active ? 'scale-105 text-brand-600' : 'text-brand-400 opacity-70 group-hover:opacity-100'">
                    <span class="png-icon absolute inset-0 m-auto h-11 w-11 transition-opacity duration-500 group-hover:opacity-0"
                          :style="`-webkit-mask:url('${s.img}') center/contain no-repeat; mask:url('${s.img}') center/contain no-repeat`"></span>
                    <span class="png-icon absolute inset-0 m-auto h-11 w-11 opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                          :style="`-webkit-mask:url('${s.img2}') center/contain no-repeat; mask:url('${s.img2}') center/contain no-repeat`"></span>
                </span>
                <span x-show="i === active" x-transition:enter="transition ease-out duration-700 delay-200"
                      x-transition:enter-start="opacity-0 -translate-x-3" x-transition:enter-end="opacity-100 translate-x-0"
                      class="ml-3.5 min-w-0 pr-3 text-left">
                    <span class="block truncate text-lg font-bold text-strong" x-text="s.title"></span>
                    <span class="block truncate text-sm text-muted" x-text="s.desc"></span>
                </span>
            </a>
        </template>
    </div>

    {{-- Mobile: one service at a time, full pill with the writing inside, crossfading --}}
    <div class="relative mx-auto h-24 max-w-sm sm:hidden" :class="shown ? 'carousel-soft' : 'opacity-0'">
        <template x-for="(s, i) in services" :key="'m'+i">
            <a :href="s.url" :title="s.title" x-show="i === active"
               x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
               x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
               class="group absolute inset-0 flex items-center gap-3 rounded-full glass-strong px-4 py-3 ring-1 ring-app">
                <span class="relative grid h-14 w-14 shrink-0 place-items-center rounded-full icon-orb text-brand-600">
                    <span class="png-icon absolute inset-0 m-auto h-7 w-7 transition-opacity duration-500 group-hover:opacity-0"
                          :style="`-webkit-mask:url('${s.img}') center/contain no-repeat; mask:url('${s.img}') center/contain no-repeat`"></span>
                    <span class="png-icon absolute inset-0 m-auto h-7 w-7 opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                          :style="`-webkit-mask:url('${s.img2}') center/contain no-repeat; mask:url('${s.img2}') center/contain no-repeat`"></span>
                </span>
                <span class="min-w-0 text-left">
                    <span class="block truncate text-base font-bold text-strong" x-text="s.title"></span>
                    <span class="block truncate text-sm text-muted" x-text="s.desc"></span>
                </span>
            </a>
        </template>
    </div>
</section>
</div>

{{-- ALL-IN-ONE HUB, the two pillars: China funding + digital goods --}}
<section class="mx-auto mt-10 max-w-none px-4 sm:mt-14 sm:px-6" x-data="{ shown: false }" x-intersect.once="shown = true">
    @php
        $reveal = "transition-all duration-500 ease-out";
        $hidden = "shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'";
        $card = "group relative flex flex-col overflow-hidden rounded-3xl surface border border-app p-5 shadow-sm hover:-translate-y-1 hover:shadow-lg sm:rounded-[1.75rem] sm:p-6";
    @endphp
    <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">

        {{-- 1 · Fund China wallets --}}
        <div class="{{ $card }} {{ $reveal }}" style="transition-delay:0ms" :class="{{ $hidden }}">
            <span class="pointer-events-none absolute right-5 top-3 select-none text-6xl font-black leading-none text-brand-500/10 sm:text-7xl">01</span>
            <span class="grid h-12 w-12 place-items-center text-strong sm:h-14 sm:w-14"><x-img-icon name="Cashless-Payment-Cad-Top-Up-Wallet-Add--Streamline-Ultimate.png" class="h-9 w-9 sm:h-10 sm:w-10" /></span>
            <p class="mt-4 text-[11px] font-bold uppercase tracking-wider text-brand-500">{{ __('Fund') }}</p>
            <h3 class="mt-1 text-xl font-extrabold text-strong sm:text-2xl">{{ __('Fund China wallets') }}</h3>
            <p class="mt-2 text-sm text-muted">{{ __('Move money into any China wallet in minutes, pay locally, we deliver in CNY.') }}</p>
            <ul class="mt-4 grid grid-cols-1 gap-x-4 gap-y-2 text-sm text-body sm:grid-cols-2">
                @foreach (['Live exchange rates', 'Instant auto-funding', 'Alipay, WeChat Pay and more', 'Transparent fees', 'Funds delivered in minutes', 'Track every order live'] as $f)
                    <li class="flex items-start gap-2.5"><span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center text-strong"><x-icon name="check" class="h-3.5 w-3.5" /></span> {{ __($f) }}</li>
                @endforeach
            </ul>
            <div class="mt-auto pt-5"><a href="{{ route('public.fund') }}" class="btn btn-primary w-full justify-center py-3">{{ __('Start funding') }} <x-icon name="arrow-right" class="h-4 w-4" /></a></div>
        </div>

        {{-- 2 · Shop digital goods --}}
        <div class="{{ $card }} {{ $reveal }}" style="transition-delay:120ms" :class="{{ $hidden }}">
            <span class="pointer-events-none absolute right-5 top-3 select-none text-6xl font-black leading-none text-brand-500/10 sm:text-7xl">02</span>
            <span class="grid h-12 w-12 place-items-center text-strong sm:h-14 sm:w-14"><x-img-icon name="Products-Shopping-Bags--Streamline-Ultimate.png" class="h-9 w-9 sm:h-10 sm:w-10" /></span>
            <p class="mt-4 text-[11px] font-bold uppercase tracking-wider text-brand-500">{{ __('Shop') }}</p>
            <h3 class="mt-1 text-xl font-extrabold text-strong sm:text-2xl">{{ __('Shop digital goods') }}</h3>
            <p class="mt-2 text-sm text-muted">{{ __('Gift cards, eSIMs, top-ups, apps & games, delivered to your account instantly.') }}</p>
            <div class="mt-4 grid grid-cols-2 gap-2">
                @foreach ([['Gift Cards','Gift-Rectangle-With-Bow--Streamline-Ultimate.png','gift-cards'],['eSIMs','Sim-Card-2--Streamline-Ultimate.png','esims'],['Digital Apps','Vpn-Shield--Streamline-Ultimate.png','gc-digital-apps'],['Games','Vr-360-Remote-Controller--Streamline-Ultimate.png','gc-games']] as [$lbl, $ic, $cat])
                    <a href="{{ route('shop.category', $cat) }}" class="group/cat flex items-center gap-2.5 rounded-xl surface px-3 py-2.5 text-sm font-medium text-body ring-1 ring-app transition hover:-translate-y-0.5 hover:ring-brand-400/40">
                        <span class="grid h-7 w-7 shrink-0 place-items-center text-strong"><x-img-icon :name="$ic" class="h-5 w-5" /></span>
                        <span class="truncate">{{ __($lbl) }}</span>
                        <x-icon name="arrow-right" class="ml-auto h-3.5 w-3.5 shrink-0 text-muted opacity-0 transition group-hover/cat:opacity-100" />
                    </a>
                @endforeach
            </div>
            <div class="mt-auto pt-5"><a href="{{ route('shop.index') }}" class="btn btn-ghost w-full justify-center py-3">{{ __('Browse the shop') }} <x-icon name="arrow-right" class="h-4 w-4" /></a></div>
        </div>

    </div>
</section>

{{-- ACCEPTED PAYMENT METHODS --}}
<section class="mx-auto mt-12 max-w-none px-4 py-4 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ cms('cms_home_payments_title', __('Accepted payment methods')) }}</h2>
        <a href="{{ route('public.payment-methods') }}" class="text-sm font-semibold text-brand-500 hover:text-brand-600">{{ __('All methods') }} →</a>
    </div>
    @php $allPay = collect(config('payments.accepted'))->collapse(); @endphp
    <div class="pay-marquee mt-6">
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
</section>

{{-- POPULAR GIFT CARDS --}}
@if ($giftCards->count())
<section class="mx-auto mt-16 max-w-none px-4 sm:px-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ cms('cms_home_giftcards_title', __('Popular gift cards')) }}</h2>
            <p class="mt-1 text-sm text-muted sm:text-base">{{ cms('cms_home_giftcards_subtitle', __('Top brands, delivered instantly to your account.')) }}</p>
        </div>
        <a href="{{ route('shop.category', 'gift-cards') }}" class="shrink-0 text-sm font-semibold text-brand-500 hover:text-brand-600">{{ __('See all') }} →</a>
    </div>
    <div class="no-scrollbar mt-6 flex snap-x gap-4 overflow-x-auto pb-2">
        @foreach ($giftCards as $gc)
            @php
                $av = $gc->variants->where('is_active', true);
                $gcMin = $av->min('price'); $gcMax = $av->max('price');
                $gcRange = $gcMin === null ? '' : ($gcMin == $gcMax ? disp($gcMin) : disp($gcMin).' – '.disp($gcMax));
            @endphp
            <a href="{{ route('shop.show', $gc) }}" class="group w-40 shrink-0 snap-start sm:w-48">
                <div class="grid aspect-[16/10] place-items-center overflow-hidden rounded-2xl bg-white px-4 text-center shadow-sm ring-1 ring-black/5 transition duration-300 group-hover:-translate-y-1 group-hover:shadow-lg">
                    <span class="text-lg font-extrabold leading-tight tracking-tight text-gray-900 sm:text-xl">{{ $gc->brand ?? $gc->name }}</span>
                </div>
                <p class="mt-2 truncate text-sm font-semibold text-strong">{{ $gc->name }}</p>
                <p class="text-sm text-muted">{{ $gcRange }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- TRAVEL eSIMs --}}
@include('partials.esim-carousel', ['esimProducts' => $esimProducts])

{{-- FEATURES, expanding accordion (hover a panel to reveal its details) --}}
@php
    $features = [
        ['shield', 'Bank-grade security', 'KYC tiers, encrypted documents, audit logs and automatic fraud screening on every transaction.', 'network', 'Security-Shield-Rate-Stars--Streamline-Freehand.png'],
        ['swap', 'Automatic funding', 'Webhook-confirmed payments trigger instant payouts to your saved China wallet.', 'money', 'Investment-Agreement--Streamline-Nova.svg'],
        ['chart', 'Transparent pricing', 'See the exact rate and fee before you confirm. No surprises, ever.', 'sphere', 'Pricing-Consumption--Streamline-Carbon.svg'],
        ['truck', 'Verified agents', 'Hire trusted procurement & shipping agents with ratings and warehouse details.', 'cube', 'Verified--Streamline-Rounded-Streamline-Material.png'],
        ['book', 'China buying academy', 'Step-by-step guides for 1688, Taobao, Pinduoduo, Alipay, shipping and customs.', 'torus', 'Global-Learning--Streamline-Sharp-Remix.svg'],
        ['clock', '24/7 self-service', 'Track every deposit and funding order in real time from your dashboard.', 'wave', 'Phone-Action-24-Hour-Service--Streamline-Nova.png'],
    ];
@endphp
<section class="mx-auto mt-24 hidden max-w-none px-4 sm:px-6 md:block">
    <div class="mb-8 text-center sm:mb-10">
        <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ cms('cms_home_features_title', __('Everything you need, in one place')) }}</h2>
    </div>

    {{-- Desktop: horizontal expanding accordion --}}
    <div class="mx-auto hidden h-[22.5rem] max-w-6xl md:flex" x-data="{ active: 1 }">
        @foreach ($features as $i => [$icon, $title, $body, $shape, $iconImg])
            <div @mouseenter="active = {{ $i }}" @click="active = {{ $i }}"
                 class="card-solid relative grow basis-0 cursor-pointer overflow-hidden rounded-3xl border border-app shadow-sm transition-all duration-500 ease-out {{ $i ? '-ml-6' : '' }}"
                 :class="active === {{ $i }} ? 'grow-[5] shadow-2xl' : 'hover:border-brand-400/40'"
                 :style="'z-index: ' + (active === {{ $i }} ? 50 : 30 - Math.abs(active - {{ $i }}))">
                {{-- Collapsed: vertical label --}}
                <div class="absolute inset-0 flex flex-col items-center justify-between py-6 transition-opacity duration-300" :class="active === {{ $i }} ? 'pointer-events-none opacity-0' : 'opacity-100'">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-slate-400/15 text-slate-600"><x-img-icon :name="$iconImg" class="h-6 w-6" /></span>
                    <span class="feat-vert text-sm font-semibold text-strong">{{ __($title) }}</span>
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400/50"></span>
                </div>
                {{-- Expanded: full content --}}
                <div class="absolute inset-0 flex min-w-[18rem] flex-col justify-between p-7 transition-opacity duration-500 delay-100" :class="active === {{ $i }} ? 'opacity-100' : 'pointer-events-none opacity-0'">
                    @if ($shape === 'network')
                        <img src="{{ asset('assets/' . rawurlencode('bank security.png')) }}" alt="{{ __($title) }}"
                             class="pointer-events-none absolute bottom-0 right-1 h-[18.5rem] w-auto max-w-[48%] object-contain object-bottom lg:right-3 lg:h-[20.5rem]" />
                    @elseif ($shape === 'money')
                        <img src="{{ asset('assets/' . rawurlencode('automatic funding.png')) }}" alt="{{ __($title) }}"
                             class="pointer-events-none absolute bottom-0 right-1 h-[18.5rem] w-auto max-w-[48%] object-contain object-bottom lg:right-3 lg:h-[20.5rem]" />
                    @elseif ($shape === 'sphere')
                        <img src="{{ asset('assets/' . rawurlencode('transparent pricing.png')) }}" alt="{{ __($title) }}"
                             class="pointer-events-none absolute bottom-0 right-1 h-[18.5rem] w-auto max-w-[48%] object-contain object-bottom lg:right-3 lg:h-[20.5rem]" />
                    @elseif ($shape === 'cube')
                        <img src="{{ asset('assets/' . rawurlencode('verified agents.png')) }}" alt="{{ __($title) }}"
                             class="pointer-events-none absolute bottom-0 right-1 h-[18.5rem] w-auto max-w-[48%] object-contain object-bottom lg:right-3 lg:h-[20.5rem]" />
                    @elseif ($shape === 'torus')
                        <img src="{{ asset('assets/' . rawurlencode('china buying academy.png')) }}" alt="{{ __($title) }}"
                             class="pointer-events-none absolute bottom-0 right-1 h-[18.5rem] w-auto max-w-[48%] object-contain object-bottom lg:right-3 lg:h-[20.5rem]" />
                    @elseif ($shape === 'wave')
                        <img src="{{ asset('assets/' . rawurlencode('self-service (wave).png')) }}" alt="{{ __($title) }}"
                             class="pointer-events-none absolute bottom-0 right-1 h-[18.5rem] w-auto max-w-[48%] object-contain object-bottom lg:right-3 lg:h-[20.5rem]" />
                    @else
                        <x-shape :name="$shape" class="pointer-events-none absolute right-4 top-4 h-28 w-28 text-slate-400 lg:h-40 lg:w-40" />
                    @endif
                    <span class="relative grid h-14 w-14 place-items-center rounded-2xl bg-slate-400/15 text-slate-600"><x-img-icon :name="$iconImg" class="h-8 w-8" /></span>
                    <div class="relative max-w-[50%]">
                        <h3 class="text-xl font-bold text-strong">{{ __($title) }}</h3>
                        <p class="mt-2 text-sm text-muted">{{ __($body) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Mobile: stacked cards --}}
    <div class="grid gap-3 sm:grid-cols-2 md:hidden">
        @foreach ($features as [$icon, $title, $body])
            <div class="card-solid rounded-2xl border border-app p-5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-slate-400/15 text-slate-500"><x-icon :name="$icon" class="h-5 w-5" /></span>
                <h3 class="mt-3 font-semibold text-strong">{{ __($title) }}</h3>
                <p class="mt-1.5 text-sm text-muted">{{ __($body) }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- REVIEWS, flipping Trustpilot/Google summary + auto-scrolling testimonials --}}
@php
    $gword = '<span class="g-wordmark"><span style="color:#4285F4">G</span><span style="color:#EA4335">o</span><span style="color:#FBBC05">o</span><span style="color:#4285F4">g</span><span style="color:#34A853">l</span><span style="color:#EA4335">e</span></span>';
    $avatarPalette = [['#ffe4e6','#e11d48'],['#e0f2fe','#0284c7'],['#d1fae5','#059669'],['#ede9fe','#7c3aed'],['#fef3c7','#b45309'],['#e0e7ff','#4f46e5'],['#ccfbf1','#0d9488'],['#fae8ff','#c026d3']];
@endphp
<section class="mx-auto mt-24 max-w-none px-4 sm:px-6" x-data="{ scroll(d) { $refs.revRow.scrollBy({ left: d * $refs.revRow.clientWidth * 0.8, behavior: 'smooth' }) } }">
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ cms('cms_home_reviews_title', __('What our customers say')) }}</h2>
        <div class="flex items-center gap-2">
            <button type="button" @click="scroll(-1)" aria-label="{{ __('Previous') }}" class="grid h-9 w-9 place-items-center rounded-full border border-app surface text-muted transition hover:text-strong"><x-icon name="chevron-right" class="h-4 w-4 rotate-180" /></button>
            <button type="button" @click="scroll(1)" aria-label="{{ __('Next') }}" class="grid h-9 w-9 place-items-center rounded-full border border-app surface text-muted transition hover:text-strong"><x-icon name="chevron-right" class="h-4 w-4" /></button>
        </div>
    </div>

    <div class="mt-6 space-y-4 sm:relative sm:h-52 sm:space-y-0">
        {{-- Flipping summary box (Trustpilot ⇄ Google) --}}
        <div class="rev-flip h-44 w-full shadow-xl sm:absolute sm:left-0 sm:top-0 sm:z-20 sm:h-52 sm:w-72" x-data="reviewFlip()" :class="{ 'is-flipped': flipped }">
            <div class="rev-flip__inner">
                {{-- Trustpilot --}}
                <div class="rev-flip__face flex flex-col justify-center rounded-2xl card-solid border border-app p-5">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-1.5" style="color:#00b67a"><x-icon name="star" class="h-5 w-5 fill-current" /><span class="text-base font-bold text-strong">Trustpilot</span></span>
                        <span class="text-2xl font-black text-strong">{{ cms('cms_reviews_trustpilot_rating', '4.5') }}<span class="text-sm font-medium text-muted"> / 5</span></span>
                    </div>
                    <x-stars variant="trustpilot" :rating="(float) cms('cms_reviews_trustpilot_rating', '4.5')" size="h-5 w-5" inner="h-3 w-3" class="mt-3 gap-1" />
                    <p class="mt-4 text-sm text-muted">{{ cms('cms_reviews_trustpilot_count', '460+') }} {{ __('reviews on Trustpilot') }}</p>
                    <p class="mt-1 text-sm font-bold text-strong">{{ cms('cms_reviews_trusted_since', __('Trusted since 2024')) }}</p>
                </div>
                {{-- Google --}}
                <div class="rev-flip__face rev-flip__back flex flex-col justify-center rounded-2xl card-solid border border-app p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-base font-bold">{!! $gword !!}</span>
                        <span class="text-2xl font-black text-strong">{{ cms('cms_reviews_google_rating', '4.8') }}<span class="text-sm font-medium text-muted"> / 5</span></span>
                    </div>
                    <x-stars variant="amber" :rating="(float) cms('cms_reviews_google_rating', '4.8')" size="h-5 w-5" class="mt-3" />
                    <p class="mt-4 text-sm text-muted">{{ cms('cms_reviews_google_count', '1,200+') }} {{ __('reviews on Google') }}</p>
                    <p class="mt-1 text-sm font-bold text-strong">{{ cms('cms_reviews_trusted_since', __('Trusted since 2024')) }}</p>
                </div>
            </div>
        </div>

        {{-- Manually-scrolled testimonials (prev/next buttons; pass under the summary box) --}}
        <div x-ref="revRow" class="no-scrollbar overflow-x-auto scroll-smooth sm:absolute sm:inset-0">
            <div class="flex w-max gap-4 sm:pl-[19rem]">
                @foreach ($testimonials as $t)
                    @php
                        $ac = $avatarPalette[abs(crc32($t->name)) % count($avatarPalette)];
                    @endphp
                    <div class="flex h-44 w-72 shrink-0 flex-col rounded-2xl card-solid border border-app p-4 sm:h-52 sm:w-80 sm:p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <img src="{{ local_avatar($t->name, $ac[0]) }}" alt="{{ $t->name }}" loading="lazy" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-app" style="background: {{ $ac[0] }}" />
                                <div>
                                    <div class="flex items-center gap-1 text-sm font-bold text-strong">{{ $t->name }} @if ($t->verified)<x-verified-tick class="h-3.5 w-3.5" />@endif</div>
                                    <p class="text-xs text-muted">{{ $t->review_date?->format('M j, Y') }}</p>
                                </div>
                            </div>
                            @if ($t->source === 'trustpilot')
                                <span class="inline-flex shrink-0 items-center gap-1 text-xs font-bold text-strong"><x-icon name="star" class="h-3.5 w-3.5 fill-current" style="color:#00b67a" />Trustpilot</span>
                            @elseif ($t->source === 'google')
                                <span class="shrink-0 text-sm font-bold">{!! $gword !!}</span>
                            @endif
                        </div>
                        <x-stars :variant="$t->source === 'trustpilot' ? 'trustpilot' : 'amber'" :rating="(float) $t->rating" class="mt-3" />
                        <p class="mt-2.5 line-clamp-3 text-sm text-body">{{ $t->text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- AGENTS PREVIEW --}}
@if ($agents->isNotEmpty())
<section class="mx-auto mt-24 max-w-none px-4 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ __('Trusted shipping agents') }}</h2>
            <p class="mt-2 text-muted">{{ __('Verified procurement & freight partners in China.') }}</p>
        </div>
        <a href="{{ route('agents.index') }}" class="text-sm font-semibold text-brand-300 hover:text-brand-200">{{ __('View all agents') }} →</a>
    </div>
    <div class="mt-8 rounded-3xl border border-app card-solid px-5 shadow-sm sm:px-7">
        @foreach ($agents as $agent)
            @include('public.agents._p2p_row', ['agent' => $agent])
        @endforeach
    </div>
</section>
@endif

{{-- INTEGRATIONS CATALOG, wallets & digital-product providers we integrate --}}
@php
    // Centered receding rows: a large glossy front line of 8, with the rest falling back behind it.
    $integRow4 = ['orange', 'airtel', 'mpesa', 'discord', 'binance']; // furthest back, the leftover few
    $integRow3 = ['flutterwave', 'telegram', 'playstation', 'xbox', 'pubg', 'freefire'];
    $integRow2 = ['netflix', 'googleplay', 'spotify', 'paypal', 'mtn', 'usdt', 'ebay', 'steam'];
    $integFront = ['alipay', 'wechatpay', 'unionpay', 'qq', 'amazon', 'visa', 'mastercard', 'apple']; // front line of 8
@endphp
<section class="integ-scene relative -mb-24 mt-24 overflow-hidden pt-28 pb-10 sm:pt-40 sm:pb-12">
    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6">
        <h2 class="integ-heading text-4xl font-extrabold leading-[1.05] tracking-tight text-strong sm:text-5xl lg:text-6xl">{{ __('Our Ecosystem') }}</h2>
    </div>
    {{-- Wider floor breaks out of the text column so the rows spread across the scene --}}
    <div class="integ-floor relative mx-auto mt-12 flex max-w-[120rem] flex-col items-center px-4 sm:mt-16 sm:px-6">
        {{-- Row 4, furthest back, smallest & dimmest (the leftover few) --}}
        <div class="flex flex-wrap justify-center gap-2 opacity-35 blur-[1.2px] sm:gap-5 lg:gap-10">
            @foreach ($integRow4 as $app)
                <x-app-logo :name="$app" class="h-6 w-6 shrink-0 sm:h-9 sm:w-9" />
            @endforeach
        </div>
        {{-- Row 3 --}}
        <div class="-mt-1 flex flex-wrap justify-center gap-2 opacity-55 blur-[0.6px] sm:-mt-2 sm:gap-5 lg:gap-10">
            @foreach ($integRow3 as $app)
                <x-app-logo :name="$app" class="h-7 w-7 shrink-0 sm:h-10 sm:w-10" />
            @endforeach
        </div>
        {{-- Row 2 --}}
        <div class="-mt-1 flex flex-wrap justify-center gap-1.5 opacity-80 blur-[0.2px] sm:-mt-2 sm:gap-4 lg:gap-9">
            @foreach ($integRow2 as $app)
                <x-app-logo :name="$app" class="h-8 w-8 shrink-0 sm:h-11 sm:w-11 lg:h-12 lg:w-12" />
            @endforeach
        </div>
        {{-- Front line, 8 logos, largest, glossy with reflection (closest) --}}
        <div class="relative z-10 -mt-1 flex flex-wrap justify-center gap-1 sm:-mt-2 sm:gap-4 lg:gap-10">
            @foreach ($integFront as $app)
                <x-app-logo :name="$app" class="integ-tile h-9 w-9 shrink-0 sm:h-12 sm:w-12 lg:h-16 lg:w-16" />
            @endforeach
        </div>
    </div>
</section>
@endsection
