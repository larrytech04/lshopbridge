{{--
    Minimal Legal Bar — copyright + a handful of real links, nothing internal
    (no environment name, build number, or framework version — those belong
    on the admin System Information page, not a public footer).
--}}
<div class="border-t border-app">
    <div class="mx-auto flex max-w-none flex-col items-center gap-2 px-4 py-4 text-xs text-faint sm:flex-row sm:justify-between sm:px-6">
        <span>© {{ date('Y') }} {{ setting('company_legal_name') ?: setting('site_name', config('platform.name')) }}</span>

        <nav class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5" aria-label="{{ __('Legal') }}">
            @if (\Illuminate\Support\Facades\Route::has('legal.index'))
                <a href="{{ route('legal.index') }}" class="hover:text-body">{{ __('Legal Center') }}</a>
                <a href="{{ route('legal.show', 'privacy') }}" class="hover:text-body">{{ __('Privacy Choices') }}</a>
                <a href="{{ route('legal.show', 'cookie-policy') }}" class="hover:text-body">{{ __('Cookie Preferences') }}</a>
            @endif
        </nav>

        @if (config('platform.version'))
            <span class="hidden text-xs sm:inline">v{{ config('platform.version') }}</span>
        @endif
    </div>
</div>
