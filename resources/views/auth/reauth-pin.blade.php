@extends('layouts.auth')
@section('title', 'Confirm it\'s you · '.config('platform.name'))
@section('heading', __('Welcome back'))
@section('sub', __('You stepped away for a bit — enter your PIN to continue.'))

@section('content')
<div x-data="reauthPin()">
    <form action="{{ route('reauth.pin') }}" method="POST" x-ref="form" @submit="submitting = true">
        @csrf
        <input type="hidden" name="pin" :value="digits.join('')">

        {{-- 4 dot indicators, filling in as digits are entered --}}
        <div class="flex items-center justify-center gap-4" role="status" aria-label="{{ __('PIN entry progress') }}">
            <template x-for="i in 4" :key="i">
                <span class="h-4 w-4 rounded-full border-2 transition-all duration-150"
                      :class="digits.length >= i ? 'scale-110 border-brand-500 bg-brand-500' : 'border-app'"></span>
            </template>
        </div>

        @error('pin')
            <p class="mt-4 text-center text-sm font-medium text-rose-500" x-cloak>{{ $message }}</p>
        @enderror

        {{-- On-screen numeric dialer — works identically by click or tap, no
             reliance on the device's own keyboard popping up. --}}
        <div class="mx-auto mt-8 grid max-w-[17rem] grid-cols-3 gap-3">
            <template x-for="n in [1,2,3,4,5,6,7,8,9]" :key="n">
                <button type="button" @click="press(n)"
                        class="grid h-16 w-16 place-items-center rounded-full surface-2 text-xl font-semibold text-strong transition hover:surface active:scale-95">
                    <span x-text="n"></span>
                </button>
            </template>
            <div></div>
            <button type="button" @click="press(0)"
                    class="grid h-16 w-16 place-items-center rounded-full surface-2 text-xl font-semibold text-strong transition hover:surface active:scale-95">
                <span>0</span>
            </button>
            <button type="button" @click="backspace()" aria-label="{{ __('Delete') }}"
                    class="grid h-16 w-16 place-items-center rounded-full text-muted transition hover:surface-2 active:scale-95">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>
    </form>

    @if ($hasPasskey)
        {{-- A registered passkey (Face ID / fingerprint / device unlock) skips
             typing the PIN entirely — same underlying flow as the login challenge. --}}
        <div class="mt-6" x-data="passkeyChallenge({
                optionsUrl: '{{ route('reauth.pin.passkey.options') }}',
                verifyUrl: '{{ route('reauth.pin.passkey.verify') }}',
                dashboardUrl: '{{ route('reauth.email') }}',
            })">
            <div class="relative flex items-center gap-3 text-xs text-faint">
                <span class="h-px flex-1 bg-app"></span>{{ __('or') }}<span class="h-px flex-1 bg-app"></span>
            </div>
            <button type="button" @click="verify()" :disabled="busy"
                    class="btn btn-ghost mt-4 w-full gap-2">
                <x-icon name="fingerprint" class="h-4 w-4" />
                <span x-text="busy ? '{{ __('Waiting for your device…') }}' : '{{ __('Use Face ID / Fingerprint') }}'"></span>
            </button>
            <p x-show="error" x-cloak x-text="error" class="mt-2 text-center text-sm font-medium text-rose-500"></p>
        </div>
    @endif

    <p class="mt-8 text-center text-xs text-faint">
        {{ __('Not you?') }}
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="font-semibold text-brand-500 hover:text-brand-600">{{ __('Log out') }}</button>
        </form>
    </p>
</div>

@push('scripts')
<script>
    function reauthPin() {
        return {
            digits: [],
            submitting: false,
            init() {
                window.addEventListener('keydown', (e) => {
                    if (this.submitting) return;
                    if (/^[0-9]$/.test(e.key)) this.press(parseInt(e.key, 10));
                    else if (e.key === 'Backspace') this.backspace();
                });
            },
            press(n) {
                if (this.submitting || this.digits.length >= 4) return;
                this.digits.push(n);
                if (this.digits.length === 4) {
                    setTimeout(() => this.$refs.form.requestSubmit(), 150);
                }
            },
            backspace() {
                if (this.submitting) return;
                this.digits.pop();
            },
        };
    }
</script>
@endpush
@endsection
