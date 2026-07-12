@php
    $url = $href ?? route('agents.show', $agent);
    $contactUrl = route('marketplace.show', $agent);
    $positive = $agent->rating > 0 ? round($agent->rating / 5 * 100, 2) : 100;
    $positiveLabel = rtrim(rtrim(number_format($positive, 2), '0'), '.');
    $rates = $agent->relationLoaded('shippingRates') ? $agent->shippingRates->where('is_active', true) : collect();
    $kgRates = $rates->whereNotNull('price_per_kg');
    $fromKg = $kgRates->min('price_per_kg');
    $cur = optional($rates->first())->currency ?? 'USD';
    $leadMin = $rates->min('estimated_days_min');
    $leadMax = $rates->max('estimated_days_max');
@endphp
<div class="flex flex-col gap-4 border-b border-app py-5 last:border-0 md:flex-row md:items-center md:justify-between md:gap-6">
    {{-- Merchant --}}
    <a href="{{ $url }}" class="group flex min-w-0 items-start gap-3 md:w-64 md:shrink-0">
        <span class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand-600 text-sm font-bold text-white">
            @if ($agent->logo_path)<img src="{{ Storage::url($agent->logo_path) }}" class="h-full w-full object-cover" alt="">@else{{ strtoupper(substr($agent->business_name, 0, 2)) }}@endif
            <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 bg-emerald-500" style="border-color: var(--bg);"></span>
        </span>
        <div class="min-w-0">
            <div class="flex items-center gap-1.5">
                <span class="truncate font-semibold text-strong group-hover:text-brand-500">{{ $agent->business_name }}</span>
                @if ($agent->verified_at)<x-verified-tick class="h-4 w-4 shrink-0" />@endif
            </div>
            <p class="mt-0.5 text-xs text-muted">
                <span class="font-semibold text-body">{{ number_format($agent->completed_orders) }}</span> {{ __('orders') }}
                <span class="text-faint">·</span> 👍 <span class="font-semibold text-body">{{ $positiveLabel }}%</span>
                <span class="text-faint">·</span> {{ $agent->reviews_count }} {{ __('reviews') }}
            </p>
        </div>
    </a>

    {{-- Headline rate (or rating) --}}
    <div class="md:w-40 md:shrink-0">
        @if ($fromKg)
            <p class="text-lg font-extrabold text-strong">{{ money($fromKg, $cur) }}<span class="text-xs font-medium text-muted"> /kg</span></p>
            <p class="text-xs text-muted">{{ __('from') }} @if($leadMin) · {{ $leadMin }}–{{ $leadMax }} {{ __('days') }}@endif</p>
        @else
            <p class="flex items-center gap-1 text-lg font-extrabold text-strong"><x-icon name="star" class="h-4 w-4 fill-current text-amber-400" /> {{ number_format((float) $agent->rating, 1) }}</p>
            <p class="text-xs text-muted">{{ __('Top-rated agent') }}</p>
        @endif
    </div>

    {{-- Service / methods --}}
    <div class="min-w-0 md:w-56 md:shrink-0">
        <div class="flex flex-wrap gap-1.5">
            @foreach (($agent->shipping_methods ?? []) as $m)
                <span class="inline-flex items-center gap-1 rounded-md surface-2 px-2 py-0.5 text-[11px] font-semibold text-body ring-1 ring-app"><span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>{{ ucfirst($m) }}</span>
            @endforeach
        </div>
        <p class="mt-1.5 text-xs text-muted">{{ $agent->warehouseCountry?->name ?? 'China' }} · {{ $agent->warehouse_city }}</p>
    </div>

    {{-- Action --}}
    <div class="md:w-32 md:shrink-0 md:text-right">
        <a href="{{ $contactUrl }}" class="btn btn-primary w-full justify-center md:w-auto md:px-6">{{ __('Contact') }}</a>
    </div>
</div>
