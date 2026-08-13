@extends('layouts.app')
@section('page-title', 'Forgot transaction PIN')

@section('content')
<x-page-header :title="__('Forgot your transaction PIN?')" :subtitle="__('Confirm your account password and we\'ll email you a one-time code to reset it.')" />

<a href="{{ route('security.index', ['tab' => 'pin']) }}" class="text-sm text-brand-500 hover:text-brand-600">← {{ __('Back to Security Center') }}</a>

<div class="mt-4 max-w-lg rounded-3xl border border-app p-6">
    <div class="flex items-start gap-3 rounded-2xl bg-amber-500/10 p-4 ring-1 ring-amber-400/30">
        <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
        <p class="text-sm text-amber-800 dark:text-amber-300">
            {{ __('Your transaction PIN authorizes every transfer and withdrawal from your wallet. Keep it safe, never share it with anyone, including our support team, who will never ask you for it.') }}
        </p>
    </div>

    <h3 class="mt-6 font-semibold text-strong">{{ __('Confirm your password') }}</h3>
    <p class="mt-1 text-sm text-muted">{{ __('This proves it\'s really you before we email a reset code, the same way changing your password would.') }}</p>

    <form method="POST" action="{{ route('security.pin.forgot') }}" class="mt-5 space-y-4">
        @csrf
        <div>
            <label class="label">{{ __('Account password') }}</label>
            <input type="password" name="password" required autofocus class="field" placeholder="{{ __('Your password') }}">
            @error('password')
                <p class="mt-1.5 text-sm font-medium text-rose-500">{{ $message }}</p>
            @enderror
        </div>
        <button class="btn btn-primary w-full">{{ __('Send reset code') }}</button>
    </form>
</div>
@endsection
