<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ locale_dir() }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', setting('site_name', config('platform.name')).' — '.config('platform.tagline'))</title>
    @include('partials.theme-head')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />
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
</body>
</html>
