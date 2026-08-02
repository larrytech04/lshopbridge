{{--
    Support & Legal — compact support links (auth-gated where the route is
    personal, like Security Center) plus a single link into the full Legal
    Center rather than every policy re-listed here (they're already indexed
    there, and in the minimal legal bar at the very bottom of the footer).
--}}
@php
    use Illuminate\Support\Facades\Route;

    $support = array_filter([
        ['label' => __('Help Center'), 'href' => route('public.faqs')],
        Route::has('disputes.index') ? ['label' => __('Create Support Ticket'), 'href' => route('disputes.index')] : null,
        auth()->check() && Route::has('security.index') ? ['label' => __('Security Center'), 'href' => route('security.index')] : null,
        ['label' => __('Contact Us'), 'href' => route('contact')],
        Route::has('legal.index') ? ['label' => __('Legal Center'), 'href' => route('legal.index')] : null,
    ]);

    // Real, configured social profiles only — same settings the sidebar
    // social dock reads (partials/social-dock.blade.php), each with its own
    // brand icon/color, filtered to actually-set ones (unset defaults to '#').
    $socialAll = [
        ['WhatsApp', setting('social_whatsapp', '#'), '#25D366',
            '<path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22c5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.8 14.06c-.24.68-1.4 1.3-1.94 1.38-.5.08-1.13.11-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.8-4.17-4.95-4.36-.14-.19-1.18-1.58-1.18-3.02 0-1.43.75-2.13 1.02-2.42.27-.29.58-.36.78-.36h.56c.18 0 .42-.07.66.5.24.59.82 2.04.89 2.19.07.15.12.32.02.51-.1.19-.15.31-.29.48-.15.17-.31.39-.44.52-.15.15-.3.31-.13.6.17.29.76 1.25 1.63 2.02 1.12 1 2.06 1.31 2.35 1.46.29.15.46.12.63-.07.17-.19.73-.85.92-1.14.19-.29.39-.24.66-.15.27.1 1.71.81 2 .96.29.15.49.22.56.34.07.12.07.68-.17 1.36z"/>'],
        ['X', setting('social_x', '#'), '#000000',
            '<path d="M18.24 2.25h3.31l-7.23 8.26L23.13 21.75h-6.66l-5.21-6.82-5.97 6.82H1.98l7.73-8.84L.87 2.25h6.83l4.71 6.23 5.83-6.23Zm-1.16 17.52h1.83L7.01 4.13H5.05l12.03 15.64Z"/>'],
        ['Instagram', setting('social_instagram', '#'), '#E4405F',
            '<rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.2" cy="6.8" r="1.2"/>'],
        ['Facebook', setting('social_facebook', '#'), '#1877F2',
            '<path d="M14 8.5V6.8c0-.8.2-1.3 1.4-1.3H17V2.6c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.5-4 4.2v1.8H8v3h2.6V22H14v-8.5h2.5l.4-3H14Z"/>'],
        ['TikTok', setting('social_tiktok', '#'), '#010101',
            '<path d="M16.5 2c.3 2.1 1.5 3.7 3.5 4v2.5c-1.3.1-2.5-.3-3.6-1v6.7c0 3.4-2.7 6.1-6 6.1s-6-2.7-6-6.1 2.7-6.1 6-6.1c.3 0 .6 0 .9.1v2.7c-.3-.1-.6-.2-.9-.2a3.4 3.4 0 1 0 3.4 3.4V2h2.7Z"/>'],
        ['Discord', setting('social_discord', '#'), '#5865F2',
            '<path d="M20.32 4.37A19.8 19.8 0 0 0 15.45 3c-.2.38-.45.9-.62 1.31a18.3 18.3 0 0 0-5.66 0C9 3.9 8.74 3.38 8.53 3a19.7 19.7 0 0 0-4.87 1.37C.56 9.04-.28 13.58.14 18.06a19.9 19.9 0 0 0 6.05 3.06c.49-.67.93-1.38 1.3-2.13-.71-.27-1.4-.6-2.04-.99l.5-.4c3.92 1.83 8.15 1.83 12.02 0l.5.4c-.64.39-1.33.72-2.04.99.37.75.8 1.46 1.29 2.13a19.9 19.9 0 0 0 6.06-3.06c.5-5.18-.85-9.68-3.56-13.69zM8.02 15.33c-1.18 0-2.15-1.09-2.15-2.42s.95-2.42 2.15-2.42 2.17 1.09 2.15 2.42c0 1.33-.96 2.42-2.15 2.42zm7.96 0c-1.18 0-2.15-1.09-2.15-2.42s.95-2.42 2.15-2.42 2.17 1.09 2.15 2.42c0 1.33-.95 2.42-2.15 2.42z"/>'],
        ['Telegram', setting('telegram_link', '#'), '#26A5E4',
            '<path d="M21.5 4.5 2.7 11.9c-1 .4-1 1.4.1 1.7l4.7 1.5 1.8 5.7c.2.7 1 .9 1.6.4l2.6-2.3 4.7 3.5c.7.5 1.6.2 1.8-.6l3.4-15.8c.3-1-.7-1.7-1.6-1.4ZM8.2 14.5l9.6-6.1c.4-.3.8.2.4.5l-8 7.4-.3 3-1.4-4.3Z"/>'],
    ];
    $social = collect($socialAll)->filter(fn ($s) => $s[1] !== '#' && filled($s[1]));
@endphp
<div class="lg:col-span-2">
    {{-- Desktop: plain, always-visible, no Alpine (see service-network.blade.php's header comment for why) --}}
    <div class="footer-list-desktop hidden lg:block">
        <div class="footer-section">
            <p class="footer-section-title">{{ __('Support') }}</p>
            <ul class="footer-plain-list">
                @foreach ($support as $item)
                    <li><a href="{{ $item['href'] }}" class="footer-link">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Mobile: collapsible accordion --}}
    <div class="footer-list-mobile lg:hidden" x-data="{ suppOpen: false }">
        <div class="footer-accordion">
            <button type="button" class="footer-accordion-trigger" @click="suppOpen = !suppOpen" :aria-expanded="suppOpen.toString()" aria-controls="footer-acc-support">
                <span>{{ __('Support') }}</span>
                <x-icon name="chevron-down" class="footer-accordion-chevron h-3.5 w-3.5" ::class="suppOpen ? 'rotate-180' : ''" />
            </button>
            <ul id="footer-acc-support" class="footer-accordion-panel" x-show="suppOpen" x-collapse>
                @foreach ($support as $item)
                    <li><a href="{{ $item['href'] }}" class="footer-link">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    @if ($social->isNotEmpty())
        <div class="mt-5">
            <p class="footer-eyebrow">{{ __('Connect with LshopBridge') }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($social as [$label, $href, $color, $svgPath])
                    <a href="{{ $href }}" target="_blank" rel="noopener" title="{{ $label }}" aria-label="{{ $label }}" class="footer-social-link" style="--footer-social-color: {{ $color }}">
                        <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">{!! $svgPath !!}</svg>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
