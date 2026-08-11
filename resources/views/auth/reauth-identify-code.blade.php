@extends('layouts.auth')
@section('title', 'Enter your code · '.config('platform.name'))
@section('heading', __('Check your email'))
@section('sub', __('We sent a 6-character code to :email.', ['email' => $maskedEmail]))

@section('content')
<div x-data="reauthEmail({{ (int) $resendWait }})">
    <form action="{{ route('reauth.identify.code') }}" method="POST" x-ref="form" @submit="submitting = true">
        @csrf
        <input type="hidden" name="code" :value="chars.join('')">

        {{-- 6 individual boxes — mixed letters & numbers, always shown uppercase. --}}
        <div class="flex items-center justify-center gap-2" role="status" aria-label="{{ __('Code entry progress') }}">
            <template x-for="i in 6" :key="i">
                <span class="grid h-12 w-10 place-items-center rounded-xl border-2 text-lg font-bold uppercase tracking-wide text-strong transition-all sm:h-14 sm:w-11"
                      :class="chars.length >= i ? 'border-brand-500' : 'border-app'"
                      x-text="chars[i - 1] || ''"></span>
            </template>
        </div>

        {{-- Real input the user actually types/pastes into — kept visually
             minimal but focusable/selectable, not display:none, so paste and
             mobile autofill (from the Mail app) both work normally. --}}
        <input type="text" inputmode="text" autocomplete="one-time-code" maxlength="6" x-ref="input"
               x-model="raw" @input="onInput()" @paste.prevent="onPaste($event)"
               class="mx-auto mt-4 block w-40 rounded-xl border border-app bg-transparent px-3 py-2 text-center text-sm uppercase tracking-[0.3em] text-strong focus:outline-none focus:ring-2 focus:ring-brand-500"
               placeholder="{{ __('Type or paste code') }}">

        @error('code')
            <p class="mt-3 text-center text-sm font-medium text-rose-500" x-cloak>{{ $message }}</p>
        @enderror

        <button type="submit" class="btn btn-primary mt-6 w-full" :disabled="chars.length !== 6 || submitting">
            {{ __('Confirm') }}
        </button>
    </form>

    <form method="POST" action="{{ route('reauth.identify.resend') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-semibold text-brand-500 hover:text-brand-600 disabled:cursor-not-allowed disabled:text-faint disabled:hover:text-faint"
                :disabled="wait > 0" x-text="wait > 0 ? '{{ __('Resend code in') }} ' + wait + 's' : '{{ __('Resend code') }}'">
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-faint">
        {{ __('Wrong email?') }}
        <a href="{{ route('reauth.identify') }}" class="font-semibold text-brand-500 hover:text-brand-600">{{ __('Start over') }}</a>
    </p>
</div>

@push('scripts')
<script>
    function reauthEmail(initialWait) {
        return {
            raw: '',
            chars: [],
            submitting: false,
            wait: initialWait,
            timer: null,
            init() {
                this.$refs.input.focus();
                if (this.wait > 0) this.startTimer();
            },
            startTimer() {
                clearInterval(this.timer);
                this.timer = setInterval(() => {
                    this.wait = Math.max(0, this.wait - 1);
                    if (this.wait === 0) clearInterval(this.timer);
                }, 1000);
            },
            onInput() {
                const clean = this.raw.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
                this.raw = clean;
                this.chars = clean.split('');
                if (this.chars.length === 6) setTimeout(() => this.$refs.form.requestSubmit(), 150);
            },
            onPaste(e) {
                const text = (e.clipboardData || window.clipboardData).getData('text');
                this.raw = text;
                this.onInput();
            },
        };
    }
</script>
@endpush
@endsection
