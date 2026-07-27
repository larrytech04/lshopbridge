{{--
    Discover LshopBridge — resource and company links, only routes that
    actually exist.
--}}
@php
    use Illuminate\Support\Facades\Route;

    $discover = array_filter([
        ['label' => __('How It Works'), 'href' => route('how-it-works')],
        Route::has('pages.show') ? ['label' => __('About LshopBridge'), 'href' => route('pages.show', 'about')] : null,
        Route::has('learning.index') ? ['label' => __('Learning Center'), 'href' => route('learning.index')] : null,
        Route::has('public.faqs') ? ['label' => __('FAQs'), 'href' => route('public.faqs')] : null,
        Route::has('referral.create') ? ['label' => __('Become an Agent'), 'href' => route('referral.create')] : null,
    ]);
@endphp
<div class="lg:col-span-2">
    {{-- Desktop: plain, always-visible, no Alpine (see service-network.blade.php's header comment for why) --}}
    <div class="footer-list-desktop hidden lg:block">
        <div class="footer-section">
            <p class="footer-section-title">{{ __('Discover LshopBridge') }}</p>
            <ul class="footer-plain-list">
                @foreach ($discover as $item)
                    <li><a href="{{ $item['href'] }}" class="footer-link">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Mobile: collapsible accordion --}}
    <div class="footer-list-mobile lg:hidden" x-data="{ discOpen: false }">
        <div class="footer-accordion">
            <button type="button" class="footer-accordion-trigger" @click="discOpen = !discOpen" :aria-expanded="discOpen.toString()" aria-controls="footer-acc-discover">
                <span>{{ __('Discover LshopBridge') }}</span>
                <x-icon name="chevron-down" class="footer-accordion-chevron h-3.5 w-3.5" ::class="discOpen ? 'rotate-180' : ''" />
            </button>
            <ul id="footer-acc-discover" class="footer-accordion-panel" x-show="discOpen" x-collapse>
                @foreach ($discover as $item)
                    <li><a href="{{ $item['href'] }}" class="footer-link">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
