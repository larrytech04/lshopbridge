<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
