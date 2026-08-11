@extends('layouts.auth')
@section('title', 'Welcome back · '.config('platform.name'))
@section('heading', __('Welcome back'))
@section('sub', __("You were away a while. Confirm your email and we'll send you a code, no password needed."))

@section('content')
<form method="POST" action="{{ route('reauth.identify') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="email">{{ __('Email address') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="field" placeholder="you@example.com">
        @error('email')
            <p class="mt-1.5 text-xs font-medium text-rose-500">{{ $message }}</p>
        @enderror
    </div>
    <button type="submit" class="btn btn-primary w-full">{{ __('Send me a code') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
</form>

<p class="mt-6 text-center text-xs text-faint">
    {{ __('Prefer your password instead?') }}
    <a href="{{ route('login') }}" class="font-semibold text-brand-500 hover:text-brand-600">{{ __('Sign in that way') }}</a>
</p>
@endsection
