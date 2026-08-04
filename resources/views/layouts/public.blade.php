<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $__title = trim($__env->yieldContent('title')) ?: setting('site_name', config('platform.name')).', '.config('platform.tagline');
        $__description = trim($__env->yieldContent('meta_description')) ?: config('platform.tagline');
        $__ogImage = trim($__env->yieldContent('og_image')) ?: site_logo();
    @endphp
    <title>{{ $__title }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($__description, 300) }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ setting('site_name', config('platform.name')) }}">
    <meta property="og:title" content="{{ $__title }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($__description, 200) }}">
    <meta property="og:image" content="{{ $__ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $__title }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($__description, 200) }}">
    @include('partials.theme-head')
    {{-- Plus Jakarta Sans is self-hosted (bundled via app.css); no external font host. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="aurora min-h-screen overflow-x-hidden">

    {{-- Branded boot loader: covers the blank gap on a hard refresh with the
         logo centered in a spinning ring, faded out once the page has fully
         loaded. Public pages only — the dashboard has its own skeleton loader
         for in-app navigation instead (see partials/page-skeleton). --}}
    <div id="boot-loader" aria-hidden="true">
        <div class="boot-loader__ring">
            <img src="{{ site_favicon() }}" alt="" class="boot-loader__logo">
        </div>
    </div>
    <script>
        // Held for at least ~1.5s so the brand moment actually registers even
        // on a fast connection. On a slow/poor connection this adds nothing —
        // it only ever waits for the real `load` event, which naturally takes
        // longer, so the spinner keeps going until the page is truly ready.
        const __bootStart = Date.now();
        window.addEventListener('load', () => {
            const remaining = Math.max(0, 1500 - (Date.now() - __bootStart));
            setTimeout(() => {
                document.getElementById('boot-loader')?.classList.add('boot-loader--done');
            }, remaining);
        });
    </script>

    {{-- Pull-to-refresh: dragging down from the very top of the page reveals
         the same branded spinner, and releasing past the threshold reloads
         the page — the touch-driven equivalent of the boot loader above. --}}
    <div id="pull-refresh" aria-hidden="true">
        <div class="pull-refresh__ring">
            <img src="{{ site_favicon() }}" alt="" class="pull-refresh__logo">
        </div>
    </div>
    <script>
        (function () {
            const el = document.getElementById('pull-refresh');
            if (!el) return;

            const THRESHOLD = 70;
            const MAX_PULL = 110;
            let startY = null;
            let pulling = false;
            let armed = false;

            document.addEventListener('touchstart', (e) => {
                if (window.scrollY <= 0) {
                    startY = e.touches[0].clientY;
                    pulling = true;
                    armed = false;
                }
            }, { passive: true });

            document.addEventListener('touchmove', (e) => {
                if (!pulling || startY === null) return;
                const delta = e.touches[0].clientY - startY;
                if (delta <= 0) { pulling = false; return; }

                e.preventDefault();
                const dist = Math.min(delta, MAX_PULL);
                el.style.transform = `translateY(${(dist / MAX_PULL) * 100 - 100}%)`;
                armed = dist >= THRESHOLD;
                el.classList.toggle('pull-refresh--armed', armed);
            }, { passive: false });

            const reset = () => {
                pulling = false;
                el.style.transform = '';
                el.classList.remove('pull-refresh--armed');
            };

            document.addEventListener('touchend', () => {
                if (!pulling) return;
                if (armed) {
                    el.classList.add('pull-refresh--spinning');
                    el.style.transform = 'translateY(0%)';
                    location.reload();
                } else {
                    reset();
                }
            });
            document.addEventListener('touchcancel', reset);
        })();
    </script>

    @include('partials.announce-bar')
    @include('partials.shell-header')

    <main>
        <div class="mx-auto max-w-none px-4 pt-4 sm:px-6">
            <x-flash />
        </div>
        @yield('content')
    </main>

    @include('partials.shell-footer')
    @include('partials.onboarding')
    @include('partials.welcome-intro')
    @include('partials.feedback-tab')
</body>
</html>
