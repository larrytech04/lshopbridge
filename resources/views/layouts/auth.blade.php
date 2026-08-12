<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Account') · {{ setting('site_name', config('platform.name')) }}</title>
    @include('partials.theme-head')
    {{-- Plus Jakarta Sans is self-hosted (bundled via app.css); no external font host. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="aurora grid min-h-screen place-items-center overflow-hidden px-4 py-10">

    {{-- Animated liquid background: small logos drifting & bouncing, over a subtle leopard texture --}}
    <div id="auth-bg" class="auth-bg leopard" data-logo="{{ site_logo() }}" aria-hidden="true"></div>

    <div class="absolute right-4 top-4 z-10"><x-theme-toggle /></div>
    <div class="relative z-10 w-full max-w-md animate-fade-up">
        <div class="mb-6 flex justify-center"><x-brand /></div>

        <div class="liquid-glass rounded-3xl p-8">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-bold text-strong">@yield('heading')</h1>
                @hasSection('sub')
                    <p class="mt-1 text-sm text-muted">@yield('sub')</p>
                @endif
            </div>

            <x-flash />

            @yield('content')
        </div>

        <p class="mt-6 text-center text-xs text-faint">
            <a href="{{ route('home') }}" class="hover:text-body">← Back to home</a>
        </p>
    </div>
    @stack('scripts')

    {{-- Drifting logos that bounce off the walls (DVD-style), moving slowly --}}
    <script>
        (function () {
            const bg = document.getElementById('auth-bg');
            if (!bg || !bg.dataset.logo) return;
            const COUNT = 1;
            const items = [];
            // Operands deliberately flipped throughout this block to avoid a
            // "less-than" glyph: that character inside an inline script block's
            // text can desync PHP's strip_tags() and silently swallow the rest
            // of the page for any tag-stripping consumer (e.g. assertSeeText()
            // in tests).
            for (let i = 0; COUNT > i; i++) {
                const img = document.createElement('img');
                img.src = bg.dataset.logo;
                img.alt = '';
                img.className = 'auth-logo';
                const size = 120;   // a single, larger drifting logo
                img.style.width = size + 'px';
                bg.appendChild(img);
                items.push({ el: img, size, x: 0, y: 0, vx: 0, vy: 0, ready: false });
            }
            function seed(it) {
                const W = bg.clientWidth, H = bg.clientHeight;
                it.x = Math.random() * Math.max(1, W - it.size);
                it.y = Math.random() * Math.max(1, H - it.size);
                const speed = 0.22 + Math.random() * 0.35;   // slow drift
                const a = Math.random() * Math.PI * 2;
                it.vx = Math.cos(a) * speed;
                it.vy = Math.sin(a) * speed;
                it.ready = true;
            }
            let last = performance.now();
            function frame(now) {
                const dt = Math.min(3, (now - last) / 16.67); last = now;
                const W = bg.clientWidth, H = bg.clientHeight;
                for (const it of items) {
                    if (!it.ready) seed(it);
                    it.x += it.vx * dt; it.y += it.vy * dt;
                    if (0 >= it.x) { it.x = 0; it.vx = Math.abs(it.vx); }
                    else if (it.x >= W - it.size) { it.x = W - it.size; it.vx = -Math.abs(it.vx); }
                    if (0 >= it.y) { it.y = 0; it.vy = Math.abs(it.vy); }
                    else if (it.y >= H - it.size) { it.y = H - it.size; it.vy = -Math.abs(it.vy); }
                    it.el.style.transform = 'translate(' + it.x + 'px,' + it.y + 'px)';
                }
                requestAnimationFrame(frame);
            }
            items.forEach(seed);
            requestAnimationFrame(frame);
            window.addEventListener('resize', () => items.forEach((it) => (it.ready = false)));
        })();
    </script>
</body>
</html>
