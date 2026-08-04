{{-- Anti-flash: apply the saved theme before first paint. --}}
<script>
    (function () {
        try {
            var m = localStorage.getItem('pb-theme') || 'system';
            var sysDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var eff = m === 'system' ? (sysDark ? 'dark' : 'light') : m;
            var r = document.documentElement;
            r.classList.remove('dark', 'night');
            if (eff === 'dark') r.classList.add('dark');
            if (eff === 'night') r.classList.add('night');
            r.dataset.theme = m;
        } catch (e) {}
    })();
</script>

{{-- Favicon (admin-managed, falls back to the logo) --}}
<link rel="icon" href="{{ site_favicon() }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

{{-- Installable web app (manifest + iOS home-screen meta) --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#9c0f26">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('platform.name') }}">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {}));
    }
</script>

{{-- Search-engine site verification --}}
@if ($gv = setting('google_site_verification'))<meta name="google-site-verification" content="{{ $gv }}">@endif
@if ($bv = setting('bing_site_verification'))<meta name="msvalidate.01" content="{{ $bv }}">@endif

{{-- Google Analytics (GA4) --}}
@if ($ga = setting('google_analytics_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $ga }}');</script>
@endif

{{-- Live chat / custom head embed (raw, admin-managed) --}}
@if ($chat = setting('livechat_embed'))
    {!! $chat !!}
@endif
