{{-- The LshopBridge Brief — compact newsletter module with interest tags, real bot protection, and honest status text. --}}
<div class="footer-newsletter" x-data="{ submitting: false }">
    <p class="footer-eyebrow">{{ cms('cms_footer_newsletter_heading', __('The LshopBridge Brief')) }}</p>
    <p class="mt-1.5 text-xs text-muted">{{ cms('cms_footer_newsletter_description', __('Useful China-shopping updates, service launches, rate notices and important platform information.')) }}</p>

    @if (session('success'))
        <p class="mt-3 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-3 py-2 text-xs font-medium text-emerald-600" role="status">{{ session('success') }}</p>
    @endif
    @if (session('error') || $errors->any())
        <p class="mt-3 rounded-xl border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-xs font-medium text-rose-600" role="alert">{{ session('error') ?: $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('newsletter.subscribe') }}" class="mt-3" @submit="submitting = true">
        @csrf
        <x-honeypot />
        <x-form-timing form-type="newsletter" />

        <label for="footer-newsletter-email" class="sr-only">{{ __('Email address') }}</label>
        <div class="flex gap-2">
            <input id="footer-newsletter-email" type="email" name="email" required autocomplete="email" placeholder="{{ __('you@example.com') }}" class="field text-sm">
            <button type="submit" :disabled="submitting" class="btn btn-primary shrink-0 px-4 text-sm">
                <span x-show="!submitting">{{ __('Subscribe') }}</span>
                <span x-show="submitting" x-cloak>{{ __('Sending…') }}</span>
            </button>
        </div>

        <fieldset class="mt-3">
            <legend class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-faint">{{ __('Interests (optional)') }}</legend>
            <div class="no-scrollbar flex flex-nowrap gap-1.5 overflow-x-auto">
                @foreach (\App\Http\Controllers\Public\NewsletterController::INTERESTS as $key => $label)
                    <label class="footer-interest-chip shrink-0">
                        <input type="checkbox" name="interests[]" value="{{ $key }}" class="footer-interest-chip-input">
                        <span class="whitespace-nowrap">{{ __($label) }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <x-turnstile action="newsletter" />

        <p class="mt-2.5 text-[11px] leading-relaxed text-faint">
            {{ __('By subscribing you agree to our') }} <a href="{{ route('legal.show', 'privacy') }}" class="underline hover:text-body">{{ __('Privacy Policy') }}</a>.
            {{ __('You can unsubscribe from any email we send.') }}
        </p>
    </form>
</div>
