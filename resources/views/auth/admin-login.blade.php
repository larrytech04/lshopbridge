@extends('layouts.auth')
@section('title', 'Admin sign-in')
@section('heading', __('Admin portal'))
@section('sub', __('Restricted access. Authorized personnel only.'))

@section('content')
<div class="mb-6 flex justify-center">
    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900">
        <x-icon name="shield" class="h-6 w-6" />
    </span>
</div>

<form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="email">{{ __('Admin email') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="field" placeholder="you@example.com">
    </div>
    <div>
        <label class="label" for="password">{{ __('Password') }}</label>
        <input id="password" name="password" type="password" required class="field" placeholder="••••••••">
    </div>
    <label class="flex items-center gap-2 text-sm text-body">
        <input type="checkbox" name="remember" class="rounded border-app surface text-brand-500 focus:ring-brand-400/40">
        {{ __('Remember me') }}
    </label>
    @if ($requireTurnstile ?? true)
        <x-turnstile action="admin-login" />
    @endif
    <button type="submit" class="btn w-full bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
        {{ __('Sign in to admin') }} <x-icon name="arrow-right" class="h-4 w-4" />
    </button>
</form>

<p class="mt-6 text-center text-xs text-faint">
    {{ __('Every sign-in attempt here is logged and monitored.') }}
</p>
@endsection
