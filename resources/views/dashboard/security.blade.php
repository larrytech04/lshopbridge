@extends('layouts.app')
@section('page-title', 'Security Center')

@section('content')
<x-page-header :title="__('Security Center')" :subtitle="__('Manage your passwords, PINs, and connected devices.')" />

<div x-data="{ tab: 'password' }">

    {{-- Tabs --}}
    <div class="flex items-center gap-1 rounded-2xl border border-app p-1.5">
        <button type="button" @click="tab = 'password'" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition" :class="tab === 'password' ? 'surface-2 text-strong' : 'text-muted hover:text-strong'">{{ __('Password') }}</button>
        <button type="button" @click="tab = 'pin'" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition" :class="tab === 'pin' ? 'surface-2 text-strong' : 'text-muted hover:text-strong'">{{ __('Transaction PIN') }}</button>
        <button type="button" @click="tab = 'devices'" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition" :class="tab === 'devices' ? 'surface-2 text-strong' : 'text-muted hover:text-strong'">{{ __('Preferences & Devices') }}</button>
    </div>

    {{-- Password --}}
    <div x-show="tab === 'password'" x-cloak class="mt-4 rounded-3xl border border-app p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold text-strong">{{ __('Change password') }}</h3>
                <p class="mt-1 text-sm text-muted">{{ __('You\'ll stay signed in on this device after updating.') }}</p>
            </div>
            <form method="POST" action="{{ route('security.forgot-password') }}" class="shrink-0" onsubmit="return confirm('{{ __('This will sign you out so you can reset your password by email. Continue?') }}')">
                @csrf
                <button type="submit" class="text-sm font-semibold text-brand-500 hover:text-brand-600">{{ __('Forgot password?') }}</button>
            </form>
        </div>
        <form method="POST" action="{{ route('profile.password') }}" class="mt-5 space-y-4">
            @csrf @method('PUT')
            <div x-data="{ show: false }">
                <label class="label">{{ __('Current password') }}</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="current_password" required class="field pr-11" placeholder="{{ __('Enter your current password') }}">
                    <button type="button" @click="show = !show" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-faint hover:text-strong">
                        <x-icon name="eye" class="h-4 w-4" x-show="!show" />
                        <x-icon name="eye-off" class="h-4 w-4" x-show="show" x-cloak />
                    </button>
                </div>
            </div>
            <div x-data="{ show: false }">
                <label class="label">{{ __('New password') }}</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password" required class="field pr-11" placeholder="{{ __('At least 8 characters') }}">
                    <button type="button" @click="show = !show" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-faint hover:text-strong">
                        <x-icon name="eye" class="h-4 w-4" x-show="!show" />
                        <x-icon name="eye-off" class="h-4 w-4" x-show="show" x-cloak />
                    </button>
                </div>
            </div>
            <div x-data="{ show: false }">
                <label class="label">{{ __('Confirm new password') }}</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password_confirmation" required class="field pr-11" placeholder="{{ __('Re-enter your new password') }}">
                    <button type="button" @click="show = !show" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-faint hover:text-strong">
                        <x-icon name="eye" class="h-4 w-4" x-show="!show" />
                        <x-icon name="eye-off" class="h-4 w-4" x-show="show" x-cloak />
                    </button>
                </div>
            </div>
            <button class="btn btn-primary">{{ __('Update password') }}</button>
        </form>
    </div>

    {{-- Transaction PIN --}}
    <div x-show="tab === 'pin'" x-cloak class="mt-4 rounded-3xl border border-app p-6">
        <h3 class="font-semibold text-strong">{{ $user->hasTransactionPin() ? __('Change transaction PIN') : __('Set a transaction PIN') }}</h3>
        <p class="mt-1 text-sm text-muted">{{ __('A 4-digit code you\'ll enter to authorize transfers and withdrawals, separate from your login password.') }}</p>
        <form method="POST" action="{{ route('security.pin') }}" class="mt-5 space-y-4">
            @csrf @method('PUT')
            @if ($user->hasTransactionPin())
                <div>
                    <label class="label">{{ __('Current PIN') }}</label>
                    <input type="password" inputmode="numeric" name="current_pin" maxlength="4" required class="field max-w-[200px]" placeholder="••••">
                </div>
            @endif
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">{{ __('New PIN') }}</label>
                    <input type="password" inputmode="numeric" name="pin" maxlength="4" required class="field" placeholder="{{ __('4 digits') }}">
                </div>
                <div>
                    <label class="label">{{ __('Confirm PIN') }}</label>
                    <input type="password" inputmode="numeric" name="pin_confirmation" maxlength="4" required class="field" placeholder="{{ __('Re-enter PIN') }}">
                </div>
            </div>
            <button class="btn btn-primary">{{ __('Save PIN') }}</button>
        </form>
        @if ($user->hasTransactionPin())
            <p class="mt-4 flex items-center gap-1.5 text-xs text-faint"><x-icon name="check-circle" class="h-3.5 w-3.5 text-emerald-500" /> {{ __('PIN last set :time.', ['time' => $user->transaction_pin_set_at->diffForHumans()]) }}</p>
        @endif
    </div>

    {{-- Preferences & Devices --}}
    <div x-show="tab === 'devices'" x-cloak class="mt-4 space-y-4">
        <div class="rounded-3xl border border-app p-6">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $user->hasMfaEnabled() ? 'bg-emerald-500/12 text-emerald-600' : 'bg-slate-500/12 text-slate-500' }}"><x-icon name="shield" class="h-5 w-5" /></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-strong">{{ __('Two-factor authentication') }}</p>
                    <p class="text-sm text-muted">{{ __('Require a one-time code in addition to your password.') }}</p>
                </div>
                @if ($user->hasMfaEnabled())
                    <span class="pill shrink-0 bg-emerald-500/15 text-[10px] font-bold uppercase text-emerald-600 ring-1 ring-emerald-400/30">{{ __('On') }}</span>
                @else
                    <span class="pill shrink-0 bg-slate-400/15 text-[10px] font-bold uppercase text-slate-500 ring-1 ring-slate-400/30">{{ __('Off') }}</span>
                @endif
            </div>
            <a href="{{ route('security.two-factor.show') }}" class="btn btn-ghost mt-3">{{ $user->hasMfaEnabled() ? __('Manage') : __('Set up two-factor authentication') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>

        <div class="rounded-3xl border border-app p-6">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $user->hasPasskeys() ? 'bg-emerald-500/12 text-emerald-600' : 'bg-slate-500/12 text-slate-500' }}"><x-icon name="fingerprint" class="h-5 w-5" /></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-strong">{{ __('Passkeys') }}</p>
                    <p class="text-sm text-muted">{{ __('Sign in with your device\'s screen lock instead of a code.') }}</p>
                </div>
                @if ($user->hasPasskeys())
                    <span class="pill shrink-0 bg-emerald-500/15 text-[10px] font-bold uppercase text-emerald-600 ring-1 ring-emerald-400/30">{{ __('On') }}</span>
                @else
                    <span class="pill shrink-0 bg-slate-400/15 text-[10px] font-bold uppercase text-slate-500 ring-1 ring-slate-400/30">{{ __('Off') }}</span>
                @endif
            </div>
            <a href="{{ route('security.passkeys.index') }}" class="btn btn-ghost mt-3">{{ $user->hasPasskeys() ? __('Manage') : __('Add a passkey') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>

        <div class="rounded-3xl border border-app p-6">
            <p class="font-semibold text-strong">{{ __('Notification preferences') }}</p>
            <p class="mt-1 text-sm text-muted">{{ __('Web push, order updates, wallet activity, security alerts and more.') }}</p>
            <a href="{{ route('profile.edit') }}#notifications" class="btn btn-ghost mt-3">{{ __('Manage in Settings') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>

        <div class="rounded-3xl border border-app p-6">
            <div class="flex items-center justify-between gap-3">
                <p class="font-semibold text-strong">{{ __('Active sessions') }}</p>
                @if ($sessions->count() > 1)
                    <form method="POST" action="{{ route('security.sessions.revoke-others') }}" onsubmit="return confirm('{{ __('Sign out of every other session?') }}')">
                        @csrf @method('DELETE')
                        <button class="text-xs font-semibold text-rose-500 hover:text-rose-600">{{ __('Sign out all other sessions') }}</button>
                    </form>
                @endif
            </div>
            <div class="mt-3 divide-y divide-app">
                @forelse ($sessions as $s)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="flex items-center gap-2 text-sm font-semibold text-strong">
                                {{ $s->device }}
                                @if ($s->is_current)<span class="pill bg-emerald-500/15 text-[10px] font-bold uppercase text-emerald-600 ring-1 ring-emerald-400/30">{{ __('This device') }}</span>@endif
                            </p>
                            <p class="text-xs text-faint">{{ $s->ip }} · {{ __('Active :time', ['time' => $s->last_active->diffForHumans()]) }}</p>
                        </div>
                        @unless ($s->is_current)
                            <form method="POST" action="{{ route('security.sessions.revoke', $s->id) }}" onsubmit="return confirm('{{ __('Sign out this session?') }}')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-rose-500 hover:text-rose-600">{{ __('Sign out') }}</button>
                            </form>
                        @endunless
                    </div>
                @empty
                    <p class="py-4 text-sm text-faint">{{ __('No active sessions found.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-3xl border border-app p-6">
            <p class="font-semibold text-strong">{{ __('Recent logins') }}</p>
            <div class="mt-3 divide-y divide-app">
                @forelse ($recentLogins as $login)
                    <div class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <div class="flex items-center gap-2 text-muted">
                            @if ($login->successful)
                                <x-icon name="check-circle" class="h-4 w-4 shrink-0 text-emerald-500" />
                            @else
                                <x-icon name="alert" class="h-4 w-4 shrink-0 text-rose-500" />
                            @endif
                            <span>{{ $login->successful ? __('Successful login') : __('Failed login attempt') }}</span>
                            @if ($login->country)<span class="text-faint">· {{ $login->country }}</span>@endif
                            @if ($login->was_new_device)<span class="pill bg-amber-500/15 text-[10px] font-bold uppercase text-amber-600 ring-1 ring-amber-400/30">{{ __('New device') }}</span>@endif
                            @if ($login->was_new_country)<span class="pill bg-amber-500/15 text-[10px] font-bold uppercase text-amber-600 ring-1 ring-amber-400/30">{{ __('New country') }}</span>@endif
                        </div>
                        <span class="text-xs text-faint">{{ $login->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="py-4 text-sm text-faint">{{ __('No login activity recorded yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
