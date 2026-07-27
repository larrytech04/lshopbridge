@extends('layouts.auth')
@section('title', 'Two-factor verification')
@section('heading', 'Enter your code')
@section('sub', 'Verify your identity to finish signing in.')

@section('content')
<div x-data="passkeyChallenge({ optionsUrl: '{{ route('two-factor.passkey.options') }}', verifyUrl: '{{ route('two-factor.passkey.verify') }}', dashboardUrl: '{{ route('dashboard') }}' })">
    @if ($hasTotp)
        <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="code">{{ __('Authentication code') }}</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus class="field text-center text-lg tracking-[0.3em]" placeholder="000000" maxlength="20">
                <p class="mt-2 text-xs text-faint">{{ __('Lost your device? Enter one of your recovery codes instead.') }}</p>
            </div>
            <button type="submit" class="btn btn-primary w-full">{{ __('Verify') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
        </form>
    @endif

    @if ($hasPasskeys)
        @if ($hasTotp)
            <div class="my-4 flex items-center gap-3 text-xs text-faint">
                <div class="h-px flex-1 surface-2"></div>{{ __('or') }}<div class="h-px flex-1 surface-2"></div>
            </div>
        @endif
        <button type="button" @click="verify()" :disabled="busy" class="btn btn-ghost w-full">
            <x-icon name="shield" class="h-4 w-4" />
            <span x-text="busy ? '{{ __('Waiting for your passkey…') }}' : '{{ __('Use a passkey instead') }}'"></span>
        </button>
        <p x-show="error" x-cloak class="mt-2 text-center text-sm text-rose-500" x-text="error"></p>
    @endif
</div>

<form method="POST" action="{{ route('two-factor.cancel') }}" class="mt-4">
    @csrf
    <button type="submit" class="w-full text-center text-sm text-muted hover:text-strong">{{ __('Cancel and start over') }}</button>
</form>
@endsection
