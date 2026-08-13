{{--
    Renders one SeoData object (see app/Services/Seo/SeoData.php) into every
    <head> tag a public page needs. This is the ONLY place any of these tags
    should be written — a page customizes what it needs by building its
    SeoData through SeoService, never by adding ad-hoc <meta> tags of its
    own in a Blade view.

    Deliberately does NOT render theme-color — that stays owned by
    partials/theme-head.blade.php, which already sets one; duplicating it
    here would just risk two conflicting tags.
--}}
@props(['seo'])

<title>{{ $seo->title }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit($seo->description, 300) }}">
<link rel="canonical" href="{{ $seo->canonical }}">
<meta name="robots" content="{{ $seo->robots }}">

<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:site_name" content="{{ setting('site_name', config('platform.name')) }}">
<meta property="og:title" content="{{ $seo->effectiveOgTitle() }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit($seo->effectiveOgDescription(), 200) }}">
@if ($seo->ogImage)
    <meta property="og:image" content="{{ $seo->ogImage }}">
@endif
<meta property="og:url" content="{{ $seo->canonical }}">
{{-- Approximate — schema.org/Facebook want a language_TERRITORY pair, and
     app locales here are bare 2-letter codes. Good enough for the OG tag's
     actual purpose (a hint, not a ranking signal); revisit if a locale is
     ever added whose obvious territory pairing is genuinely ambiguous. --}}
<meta property="og:locale" content="{{ ['en' => 'en_US', 'fr' => 'fr_FR', 'zh' => 'zh_CN', 'es' => 'es_ES', 'pt' => 'pt_PT'][app()->getLocale()] ?? app()->getLocale() }}">

<meta name="twitter:card" content="{{ $seo->twitterCard }}">
@if ($seo->twitterSite)
    <meta name="twitter:site" content="{{ $seo->twitterSite }}">
@endif
<meta name="twitter:title" content="{{ $seo->effectiveOgTitle() }}">
<meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($seo->effectiveOgDescription(), 200) }}">
@if ($seo->ogImage)
    <meta name="twitter:image" content="{{ $seo->ogImage }}">
@endif

@if ($seo->publishedAt)
    <meta property="article:published_time" content="{{ $seo->publishedAt }}">
@endif
@if ($seo->modifiedAt)
    <meta property="article:modified_time" content="{{ $seo->modifiedAt }}">
@endif

@foreach ($seo->alternates as $alternate)
    <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}">
@endforeach

@foreach ($seo->structuredData as $block)
    {!! \App\Services\Seo\StructuredDataBuilder::scriptTag($block) !!}
@endforeach
