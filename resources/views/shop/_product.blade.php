@php
    $from = $product->fromPrice();
    $img = $product->image_path ?? $product->logo_path;
    $save = $from && $from->compare_at_price ? round(100 - ($from->price / $from->compare_at_price * 100)) : null;
    $active = $product->variants->where('is_active', true);
    $min = $active->min('price');
    $max = $active->max('price');
    $priceRange = $min === null ? '—' : ($min == $max ? disp($min) : disp($min).' – '.disp($max));
@endphp
<a href="{{ route('shop.show', $product) }}" class="glass glass-hover group relative flex flex-col rounded-2xl p-5">
    @if ($product->is_best_deal)
        <span class="absolute right-3 top-3 z-10 rounded-lg bg-amber-600 px-2 py-0.5 text-[10px] font-bold text-white shadow">{{ __('BEST DEAL') }}</span>
    @elseif ($save)
        <span class="absolute right-3 top-3 z-10 rounded-lg bg-emerald-500/90 px-2 py-0.5 text-[10px] font-bold text-white">-{{ $save }}%</span>
    @endif

    @if ($img)
        <span class="grid h-20 w-full place-items-center overflow-hidden rounded-xl bg-white ring-1 ring-app">
            <img src="{{ Storage::url($img) }}" class="max-h-16 w-auto object-contain" alt="{{ $product->name }}" loading="lazy">
        </span>
    @endif

    <p class="{{ $img ? 'mt-4' : '' }} text-xs font-medium text-faint">{{ $product->brand ?? $product->category->name }}</p>
    <h3 class="line-clamp-1 font-semibold text-strong group-hover:text-brand-400">{{ $product->name }}</h3>
    <p class="mt-1 line-clamp-2 flex-1 text-sm text-muted">{{ $product->summary }}</p>

    <div class="mt-4 flex items-center justify-between border-t border-app pt-3">
        <div>
            <p class="text-[11px] text-faint">{{ $min == $max ? __('Price') : __('Range') }}</p>
            <p class="text-sm font-bold text-strong">{{ $priceRange }}</p>
        </div>
        <span class="inline-flex items-center gap-1 rounded-xl bg-slate-600/15 px-3 py-1.5 text-sm font-semibold text-brand-400 group-hover:bg-brand-600 group-hover:text-white transition">{{ __('Buy') }} <x-icon name="arrow-right" class="h-3.5 w-3.5" /></span>
    </div>
</a>
