@extends('layouts.auth')
@section('title', 'Log in')
@section('heading', 'Welcome back')
@section('sub', 'Sign in to your '.config('platform.name').' account')

@section('content')
@include('partials.auth-social')

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="email">{{ __('Email address') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="field" placeholder="you@example.com">
    </div>
    <div>
        <div class="flex items-center justify-between">
            <label class="label" for="password">{{ __('Password') }}</label>
            <a href="{{ route('password.request') }}" class="text-xs text-brand-400 hover:text-brand-300">{{ __('Forgot?') }}</a>
        </div>
        <input id="password" name="password" type="password" required class="field" placeholder="••••••••">
    </div>
    <label class="flex items-center gap-2 text-sm text-body">
        <input type="checkbox" name="remember" class="rounded border-app surface text-brand-500 focus:ring-brand-400/40">
        {{ __('Remember me') }}
    </label>
    <x-turnstile />
    <button type="submit" class="btn btn-primary w-full">{{ __('Sign in') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
</form>

<p class="mt-6 text-center text-sm text-muted">
    {{ __('New here?') }} <a href="{{ route('register') }}" class="font-semibold text-brand-400 hover:text-brand-300">{{ __('Create an account') }}</a>
</p>
<p class="mt-2 text-center text-xs text-faint">
    {{ __('Want to ship goods?') }} <a href="{{ route('register.agent') }}" class="text-body hover:text-strong">{{ __('Become an agent') }}</a>
</p>
@endsection
