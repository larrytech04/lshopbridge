@extends('layouts.auth')
@section('title', 'Set new password')
@section('heading', 'Set a new password')

@section('content')
<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label class="label" for="email">{{ __('Email') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="field">
    </div>
    <div>
        <label class="label" for="password">{{ __('New password') }}</label>
        <input id="password" name="password" type="password" required class="field">
    </div>
    <div>
        <label class="label" for="password_confirmation">{{ __('Confirm password') }}</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required class="field">
    </div>
    <button type="submit" class="btn btn-primary w-full">{{ __('Reset password') }}</button>
</form>
@endsection
