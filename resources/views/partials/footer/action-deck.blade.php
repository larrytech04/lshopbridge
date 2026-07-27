{{--
    Global Action Deck — pre-footer strip. One strong primary action plus a
    couple of subtle secondary ones, chosen by auth state and role so a
    logged-in customer never sees "Create Account" and a guest never sees
    "View Orders". Heading/description are CMS-editable (Admin -> Page content).
--}}
@php
    $user = auth()->user();
    if (! $user) {
        $deckPrimary = ['label' => __('Create Account'), 'href' => route('register')];
        $deckSecondary = [
            ['label' => __('Explore Marketplace'), 'href' => route('shop.index')],
            ['label' => __('Learn How It Works'), 'href' => route('how-it-works')],
        ];
    } elseif ($user->isAgent()) {
        $deckPrimary = ['label' => __('Open Agent Workspace'), 'href' => route('agent.dashboard')];
        $deckSecondary = [
            ['label' => __('Browse Marketplace'), 'href' => route('shop.index')],
            ['label' => __('Contact Support'), 'href' => route('disputes.index')],
        ];
    } else {
        $deckPrimary = ['label' => __('Deposit Funds'), 'href' => route('deposit.index')];
        $deckSecondary = array_filter([
            \Illuminate\Support\Facades\Route::has('funding.create') ? ['label' => __('Fund China Wallet'), 'href' => route('funding.create')] : null,
            ['label' => __('View Orders'), 'href' => route('shop.orders.index')],
        ]);
    }
@endphp
<div class="border-b border-app bg-transparent">
    <div class="mx-auto flex max-w-none flex-col gap-2 px-4 py-3 sm:gap-3 sm:px-6 sm:py-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-xl">
            <h2 class="text-sm font-extrabold leading-snug text-strong sm:text-base lg:text-lg">{{ cms('cms_footer_deck_heading', __('Move money, shop, learn and ship with one connected platform.')) }}</h2>
            <p class="mt-1 hidden text-xs text-muted sm:block">{{ cms('cms_footer_deck_description', __('One account for wallet funding, China payments, the marketplace and shipping, backed by real support.')) }}</p>
        </div>
        <div class="no-scrollbar flex flex-nowrap items-center gap-1.5 overflow-x-auto sm:flex-wrap sm:gap-2">
            {{-- Primary CTA leads in the DOM so it's visible without scrolling on mobile; visually pushed back to the end from sm: up to match the original reading order. --}}
            <a href="{{ $deckPrimary['href'] }}" class="btn btn-primary shrink-0 !px-3 !py-1.5 text-xs sm:order-last sm:!px-4 sm:!py-1.5 sm:text-xs">{{ $deckPrimary['label'] }}</a>
            @foreach ($deckSecondary as $action)
                <a href="{{ $action['href'] }}" class="btn btn-ghost shrink-0 !px-3 !py-1.5 text-xs sm:!px-4 sm:!py-1.5 sm:text-xs">{{ $action['label'] }}</a>
            @endforeach
        </div>
    </div>
</div>
