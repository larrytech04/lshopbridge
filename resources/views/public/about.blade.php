@extends('layouts.public')
@section('title', ($page->meta_title ?: $page->title ?: __('About us')).' · '.config('platform.name'))
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->excerpt ?: ''), 160))

@php
    $brand = setting('site_name', config('platform.name'));
    $aboutHeading = __('Bridging Africa & China, one payment at a time.');
    $lead = $page->excerpt ?: __('We make it simple, fast and safe for people across Africa to fund China wallets and buy digital products, no Chinese bank account, no middlemen, no surprises.');
    $stats = [
        ['num' => 190, 'suffix' => '+', 'label' => __('Countries reachable')],
        ['text' => __('Instant'), 'label' => __('Wallet funding')],
        ['num' => 24, 'suffix' => '/7', 'label' => __('Self-service & support')],
        ['num' => 100, 'suffix' => '%', 'label' => __('Secure & encrypted')],
    ];
    $values = [
        ['Vpn-Shield--Streamline-Ultimate.png', __('Bank-grade security'), __('KYC tiers, encrypted documents and automatic fraud screening on every transaction.'), 'emerald'],
        ['Cashless-Payment-Cad-Top-Up-Wallet-Add--Streamline-Ultimate.png', __('Built for speed'), __('Automated, webhook-confirmed payments trigger instant payouts and delivery.'), 'amber'],
        ['Pricing-Consumption--Streamline-Carbon.svg', __('Radical transparency'), __('See the exact rate and fee before you confirm. No hidden charges, ever.'), 'sky'],
        ['Headphones-Customer-Support-Human-1--Streamline-Ultimate.png', __('Human support'), __('Real people on chat, WhatsApp and email whenever you need a hand.'), 'rose'],
    ];
    $steps = [
        [__('Create your account'), __('Sign up free and verify your identity to unlock funding and higher limits.')],
        [__('Top up your wallet'), __('Add funds with Mobile Money, bank transfer, card or crypto.')],
        [__('Fund or shop'), __('Send to any China wallet, or buy gift cards, eSIMs and top-ups.')],
        [__('Delivered instantly'), __('Most orders confirm within seconds, track everything from your dashboard.')],
    ];
@endphp

@section('content')
{{-- Breadcrumb --}}
<div class="mx-auto max-w-6xl px-4 pt-6 sm:px-6">
    <nav class="flex flex-wrap items-center gap-2 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('home') }}" class="hover:text-strong">{{ __('Home') }}</a>
        <x-img-icon name="Arrow-Button-Right-3--Streamline-Ultimate.png" class="h-3 w-3 text-faint" />
        <span class="font-semibold text-strong">{{ __('About us') }}</span>
    </nav>
</div>

{{-- Hero --}}
<section class="mx-auto max-w-6xl px-4 pb-8 pt-4 sm:px-6 sm:pt-6">
    <div class="grid items-center gap-6 lg:grid-cols-2">
        <div>
            <h1 data-reveal class="reveal-heading text-3xl font-extrabold leading-[1.1] tracking-tight text-strong sm:text-4xl lg:text-5xl">
                <span>{{ $aboutHeading }}</span>
                <span class="reveal-red" aria-hidden="true">{{ $aboutHeading }}</span>
            </h1>
            <p class="mt-4 max-w-xl text-base text-body sm:text-lg">{{ $lead }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="liquid-glass-brand inline-flex items-center justify-center gap-2 rounded-xl px-7 py-3 text-base font-semibold transition hover:-translate-y-0.5">{{ __('Create free account') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
                <a href="{{ route('shop.index') }}" class="liquid-glass inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 text-base font-semibold text-strong transition hover:-translate-y-0.5">{{ __('Explore the shop') }}</a>
            </div>
        </div>
        <div class="flex justify-center">
            <img src="{{ asset('assets/'.rawurlencode('about us.png')) }}" alt="{{ $brand }}" class="mx-auto max-h-64 w-auto" loading="lazy">
        </div>
    </div>
</section>

{{-- Stats band (solid brand) --}}
<section class="w-full bg-brand-700 text-white">
    <div class="mx-auto grid max-w-6xl grid-cols-2 gap-8 px-4 py-12 text-center sm:grid-cols-4 sm:px-6">
        @foreach ($stats as $stat)
            <div>
                <p class="text-3xl font-extrabold sm:text-4xl">
                    @isset($stat['num'])
                        <span class="count-up" data-count="{{ $stat['num'] }}" data-suffix="{{ $stat['suffix'] ?? '' }}">0{{ $stat['suffix'] ?? '' }}</span>
                    @else
                        {{ $stat['text'] }}
                    @endisset
                </p>
                <p class="mt-1 text-sm text-white/75">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- Mission --}}
<section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
    <div class="grid items-center gap-10 lg:grid-cols-2">
        <div class="order-2 flex justify-center lg:order-1">
            <img src="{{ asset('assets/'.rawurlencode('about.png')) }}" alt="{{ __('Our mission') }}" class="w-full max-w-lg" loading="lazy">
        </div>
        <div class="order-1 lg:order-2">
            <h2 class="text-3xl font-bold text-strong sm:text-4xl">{{ __('Our mission') }}</h2>
            <p class="mt-4 text-lg text-body">{{ __('Cross-border payments to China have always been slow, expensive and confusing for African buyers. We built :brand to change that.', ['brand' => $brand]) }}</p>
            <p class="mt-4 text-body">{{ __('From a single dashboard you can fund any major China wallet, buy digital products, and connect with verified shipping agents, with clear pricing and instant delivery. We handle the complexity so you can focus on growing your business or getting what you need.') }}</p>
            <div class="mt-6 space-y-3">
                @foreach ([__('No Chinese bank account required'), __('Transparent, upfront rates & fees'), __('Money-back protection on every order')] as $point)
                    <p class="flex items-center gap-3 text-body"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-slate-500/12 text-brand-500"><x-icon name="check" class="h-4 w-4" /></span> {{ $point }}</p>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Shop spotlight --}}
<section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
    <div class="grid items-center gap-10 lg:grid-cols-2">
        <div>
            <span class="text-xs font-bold uppercase tracking-widest text-brand-500">{{ __('The digital shop') }}</span>
            <h2 class="mt-2 text-3xl font-bold text-strong sm:text-4xl">{{ __('More than payments, a full digital shop') }}</h2>
            <p class="mt-4 text-body">{{ __('Alongside wallet funding, :brand is a marketplace for instant digital goods, pay in your currency and get delivery to your account in seconds.', ['brand' => $brand]) }}</p>
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach ([__('Gift cards'), __('eSIMs'), __('Mobile top-ups'), __('Bill payments'), __('Games & apps'), __('Flights & stays')] as $chip)
                    <span class="pill surface border border-app text-body">{{ $chip }}</span>
                @endforeach
            </div>
            <a href="{{ route('shop.index') }}" class="btn btn-primary mt-6 px-6 py-3">{{ __('Explore the shop') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
        <div class="text-center">
            <img src="{{ asset('assets/'.rawurlencode('shop cart.png')) }}" alt="{{ __('Digital shop') }}" class="img-float mx-auto max-h-72 w-auto" loading="lazy">
            <div class="float-shadow mx-auto mt-4"></div>
        </div>
    </div>
</section>

{{-- Values, video band with glass cards --}}
<section class="leopard relative w-full overflow-hidden bg-slate-900">
    {{-- Source clip is portrait; rotated 90deg and over-scaled so the landscape band is always fully covered --}}
    <video class="absolute left-1/2 top-1/2 h-[200%] w-[200%] -translate-x-1/2 -translate-y-1/2 rotate-90 object-cover opacity-40" src="{{ asset('assets/'.rawurlencode('about vid.mp4')) }}" autoplay muted loop playsinline preload="auto"></video>
    <div class="pointer-events-none absolute inset-0 bg-slate-900/70"></div>
    <div class="pointer-events-none absolute -left-16 -top-16 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-20 -right-10 h-80 w-80 rounded-full bg-sky-400/20 blur-3xl"></div>
    <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-white sm:text-4xl">{{ __('What we stand for') }}</h2>
            <p class="mt-3 text-white/70">{{ __('The principles behind every feature we build.') }}</p>
        </div>
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($values as [$icon, $title, $body, $tint])
                @php
                    // Literal, complete class strings per tint (not interpolated) so
                    // Tailwind's build-time scanner can find and generate them, same
                    // convention as components/guide-icon.blade.php.
                    [$glow, $badge, $iconColor, $ring] = match ($tint) {
                        'emerald' => ['bg-emerald-400/20', 'bg-emerald-400/15', 'text-emerald-300', 'ring-emerald-400/25'],
                        'amber' => ['bg-amber-400/20', 'bg-amber-400/15', 'text-amber-300', 'ring-amber-400/25'],
                        'sky' => ['bg-sky-400/20', 'bg-sky-400/15', 'text-sky-300', 'ring-sky-400/25'],
                        'rose' => ['bg-rose-400/20', 'bg-rose-400/15', 'text-rose-300', 'ring-rose-400/25'],
                        default => ['bg-white/20', 'bg-white/15', 'text-white', 'ring-white/25'],
                    };
                @endphp
                <div class="glass glass-hover group relative overflow-hidden rounded-3xl p-6">
                    <span class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full {{ $glow }} blur-2xl transition-opacity group-hover:opacity-80"></span>
                    <span class="relative grid h-14 w-14 place-items-center rounded-2xl {{ $badge }} {{ $iconColor }} ring-1 {{ $ring }}"><x-img-icon :name="$icon" class="h-6 w-6" /></span>
                    <h3 class="relative mt-5 font-bold text-white">{{ $title }}</h3>
                    <p class="relative mt-2 text-sm leading-relaxed text-white/70">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- How it works --}}
<section class="w-full surface border-y border-app">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-strong sm:text-4xl">{{ __('How it works') }}</h2>
            <p class="mt-3 text-body">{{ __('From sign-up to delivery in four simple steps.') }}</p>
        </div>
        {{-- Two infographics side by side: funding (left) & shop (right), steps wrapped around each loop --}}
        @php
            $shopStepsAbout = [
                [__('Open the shop'), __('Browse gift cards, eSIMs, top-ups, bills & more, right from your dashboard.')],
                [__('Pick & pay'), __('Choose your product and pay from your wallet, MoMo, bank, card or crypto.')],
                [__('Instant delivery'), __('Codes, PINs and eSIM QR details arrive in your account within seconds.')],
                [__('Redeem & enjoy'), __('Follow the simple redeem steps, eSIMs install by scanning the QR code.')],
            ];
            $journeys = [
                [__('Fund a China wallet'), $steps],
                [__('Shop digital products'), $shopStepsAbout],
            ];
            // [placement classes, mirrored (number on the right, text right-aligned)], staggered so they hug the curve
            $miniPos = [
                ['lg:absolute lg:left-0 lg:top-[2%] lg:w-56', true],       // 01, upper-left
                ['lg:absolute lg:left-[5%] lg:bottom-[5%] lg:w-56', true], // 02, lower-left, tucked in
                ['lg:absolute lg:right-[5%] lg:top-[5%] lg:w-56', false],  // 03, upper-right, tucked in
                ['lg:absolute lg:right-0 lg:bottom-[2%] lg:w-56', false],  // 04, lower-right
            ];
        @endphp
        <div class="mt-12 grid gap-16 lg:grid-cols-2 lg:gap-10">
            @foreach ($journeys as [$jTitle, $jSteps])
                <div>
                    <h3 class="text-center text-lg font-bold text-strong">{{ $jTitle }}</h3>
                    <div class="relative mx-auto mt-6 lg:h-[28rem]">
                        {{-- Floating loop with hanging shadow (dead-centre on desktop) --}}
                        <div class="mx-auto text-center lg:absolute lg:inset-x-0 lg:top-1/2 lg:-translate-y-1/2">
                            <img src="{{ asset('assets/'.rawurlencode('how it works aboutpg.png')) }}" alt="{{ $jTitle }}"
                                 class="img-float mx-auto h-44 w-auto sm:h-52 lg:h-48" loading="lazy">
                            <div class="float-shadow mx-auto mt-4"></div>
                        </div>

                        {{-- Steps positioned around the loop --}}
                        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:mt-0 lg:block">
                            @foreach ($jSteps as $i => [$title, $body])
                                <div class="{{ $miniPos[$i][0] }} flex items-start gap-2.5 {{ $miniPos[$i][1] ? 'lg:flex-row-reverse lg:text-right' : '' }}">
                                    <span class="shrink-0 text-3xl font-black leading-none tracking-tight text-brand-600">0{{ $i + 1 }}</span>
                                    <div>
                                        <h4 class="text-sm font-bold text-strong">{{ $title }}</h4>
                                        <p class="mt-1 text-xs leading-relaxed text-muted">{{ $body }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<script>
    (function () {
        document.querySelectorAll('[data-reveal]').forEach(function (el) {
            el.addEventListener('pointermove', function (e) {
                const r = el.getBoundingClientRect();
                el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                el.style.setProperty('--my', (e.clientY - r.top) + 'px');
            });
            el.addEventListener('pointerleave', function () {
                el.style.setProperty('--mx', '-300px');
                el.style.setProperty('--my', '-300px');
            });
        });

        // Count-up stats, animate from 0 to target when they scroll into view.
        const counters = document.querySelectorAll('.count-up');
        const run = function (el) {
            const target = parseFloat(el.dataset.count) || 0;
            const suffix = el.dataset.suffix || '';
            const dur = 1300, start = performance.now();
            const tick = function (now) {
                const t = Math.min(1, (now - start) / dur);
                const eased = 1 - Math.pow(1 - t, 3);
                el.textContent = Math.round(eased * target).toLocaleString() + suffix;
                if (t < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        };
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
            }, { threshold: 0.4 });
            counters.forEach(function (el) { io.observe(el); });
        } else {
            counters.forEach(run);
        }
    })();
</script>
@endsection
