@php $url = $href ?? route('agents.show', $agent); @endphp
<a href="{{ $url }}" class="glass glass-hover group flex h-full flex-col rounded-2xl p-6">
    <div class="flex items-center gap-4">
        <span class="grid h-12 w-12 place-items-center overflow-hidden rounded-xl bg-brand-600 text-base font-bold text-white">
            @if ($agent->logo_path)<img src="{{ Storage::url($agent->logo_path) }}" class="h-full w-full object-cover" alt="">@else{{ strtoupper(substr($agent->business_name, 0, 2)) }}@endif
        </span>
        <div class="min-w-0">
            <div class="flex items-center gap-1.5">
                <h3 class="truncate font-semibold text-strong group-hover:text-brand-200">{{ $agent->business_name }}</h3>
                @if ($agent->status->value === 'approved')<x-verified-tick class="h-4 w-4 shrink-0" />@endif
            </div>
            <p class="text-xs text-muted">{{ $agent->warehouseCountry?->name ?? 'China' }} · {{ $agent->warehouse_city ?? 'Warehouse' }}</p>
        </div>
    </div>

    <p class="mt-4 line-clamp-2 flex-1 text-sm text-muted">{{ $agent->bio ?: 'Procurement & shipping from China to Africa.' }}</p>

    <div class="mt-4 flex flex-wrap gap-1.5">
        @foreach (($agent->shipping_methods ?? []) as $m)
            <span class="pill surface text-body ring-1 ring-white/10">{{ ucfirst($m) }}</span>
        @endforeach
    </div>

    <div class="mt-4 flex items-center justify-between border-t border-app pt-4">
        <div class="flex items-center gap-1 text-amber-300">
            <x-icon name="star" class="h-4 w-4 fill-current" />
            <span class="text-sm font-semibold text-strong">{{ number_format((float) $agent->rating, 1) }}</span>
            <span class="text-xs text-faint">({{ $agent->reviews_count }})</span>
        </div>
        <span class="text-xs font-semibold text-brand-300">{{ __('View profile') }} →</span>
    </div>
</a>
