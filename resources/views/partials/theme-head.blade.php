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

{{-- Anti-flash: apply saved accessibility preferences before first paint.
     Site-wide (not just the public pages where the toggle panel lives) so a
     preference set once persists into the dashboard and admin too. --}}
<script>
    (function () {
        try {
            var r = document.documentElement;
            var textSize = localStorage.getItem('pb-a11y-text-size'); // 'lg' | 'xl' | null
            if (textSize === 'lg' || textSize === 'xl') r.classList.add('a11y-text-' + textSize);
            if (localStorage.getItem('pb-a11y-contrast') === '1') r.classList.add('a11y-contrast');
            if (localStorage.getItem('pb-a11y-underline-links') === '1') r.classList.add('a11y-underline-links');
            if (localStorage.getItem('pb-a11y-reduced-motion') === '1') r.classList.add('a11y-reduced-motion');
        } catch (e) {}
    })();
</script>

{{-- Favicon (admin-managed, falls back to the logo) --}}
<link rel="icon" href="{{ site_favicon() }}">
<link rel="apple-touch-icon" href="{{ site_favicon() }}">

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
