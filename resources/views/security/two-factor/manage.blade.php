@extends('layouts.app')
@section('page-title', 'Two-factor authentication')

@section('content')
<x-page-header :title="__('Two-factor authentication')" :subtitle="__('Manage the authenticator app protecting your login.')" />

<a href="{{ route('security.index') }}" class="text-sm text-brand-500 hover:text-brand-600">← {{ __('Back to Security Center') }}</a>

@if (session('recovery_codes'))
    <div class="mt-4 rounded-3xl border border-emerald-400/30 bg-emerald-500/10 p-6">
        <h3 class="font-semibold text-strong">{{ __('Save your recovery codes') }}</h3>
        <p class="mt-1 text-sm text-muted">{{ __('Each code can be used once to sign in if you lose access to your authenticator app. Store them somewhere safe, this is the only time they will be shown.') }}</p>
        <div class="mt-4 grid grid-cols-2 gap-2 rounded-2xl surface p-4 font-mono text-sm sm:grid-cols-4">
            @foreach (session('recovery_codes') as $code)
                <span class="text-strong">{{ $code }}</span>
            @endforeach
        </div>
    </div>
@endif

<div class="mt-4 rounded-3xl border border-app p-6">
    <div class="flex items-center gap-3">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-emerald-500/12 text-emerald-600"><x-icon name="shield" class="h-5 w-5" /></span>
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-strong">{{ __('Two-factor authentication is on') }}</p>
            <p class="text-sm text-muted">{{ __('Enabled :time.', ['time' => $user->two_factor_confirmed_at->diffForHumans()]) }}</p>
        </div>
    </div>

    <div class="mt-6 border-t border-app pt-5" x-data="{ show: false }">
        <button type="button" @click="show = !show" class="text-sm font-semibold text-brand-500 hover:text-brand-600">{{ __('Regenerate recovery codes') }}</button>
        <form x-show="show" x-collapse x-cloak method="POST" action="{{ route('security.two-factor.recovery-codes') }}" class="mt-3 flex items-end gap-3">
            @csrf
            <div>
                <label class="label text-xs">{{ __('Current password') }}</label>
                <input type="password" name="password" required class="field">
            </div>
            <button class="btn btn-ghost">{{ __('Regenerate') }}</button>
        </form>
    </div>

    <div class="mt-5 border-t border-app pt-5" x-data="{ show: false }">
        <button type="button" @click="show = !show" class="text-sm font-semibold text-rose-500 hover:text-rose-600">{{ __('Turn off two-factor authentication') }}</button>
        <form x-show="show" x-collapse x-cloak method="POST" action="{{ route('security.two-factor.disable') }}" class="mt-3 flex items-end gap-3" onsubmit="return confirm('{{ __('Turn off two-factor authentication? This makes your account easier to access with just a password.') }}')">
            @csrf @method('DELETE')
            <div>
                <label class="label text-xs">{{ __('Current password') }}</label>
                <input type="password" name="password" required class="field">
            </div>
            <button class="btn btn-danger">{{ __('Turn off') }}</button>
        </form>
    </div>
</div>
@endsection
