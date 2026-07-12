@props([
    'rating' => 5,
    'variant' => 'amber',      // amber (Google-style) | trustpilot (green tiles)
    'size' => 'h-4 w-4',       // star / tile size
    'inner' => 'h-2.5 w-2.5',  // white star size inside a Trustpilot tile
])
@php
    $pct = max(0.0, min(100.0, ((float) $rating) / 5 * 100));
    $d = 'm12 3.6 2.5 5.2 5.6.8-4 3.9 1 5.6L12 16.4 6.9 19.1l1-5.6-4-3.9 5.6-.8Z';
@endphp
<div {{ $attributes->merge(['class' => 'relative inline-flex w-max align-middle']) }}>
    {{-- Empty layer --}}
    <div class="flex gap-0.5">
        @for ($i = 0; $i < 5; $i++)
            @if ($variant === 'trustpilot')
                <span class="grid {{ $size }} shrink-0 place-items-center rounded-[3px]" style="background:#cbd0dd"><svg viewBox="0 0 24 24" fill="#fff" class="{{ $inner }}"><path d="{{ $d }}" /></svg></span>
            @else
                <svg viewBox="0 0 24 24" fill="currentColor" class="{{ $size }} shrink-0 text-slate-300"><path d="{{ $d }}" /></svg>
            @endif
        @endfor
    </div>
    {{-- Filled layer, clipped to the rating --}}
    <div class="absolute left-0 top-0 flex gap-0.5 overflow-hidden" style="width: {{ $pct }}%">
        @for ($i = 0; $i < 5; $i++)
            @if ($variant === 'trustpilot')
                <span class="grid {{ $size }} shrink-0 place-items-center rounded-[3px]" style="background:#00b67a"><svg viewBox="0 0 24 24" fill="#fff" class="{{ $inner }}"><path d="{{ $d }}" /></svg></span>
            @else
                <svg viewBox="0 0 24 24" fill="currentColor" class="{{ $size }} shrink-0 text-amber-400"><path d="{{ $d }}" /></svg>
            @endif
        @endfor
    </div>
</div>
