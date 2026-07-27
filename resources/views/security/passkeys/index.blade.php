@extends('layouts.app')
@section('page-title', 'Passkeys')

@section('content')
<x-page-header :title="__('Passkeys')" :subtitle="__('Sign in using your device\'s fingerprint, face, or screen lock instead of a code.')" />

<a href="{{ route('security.index') }}" class="text-sm text-brand-500 hover:text-brand-600">← {{ __('Back to Security Center') }}</a>

<div x-data="passkeyManager({ optionsUrl: '{{ route('security.passkeys.register-options') }}', storeUrl: '{{ route('security.passkeys.store') }}' })" class="mt-4 space-y-4">
    <div class="rounded-3xl border border-app p-6">
        <p class="font-semibold text-strong">{{ __('Your passkeys') }}</p>
        <div class="mt-3 divide-y divide-app">
            @forelse ($passkeys as $passkey)
                <div class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-strong">{{ $passkey->name }}</p>
                        <p class="text-xs text-faint">
                            {{ __('Added :time', ['time' => $passkey->created_at->diffForHumans()]) }}
                            @if ($passkey->last_used_at) · {{ __('Last used :time', ['time' => $passkey->last_used_at->diffForHumans()]) }} @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('security.passkeys.destroy', $passkey) }}" onsubmit="return confirm('{{ __('Remove this passkey?') }}')">
                        @csrf @method('DELETE')
                        <button class="text-xs font-semibold text-rose-500 hover:text-rose-600">{{ __('Remove') }}</button>
                    </form>
                </div>
            @empty
                <p class="py-4 text-sm text-faint">{{ __('No passkeys added yet.') }}</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-3xl border border-app p-6">
        <p class="font-semibold text-strong">{{ __('Add a passkey') }}</p>
        <p class="mt-1 text-sm text-muted">{{ __('Give it a name you\'ll recognize, like "iPhone" or "Work laptop".') }}</p>
        <div class="mt-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="label text-xs">{{ __('Name') }}</label>
                <input x-model="name" type="text" maxlength="100" class="field" placeholder="{{ __('e.g. iPhone') }}">
            </div>
            <button type="button" @click="register()" :disabled="busy || !name" class="btn btn-primary">
                <x-icon name="fingerprint" class="h-4 w-4" />
                <span x-text="busy ? '{{ __('Waiting for your device…') }}' : '{{ __('Add passkey') }}'"></span>
            </button>
        </div>
        <p x-show="error" x-cloak class="mt-2 text-sm text-rose-500" x-text="error"></p>
        <p x-show="success" x-cloak class="mt-2 text-sm text-emerald-600" x-text="success"></p>
    </div>
</div>
@endsection
