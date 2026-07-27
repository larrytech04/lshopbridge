@props(['action' => null])
@php
    $turnstile = app(\App\Services\Security\TurnstileVerificationService::class);
    $appearance = setting('turnstile_appearance_mode', 'managed') === 'managed' ? 'always' : 'interaction-only';
@endphp
@if ($turnstile->enabled())
    <div class="cf-turnstile"
         data-sitekey="{{ $turnstile->siteKey() }}"
         data-theme="auto"
         data-appearance="{{ $appearance }}"
         @if ($action) data-action="{{ $action }}" @endif
    ></div>
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
