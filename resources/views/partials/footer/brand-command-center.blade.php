{{--
    Brand Command Center — the wide, visually-stronger first column of the
    network footer. Only verified/administrator-entered information is shown;
    anything not yet configured in Settings simply doesn't render (see the
    conditional identity rows below) rather than showing a fabricated value.
--}}
@php
    $maintenanceOn = (bool) setting('maintenance_mode', false);
    $foundedYear = setting('company_founded_year');
    $legalName = setting('company_legal_name');
    $tradingName = setting('company_trading_name', setting('site_name', config('platform.name')));
    $hours = cms('cms_footer_support_hours', '');
@endphp
<div class="lg:col-span-4">
    <a href="{{ route('home') }}" class="inline-flex items-center">
        <img src="{{ site_logo() }}" alt="{{ setting('site_name', config('platform.name')) }}" class="h-9 w-auto" />
    </a>
    <p class="mt-1 text-xs font-semibold text-faint">{{ __('Digital Marketplace') }}@if($foundedYear) · {{ __('Est. :year', ['year' => $foundedYear]) }}@endif</p>

    <p class="mt-4 max-w-sm text-sm leading-relaxed text-muted">{{ cms('cms_footer_brand_statement', __('LshopBridge connects African customers with China-focused financial, digital-commerce and logistics services through one secure platform.')) }}</p>

    @if ($maintenanceOn)
        <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-app px-3 py-1.5 text-xs font-semibold">
            <span class="h-2 w-2 shrink-0 rounded-full bg-amber-500"></span>
            <span class="text-amber-600">{{ __('Scheduled Maintenance') }}</span>
        </div>
    @endif

    {{-- Verified identity block — every row is conditional on a real setting existing. --}}
    <dl class="mt-4 space-y-1 text-xs text-muted">
        @if ($legalName)<div class="flex gap-1.5"><dt class="shrink-0 font-semibold text-faint">{{ __('Company') }}:</dt><dd>{{ $legalName }}</dd></div>@endif
        @if ($jurisdiction = setting('company_jurisdiction'))<div class="flex gap-1.5"><dt class="shrink-0 font-semibold text-faint">{{ __('Operating region') }}:</dt><dd>{{ $jurisdiction }}</dd></div>@endif
        @if ($email = setting('support_email', config('platform.support_email')))<div class="flex gap-1.5"><dt class="shrink-0 font-semibold text-faint">{{ __('Support') }}:</dt><dd><a href="mailto:{{ $email }}" class="hover:text-strong">{{ $email }}</a></dd></div>@endif
        @if ($phone = setting('support_phone'))<div class="flex gap-1.5"><dt class="shrink-0 font-semibold text-faint">{{ __('Phone') }}:</dt><dd><a href="tel:{{ $phone }}" class="hover:text-strong">{{ $phone }}</a></dd></div>@endif
        @if ($hours)<div class="flex gap-1.5"><dt class="shrink-0 font-semibold text-faint">{{ __('Hours') }}:</dt><dd>{{ $hours }}</dd></div>@endif
    </dl>
</div>
