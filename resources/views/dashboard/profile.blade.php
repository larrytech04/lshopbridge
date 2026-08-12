@extends('layouts.app')
@section('page-title', 'Settings')

@php
    $prefs = $user->preferences ?? [];
    $pref = fn ($key, $default = true) => array_key_exists($key, $prefs) ? (bool) $prefs[$key] : $default;
@endphp

@section('content')
<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-strong">{{ __('Settings') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('Manage your account, security and preferences.') }}</p>
    </div>

    {{-- Personal information --}}
    <div x-data="{ editing: {{ $errors->any() ? 'true' : 'false' }} }">
        <h2 class="mb-3 text-sm font-semibold text-strong">{{ __('Personal Information') }}</h2>
        <div class="card-solid rounded-3xl border border-app p-6 shadow-sm">
            <div class="flex flex-wrap items-start gap-4">
                <div>
                    <div class="relative h-16 w-16">
                        @if ($user->avatar_path)
                            <img src="{{ Storage::url($user->avatar_path) }}" class="h-16 w-16 rounded-full object-cover ring-2 ring-app" alt="{{ $user->name }}">
                        @else
                            <div class="grid h-16 w-16 place-items-center rounded-full bg-brand-600 text-xl font-bold text-white ring-2 ring-app">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</div>
                        @endif
                        <label class="absolute -bottom-1 -right-1 grid h-7 w-7 cursor-pointer place-items-center rounded-full bg-brand-600 text-white ring-2 ring-app" style="ring-color: var(--bg);">
                            <x-icon name="upload" class="h-3.5 w-3.5" />
                            <input type="file" name="avatar" form="profile-form" accept="image/*" class="sr-only" onchange="this.form.requestSubmit()">
                        </label>
                    </div>
                    <button type="button" @click="editing = true" x-show="! editing" class="btn btn-ghost mt-3 !px-3 !py-1.5 text-xs">{{ __('Edit profile') }}</button>
                    @if ($user->avatar_path)
                        <form method="POST" action="{{ route('profile.photo.remove') }}" class="mt-1">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-rose-500 hover:text-rose-600">{{ __('Remove photo') }}</button>
                        </form>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-lg font-bold text-strong">{{ $user->name }}</span>
                        @if ((int) $user->kyc_level >= 2)
                            <x-verified-tick class="h-5 w-5" />
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-muted">{{ __('Member since :date', ['date' => $user->created_at->format('F Y')]) }}</p>
                </div>
            </div>

            {{-- Read-only view: every field the edit form below covers, so "Edit profile" toggles the same data into an editable state instead of duplicating a separate summary. --}}
            <div x-show="! editing" class="mt-6 divide-y divide-app border-t border-app">
                <div class="py-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Full name') }}</p><p class="mt-1 text-sm text-strong">{{ $user->name }}</p></div>
                <div class="flex items-center justify-between py-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Email address') }}</p>
                        <p class="mt-1 truncate text-sm text-strong">{{ $user->email }}</p>
                    </div>
                    <span class="shrink-0 rounded-full {{ $user->email_verified_at ? 'bg-emerald-500/15 text-emerald-500 ring-emerald-400/30' : 'bg-amber-500/15 text-amber-500 ring-amber-400/30' }} px-2.5 py-1 text-[10px] font-bold uppercase ring-1">{{ $user->email_verified_at ? __('Verified') : __('Unverified') }}</span>
                </div>
                <div class="py-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Phone number') }}</p><p class="mt-1 text-sm text-strong">{{ $user->phone ?: __('Not set') }}</p></div>
                <div class="py-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Gender') }}</p><p class="mt-1 text-sm text-strong">{{ $user->gender ? __(ucfirst($user->gender)) : __('Not set') }}</p></div>
                <div class="py-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Country') }}</p><p class="mt-1 text-sm text-strong">{{ $user->country?->name ?? __('Not set') }}</p></div>
                <div class="py-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('City') }}</p><p class="mt-1 text-sm text-strong">{{ $user->city ?: __('Not set') }}</p></div>
                <div class="py-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Address') }}</p><p class="mt-1 text-sm text-strong">{{ $user->address ?: __('Not set') }}</p></div>
            </div>

            <form id="profile-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" x-show="editing" x-cloak class="mt-6 divide-y divide-app border-t border-app">
                @csrf @method('PUT')
                <div class="py-3">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Full name') }}</label>
                    <input name="name" value="{{ old('name', $user->name) }}" required class="w-full bg-transparent text-sm text-strong focus:outline-none">
                </div>
                <div class="flex items-center justify-between py-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Email address') }}</p>
                        <p class="truncate text-sm text-strong">{{ $user->email }}</p>
                    </div>
                    <span class="shrink-0 rounded-full {{ $user->email_verified_at ? 'bg-emerald-500/15 text-emerald-500 ring-emerald-400/30' : 'bg-amber-500/15 text-amber-500 ring-amber-400/30' }} px-2.5 py-1 text-[10px] font-bold uppercase ring-1">{{ $user->email_verified_at ? __('Verified') : __('Unverified') }}</span>
                </div>
                <div class="py-3">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Phone number') }}</label>
                    <input name="phone" value="{{ old('phone', $user->phone) }}" placeholder="{{ __('Not set') }}" required class="w-full bg-transparent text-sm text-strong placeholder:italic placeholder:text-faint focus:outline-none">
                </div>
                <div class="py-3">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Gender') }}</label>
                    <select name="gender" class="w-full bg-transparent text-sm text-strong focus:outline-none">
                        <option value="" @selected(! $user->gender)>{{ __('Not set') }}</option>
                        <option value="male" @selected($user->gender === 'male')>{{ __('Male') }}</option>
                        <option value="female" @selected($user->gender === 'female')>{{ __('Female') }}</option>
                        <option value="other" @selected($user->gender === 'other')>{{ __('Other') }}</option>
                    </select>
                </div>
                <div class="py-3">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Country') }}</label>
                    <select name="country_id" required class="w-full bg-transparent text-sm text-strong focus:outline-none">
                        @foreach ($countries as $c)<option value="{{ $c->id }}" @selected($user->country_id == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="py-3">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('City') }}</label>
                    <input name="city" value="{{ old('city', $user->city) }}" placeholder="{{ __('Not set') }}" class="w-full bg-transparent text-sm text-strong placeholder:italic placeholder:text-faint focus:outline-none">
                </div>
                <div class="py-3">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Address') }}</label>
                    <input name="address" value="{{ old('address', $user->address) }}" placeholder="{{ __('Not set') }}" class="w-full bg-transparent text-sm text-strong placeholder:italic placeholder:text-faint focus:outline-none">
                </div>
                <div class="flex items-center gap-2 pt-4">
                    <button class="btn btn-primary">{{ __('Save changes') }}</button>
                    <button type="button" @click="editing = false" class="btn btn-ghost">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Security --}}
    <div>
        <div class="card-solid divide-y divide-app rounded-3xl border border-app shadow-sm">
            <button type="button" onclick="document.getElementById('password-form').classList.toggle('hidden')" class="flex w-full items-center gap-3 px-5 py-4 text-left hover:surface">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-900 text-white"><x-icon name="lock" class="h-4 w-4" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-strong">{{ __('Password') }}</span>
                    <span class="block text-xs text-muted">{{ __('Change your account password') }}</span>
                </span>
                <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-faint" />
            </button>
            <div id="password-form" class="hidden px-5 pb-5">
                <form method="POST" action="{{ route('profile.password') }}" class="grid gap-3 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <div class="sm:col-span-2"><label class="label">{{ __('Current password') }}</label><input type="password" name="current_password" required class="field"></div>
                    <div><label class="label">{{ __('New password') }}</label><input type="password" name="password" required class="field"></div>
                    <div><label class="label">{{ __('Confirm') }}</label><input type="password" name="password_confirmation" required class="field"></div>
                    <div class="sm:col-span-2"><button class="btn btn-primary">{{ __('Update password') }}</button></div>
                </form>
            </div>

            <div class="flex items-center gap-3 px-5 py-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white ring-1 ring-app">
                    <svg viewBox="0 0 24 24" class="h-4 w-4"><path fill="#4285F4" d="M23.5 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.55-5.17 3.55-8.66Z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.94-2.92l-3.88-3c-1.08.72-2.45 1.15-4.06 1.15-3.13 0-5.78-2.11-6.73-4.96H1.27v3.1A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.27 14.27a7.2 7.2 0 0 1 0-4.54v-3.1H1.27a12 12 0 0 0 0 10.74l4-3.1Z"/><path fill="#EA4335" d="M12 4.75c1.76 0 3.34.6 4.58 1.79l3.44-3.44C17.94 1.19 15.24 0 12 0A12 12 0 0 0 1.27 6.63l4 3.1C6.22 6.86 8.87 4.75 12 4.75Z"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-strong">{{ __('Google account') }}</span>
                    <span class="block truncate text-xs text-muted">{{ $user->google_id ? __('Connected to :email', ['email' => $user->email]) : __('Not connected') }}</span>
                </span>
                @if ($user->google_id)
                    <span class="shrink-0 inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-1 text-[10px] font-bold uppercase text-emerald-500 ring-1 ring-emerald-400/30"><x-icon name="check" class="h-3 w-3" /> {{ __('Connected') }}</span>
                @else
                    <a href="{{ route('google.redirect') }}" class="shrink-0 text-xs font-semibold text-brand-500 hover:text-brand-600">{{ __('Connect') }}</a>
                @endif
            </div>
        </div>
        <p class="mt-2 flex items-center gap-1.5 text-xs text-faint">
            <x-icon name="clock" class="h-3.5 w-3.5" />
            {{ $user->last_login_at ? __('Last active :time on this device', ['time' => $user->last_login_at->diffForHumans()]) : __('No login activity recorded yet') }}
        </p>
    </div>

    {{-- Notifications --}}
    <div>
        <h2 class="mb-3 text-sm font-semibold text-strong">{{ __('Notifications') }}</h2>
        <form method="POST" action="{{ route('profile.preferences') }}" class="card-solid divide-y divide-app rounded-3xl border border-app shadow-sm" x-data>
            @csrf @method('PUT')
            @foreach ([
                ['key' => 'notify_web_push', 'icon' => 'bell', 'title' => __('Web Push'), 'desc' => __('Push/banner notifications on this device')],
                ['key' => 'notify_order_updates', 'icon' => 'bag', 'title' => __('Order updates'), 'desc' => __('Delivery, fulfillment and refunds')],
                ['key' => 'notify_wallet_activity', 'icon' => 'card', 'title' => __('Wallet activity'), 'desc' => __('Funding, credits and debits')],
                ['key' => 'notify_security_alerts', 'icon' => 'check-circle', 'title' => __('Security alerts'), 'desc' => __('Login attempts and account changes')],
                ['key' => 'notify_promotions', 'icon' => 'giftcard', 'title' => __('Promotions'), 'desc' => __('Deals, offers and new arrivals')],
                ['key' => 'notify_email', 'icon' => 'mail', 'title' => __('Email notifications'), 'desc' => __('Master switch for all emails')],
            ] as $row)
                <div class="flex items-center gap-3 px-5 py-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-900 text-white"><x-icon :name="$row['icon']" class="h-4 w-4" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-strong">{{ $row['title'] }}</span>
                        <span class="block text-xs text-muted">{{ $row['desc'] }}</span>
                    </span>
                    <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                        {{-- The web-push row is owned by initWebPush() in app.js instead of the
                             generic onchange, since turning it on has to request browser
                             permission and subscribe (async) BEFORE this form submits, not after. --}}
                        <input type="checkbox" name="{{ $row['key'] }}" value="1" class="peer sr-only" @checked($pref($row['key']))
                            @if ($row['key'] === 'notify_web_push') data-webpush-toggle @else onchange="this.form.requestSubmit()" @endif>
                        <span class="peer h-6 w-11 rounded-full surface-2 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:bg-brand-600 peer-checked:after:translate-x-5"></span>
                    </label>
                </div>
            @endforeach
        </form>
        <p class="mt-2 text-xs text-faint">{{ __('Changes save automatically.') }}</p>
    </div>

    {{-- Keyboard shortcuts --}}
    <div>
        <h2 class="mb-3 text-sm font-semibold text-strong">{{ __('Keyboard shortcuts') }}</h2>
        <div class="card-solid rounded-3xl border border-app p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm text-muted">{{ __('Ctrl/Cmd+K opens the command palette, ? shows the full list.') }}</p>
                <button type="button" @click="window.dispatchEvent(new CustomEvent('open-shortcuts-help'))" class="btn btn-ghost shrink-0 !px-3 !py-1.5 text-xs">{{ __('View all') }}</button>
            </div>
            <form method="POST" action="{{ route('profile.shortcuts') }}" class="mt-4 flex items-center justify-between gap-4 rounded-2xl border border-app p-4">
                @csrf @method('PUT')
                <p class="text-sm font-semibold text-strong">{{ __('Enable keyboard shortcuts') }}</p>
                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                    <input type="checkbox" name="shortcuts_enabled" value="1" class="peer sr-only" @checked($user->shortcuts_enabled) onchange="this.form.requestSubmit()">
                    <span class="peer h-6 w-11 rounded-full surface-2 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:bg-brand-600 peer-checked:after:translate-x-5"></span>
                </label>
            </form>
            <form method="POST" action="{{ route('profile.shortcuts.reset') }}" class="mt-3">
                @csrf
                <button type="submit" class="text-sm font-semibold text-brand-500 hover:text-brand-600">{{ __('Restore default shortcuts') }}</button>
            </form>
        </div>
    </div>

    {{-- Danger zone --}}
    <div>
        <h2 class="mb-3 text-sm font-semibold text-rose-500">{{ __('Danger zone') }}</h2>
        <div class="card-solid divide-y divide-app rounded-3xl border border-app shadow-sm">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 px-5 py-4 text-left hover:surface">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-900 text-white"><x-icon name="logout" class="h-4 w-4" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-strong">{{ __('Log out') }}</span>
                        <span class="block text-xs text-muted">{{ __('End your session on this device') }}</span>
                    </span>
                    <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-faint" />
                </button>
            </form>
            <div class="px-5 py-4" x-data="{ open: false }">
                <div class="flex items-center justify-between gap-3">
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-strong">{{ __('Delete account') }}</span>
                        <span class="block text-xs text-muted">{{ __('Permanent and cannot be undone') }}</span>
                    </span>
                    <button type="button" @click="open = true" class="shrink-0 text-sm font-semibold text-rose-500 hover:text-rose-600">{{ __('Delete account') }}</button>
                </div>
                <div x-show="open" x-cloak x-transition class="mt-4 rounded-2xl border border-rose-400/30 bg-rose-500/5 p-4">
                    <p class="text-sm text-body">{{ __('This closes your account immediately and logs you out. Enter your password to confirm.') }}</p>
                    <form method="POST" action="{{ route('profile.delete') }}" class="mt-3 flex flex-wrap items-center gap-2">
                        @csrf @method('DELETE')
                        <input type="password" name="current_password" required placeholder="{{ __('Current password') }}" class="field !w-56">
                        <button type="submit" class="btn !bg-rose-600 !text-white hover:!bg-rose-700">{{ __('Confirm deletion') }}</button>
                        <button type="button" @click="open = false" class="btn btn-ghost">{{ __('Cancel') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
