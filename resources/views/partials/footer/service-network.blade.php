{{--
    Marketplace + Money — two compact columns instead of one tall stack of
    four clusters. Every link is gated by an actual route (Route::has, same
    pattern as PageController::activeServiceKeys()) or a real, active
    ShopCategory — nothing here is a hardcoded "coming soon" feature list.

    Rendered twice deliberately: a plain always-visible list for desktop
    (.footer-list-desktop, no Alpine involved) and a collapsible accordion
    for mobile (.footer-list-mobile, x-show/x-collapse driven). Trying to
    make ONE Alpine-controlled <ul> serve both — hidden-until-clicked on
    mobile, always-open on desktop via a CSS override — turned out not to be
    reliably beatable with an external !important rule against Alpine's own
    inline style, verified with a real browser probe. Two independent copies
    sidesteps the whole question rather than fighting it.
--}}
@php
    use Illuminate\Support\Facades\Route;

    // Real, active top-level marketplace categories — never a hardcoded list.
    $marketplaceCategories = app(\App\Services\Shop\CategoryNavigationService::class)
        ->visibleTopLevel(region()['iso'] ?? null)
        ->take(5);

    $money = array_filter([
        Route::has('wallet.index') ? ['label' => __('Wallet'), 'href' => route('wallet.index')] : null,
        Route::has('deposit.index') ? ['label' => __('Deposits'), 'href' => route('deposit.index')] : null,
        Route::has('funding.create') ? ['label' => __('China Wallet Funding'), 'href' => route('funding.create')] : null,
        Route::has('agents.index') ? ['label' => __('Verified Shipping Agents'), 'href' => route('agents.index')] : null,
    ]);
@endphp
<div class="lg:col-span-2">
    <div class="footer-list-desktop hidden lg:block">
        @if ($marketplaceCategories->isNotEmpty())
            <div class="footer-section">
                <p class="footer-section-title">{{ __('Marketplace') }}</p>
                <ul class="footer-plain-list">
                    @foreach ($marketplaceCategories as $category)
                        <li><a href="{{ route('shop.category', $category->slug) }}" class="footer-link">{{ __($category->name) }} @if ($category->navigation_badge)<span class="footer-badge">{{ $category->navigation_badge }}</span>@endif</a></li>
                    @endforeach
                    <li><a href="{{ route('shop.index') }}" class="footer-link font-semibold">{{ __('All products') }} →</a></li>
                </ul>
            </div>
        @endif
    </div>

    <div class="footer-list-mobile lg:hidden" x-data="{ open: false }">
        @if ($marketplaceCategories->isNotEmpty())
            <div class="footer-accordion">
                <button type="button" class="footer-accordion-trigger" @click="open = !open" :aria-expanded="open.toString()" aria-controls="footer-acc-marketplace-m">
                    <span>{{ __('Marketplace') }}</span>
                    <x-icon name="chevron-down" class="footer-accordion-chevron h-3.5 w-3.5" ::class="open ? 'rotate-180' : ''" />
                </button>
                <ul id="footer-acc-marketplace-m" class="footer-accordion-panel" x-show="open" x-collapse>
                    @foreach ($marketplaceCategories as $category)
                        <li><a href="{{ route('shop.category', $category->slug) }}" class="footer-link">{{ __($category->name) }} @if ($category->navigation_badge)<span class="footer-badge">{{ $category->navigation_badge }}</span>@endif</a></li>
                    @endforeach
                    <li><a href="{{ route('shop.index') }}" class="footer-link font-semibold">{{ __('All products') }} →</a></li>
                </ul>
            </div>
        @endif
    </div>
</div>

<div class="lg:col-span-2">
    <div class="footer-list-desktop hidden lg:block">
        @if (! empty($money))
            <div class="footer-section">
                <p class="footer-section-title">{{ __('Money') }}</p>
                <ul class="footer-plain-list">
                    @foreach ($money as $item)
                        <li><a href="{{ $item['href'] }}" class="footer-link">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="footer-list-mobile lg:hidden" x-data="{ open: false }">
        @if (! empty($money))
            <div class="footer-accordion">
                <button type="button" class="footer-accordion-trigger" @click="open = !open" :aria-expanded="open.toString()" aria-controls="footer-acc-money-m">
                    <span>{{ __('Money') }}</span>
                    <x-icon name="chevron-down" class="footer-accordion-chevron h-3.5 w-3.5" ::class="open ? 'rotate-180' : ''" />
                </button>
                <ul id="footer-acc-money-m" class="footer-accordion-panel" x-show="open" x-collapse>
                    @foreach ($money as $item)
                        <li><a href="{{ $item['href'] }}" class="footer-link">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
