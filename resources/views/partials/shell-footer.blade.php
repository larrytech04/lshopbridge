<footer class="mt-24" style="background: var(--header-bg);">
    <div class="mx-auto grid max-w-none gap-10 px-4 py-14 sm:px-6 lg:grid-cols-6">
        {{-- Brand --}}
        <div class="lg:col-span-2">
            <img src="{{ site_logo() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="h-10 w-auto" />
            <p class="mt-1 text-xs font-semibold text-faint">{{ __('Digital Marketplace') }} · Est. 2026</p>
            <p class="mt-4 max-w-xs text-sm text-muted">{{ cms('cms_footer_tagline', __('Make payments, fund China wallets, and buy instant digital products — gift cards, eSIMs & VPN — delivered in minutes.')) }}</p>
            <div class="mt-5 flex gap-2">
                @foreach (['globe','mail','phone','heart'] as $s)
                    <span class="grid h-9 w-9 place-items-center rounded-xl border border-app surface text-muted"><x-icon :name="$s" class="h-4 w-4" /></span>
                @endforeach
            </div>
            <p class="mt-6 text-xs font-semibold uppercase tracking-wider text-faint">{{ __('Get the app') }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="flex items-center gap-2 rounded-xl bg-ink-900 px-3 py-2 text-white"><x-icon name="apple" class="h-5 w-5" /><span class="text-left leading-none"><span class="block text-[9px]">{{ __('Download on the') }}</span><span class="block text-sm font-semibold">{{ __('App Store') }}</span></span></span>
                <span class="flex items-center gap-2 rounded-xl bg-ink-900 px-3 py-2 text-white"><x-icon name="googleplay" class="h-5 w-5" /><span class="text-left leading-none"><span class="block text-[9px]">{{ __('Get it on') }}</span><span class="block text-sm font-semibold">{{ __('Google Play') }}</span></span></span>
            </div>
        </div>

        @php
        $cols = [
            'Shop' => [['shop.index','All products'],['shop.category','Gift cards','gift-cards'],['shop.category','eSIMs','esims'],['shop.category','Digital Apps','gc-digital-apps']],
            'Money' => [['public.fund','Fund Alipay'],['public.payment-methods','Payment methods'],['public.fees','Fees & rates'],['agents.index','Shipping agents']],
            'Company' => [['how-it-works','How it works'],['guides.index','China academy'],['public.faqs','FAQs'],['contact','Contact']],
            'Legal' => [['pages.show','Terms','terms'],['pages.show','Privacy','privacy'],['pages.show','Refund policy','refund-policy'],['pages.show','About','about']],
        ];
        @endphp
        @foreach ($cols as $title => $items)
            <div>
                <h4 class="text-sm font-semibold text-strong">{{ __($title) }}</h4>
                <ul class="mt-4 space-y-2 text-sm text-muted">
                    @foreach ($items as $item)
                        <li><a href="{{ isset($item[2]) ? (str_contains($item[0],'.show') ? route($item[0], $item[2]) : route($item[0], ['category'=>$item[2]])) : route($item[0]) }}" class="hover:text-strong">{{ __($item[1]) }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    {{-- Accepted payment methods strip --}}
    <div class="border-t border-app">
        <div class="mx-auto max-w-none px-4 py-5 sm:px-6">
            <p class="mb-3 text-center text-[11px] font-semibold uppercase tracking-wider text-faint sm:text-left">{{ __('Accepted payment methods') }}</p>
            <div class="flex items-center justify-center gap-3 sm:justify-start">
                @foreach (['visa', 'mastercard', 'mtn', 'btc', 'usdt'] as $key)
                    <x-pay-icon :name="$key" class="pay-colorless h-9 w-9" />
                @endforeach
            </div>
        </div>
    </div>

    <div class="border-t border-app">
        <div class="mx-auto flex max-w-none flex-col items-center justify-between gap-4 px-4 py-6 text-xs text-faint sm:flex-row sm:px-6">
            <span>© {{ date('Y') }} {{ setting('site_name', config('platform.name')) }}. {{ __('All rights reserved.') }}</span>
            <div class="flex items-center gap-4">
                <x-theme-toggle variant="full" />
                <span class="hidden sm:inline">v2.0.0</span>
            </div>
        </div>
    </div>
</footer>
