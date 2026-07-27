@extends('layouts.app')
@section('page-title', 'Two-factor authentication')

@section('content')
<x-page-header :title="__('Two-factor authentication')" :subtitle="__('Add a one-time code from an authenticator app to your login.')" />

<a href="{{ route('security.index') }}" class="text-sm text-brand-500 hover:text-brand-600">← {{ __('Back to Security Center') }}</a>

    <div class="mt-4 rounded-3xl border border-app p-6">
        <h3 class="font-semibold text-strong">{{ __('Step 1: scan or enter this key') }}</h3>
        <p class="mt-1 text-sm text-muted">{{ __('In your authenticator app (Google Authenticator, Authy, 1Password, etc.), add a new account and enter this key manually, or paste the setup link if your app supports it.') }}</p>

        <div class="mt-4">
            <label class="label text-xs">{{ __('Manual entry key') }}</label>
            <div class="flex items-center gap-2">
                <code class="field select-all font-mono text-sm tracking-wider">{{ implode(' ', str_split($secret, 4)) }}</code>
            </div>
        </div>
        <div class="mt-4">
            <label class="label text-xs">{{ __('Setup link (paste into apps that support it)') }}</label>
            <input class="field font-mono text-xs" value="{{ $uri }}" readonly onclick="this.select()">
        </div>

        <div class="mt-6 border-t border-app pt-5">
            <h3 class="font-semibold text-strong">{{ __('Step 2: confirm the code from your app') }}</h3>
            <form method="POST" action="{{ route('security.two-factor.confirm') }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="label text-xs">{{ __('6-digit code') }}</label>
                    <input name="code" type="text" inputmode="numeric" required autofocus maxlength="10" class="field text-center text-lg tracking-[0.3em]" placeholder="000000">
                </div>
                <button class="btn btn-primary">{{ __('Enable two-factor authentication') }}</button>
            </form>
        </div>
    </div>
@endsection
