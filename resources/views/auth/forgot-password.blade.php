@extends('layouts.auth')
@section('title', 'Reset password')
@section('heading', 'Forgot password?')
@section('sub', "We'll email you a secure reset link")

@section('content')
<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="email">{{ __('Email address') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="field">
    </div>
    <x-turnstile action="password_reset" />
    <button type="submit" class="btn btn-primary w-full">{{ __('Send reset link') }}</button>
</form>
<p class="mt-6 text-center text-sm text-muted">
    <a href="{{ route('login') }}" class="font-semibold text-brand-300 hover:text-brand-200">{{ __('Back to login') }}</a>
</p>
@endsection
