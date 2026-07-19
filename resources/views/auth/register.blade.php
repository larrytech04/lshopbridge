@extends('layouts.auth')
@section('title', 'Create account')
@section('heading', 'Create your account')
@section('sub', 'Start funding Alipay & shopping digital products')

@section('content')
@include('partials.auth-social')

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf
    @if ($ref ?? null)<input type="hidden" name="ref" value="{{ $ref }}">@endif
    <div>
        <label class="label" for="name">{{ __('Full name') }}</label>
        <input id="name" name="name" value="{{ old('name') }}" required autofocus class="field" placeholder="{{ __('Jane Doe') }}">
    </div>
    <div>
        <label class="label" for="email">{{ __('Email') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="field" placeholder="you@example.com">
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="label" for="country_id">{{ __('Country') }}</label>
            <select id="country_id" name="country_id" required class="field">
                <option value="">{{ __('Select…') }}</option>
                @foreach ($countries as $c)
                    <option value="{{ $c->id }}" @selected(old('country_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="phone">{{ __('Phone') }}</label>
            <input id="phone" name="phone" value="{{ old('phone') }}" required class="field" placeholder="{{ __('+237 6xx xxx xxx') }}">
        </div>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="label" for="password">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required class="field" placeholder="••••••••">
        </div>
        <div>
            <label class="label" for="password_confirmation">{{ __('Confirm') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="field" placeholder="••••••••">
        </div>
    </div>
    <label class="flex items-start gap-2 text-sm text-body">
        <input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-app surface text-brand-500 focus:ring-brand-400/40">
        <span>{{ __('I agree to the') }} <a href="{{ route('pages.show', 'terms') }}" class="text-brand-400 hover:text-brand-300">{{ __('Terms') }}</a> {{ __('and') }} <a href="{{ route('pages.show', 'privacy') }}" class="text-brand-400 hover:text-brand-300">{{ __('Privacy Policy') }}</a>.</span>
    </label>
    <x-turnstile />
    <button type="submit" class="btn btn-primary w-full">{{ __('Create account') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
</form>

<p class="mt-6 text-center text-sm text-muted">
    {{ __('Already have an account?') }} <a href="{{ route('login') }}" class="font-semibold text-brand-400 hover:text-brand-300">{{ __('Log in') }}</a>
</p>
@endsection
