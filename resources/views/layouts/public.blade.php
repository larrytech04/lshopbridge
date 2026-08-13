<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        // Backward compatible with every existing @section('title')/
        // @section('meta_description')/@section('og_image') override — an
        // empty yield (page didn't set one) becomes null, so SeoService's
        // own computed defaults apply instead of overwriting them with ''.
        $__titleYield = trim($__env->yieldContent('title'));
        $__descYield = trim($__env->yieldContent('meta_description'));
        $__ogImageYield = trim($__env->yieldContent('og_image'));
        // For pages whose indexability is a runtime decision (e.g. a
        // country page with no real configured content yet) rather than a
        // fixed string — see public/countries/show.blade.php.
        $__robotsYield = trim($__env->yieldContent('robots'));
        // An admin-entered canonical override (e.g. a shipping agent's
        // seo_metadata.canonical_override) — resolved through
        // CanonicalUrlService::fromOverride() by whoever sets this yield,
        // so it's already a full, normalized URL by the time it lands here.
        $__canonicalYield = trim($__env->yieldContent('canonical'));

        $seo = app(\App\Services\Seo\SeoService::class)->build(request(), [
            'title' => $__titleYield !== '' ? $__titleYield : null,
            'description' => $__descYield !== '' ? $__descYield : null,
            'robots' => $__robotsYield !== '' ? $__robotsYield : null,
            'canonical' => $__canonicalYield !== '' ? $__canonicalYield : null,
            // Normalized the same way as every other image URL in this
            // system (see CanonicalUrlService) — a page's own
            // @section('og_image', Storage::url(...)) would otherwise skip
            // the https/host forcing that the computed default already gets.
            'ogImage' => $__ogImageYield !== '' ? app(\App\Services\Seo\CanonicalUrlService::class)->normalize($__ogImageYield) : null,
        ]);
    @endphp
    <x-seo-head :seo="$seo" />
    {{-- A page adds its OWN structured data (beyond whatever's already on
         $seo) by pushing ready-made <script> tags here — see home.blade.php
         for the pattern (StructuredDataBuilder::scriptTag(...) per block). --}}
    @stack('structured-data')
    @include('partials.theme-head')
    {{-- Plus Jakarta Sans is self-hosted (bundled via app.css); no external font host. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="aurora public-shell min-h-screen overflow-x-hidden">

    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg">{{ __('Skip to content') }}</a>

    {{-- Home page gets the fuller 1.8s brand moment; every other public page
         (plain page-to-page navigation) gets a snappier 1.2s. --}}
    @include('partials.boot-loader', ['holdMs' => request()->routeIs('home') ? 1800 : 1200])

    @include('partials.pull-to-refresh')

    @include('partials.announce-bar')
    @include('partials.shell-header')

    <main id="main-content" tabindex="-1">
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
