@php $turnstile = app(\App\Services\Security\Turnstile::class); @endphp
@if ($turnstile->enabled())
    <div class="cf-turnstile" data-sitekey="{{ $turnstile->siteKey() }}" data-theme="auto"></div>
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
