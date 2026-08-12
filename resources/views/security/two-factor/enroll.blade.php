@extends('layouts.app')
@section('page-title', 'Two-factor authentication')

@section('content')
<x-page-header :title="__('Two-factor authentication')" :subtitle="__('Add a one-time code from an authenticator app to your login.')" />

<a href="{{ route('security.index') }}" class="text-sm text-brand-500 hover:text-brand-600">← {{ __('Back to Security Center') }}</a>

    <div class="mt-4 rounded-3xl border border-app p-6">
        <h3 class="font-semibold text-strong">{{ __('Step 1: scan this code') }}</h3>
        <p class="mt-1 text-sm text-muted">{{ __('Open your authenticator app (Google Authenticator, Authy, 1Password, etc.), add a new account, and scan this. Scanning is safer than typing the key by hand, one mistyped character and the codes will never match.') }}</p>

        <div class="mt-4 flex justify-center">
            <img src="{{ $qrCode }}" alt="{{ __('Two-factor authentication QR code') }}" width="240" height="240" class="rounded-2xl border border-app p-3">
        </div>

        <details class="mt-5">
            <summary class="cursor-pointer text-sm font-semibold text-brand-500 hover:text-brand-600">{{ __("Can't scan it? Enter the key manually") }}</summary>
            <div class="mt-3">
                <label class="label text-xs">{{ __('Manual entry key') }}</label>
                <div class="flex items-center gap-2">
                    <code class="field select-all font-mono text-sm tracking-wider">{{ implode(' ', str_split($secret, 4)) }}</code>
                </div>
                <p class="mt-1 text-xs text-faint">{{ __('Type this exactly, without the spaces — most apps strip them automatically, but not all do.') }}</p>
            </div>
            <div class="mt-4">
                <label class="label text-xs">{{ __('Setup link (paste into apps that support it)') }}</label>
                <input class="field font-mono text-xs" value="{{ $uri }}" readonly onclick="this.select()">
            </div>
        </details>

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
