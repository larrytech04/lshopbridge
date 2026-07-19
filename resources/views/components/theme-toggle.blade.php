@props(['variant' => 'compact', 'size' => 'md', 'bare' => false])

@php
    // mode => [icon, isAsset]. Light + Extra dark use uploaded assets; Dark/System
    // keep crisp built-in glyphs (no matching moon/monitor asset in the set).
    $modes = [
        'light'  => ['Light-Mode-Dark-Light--Streamline-Ultimate.png', true],
        'dark'   => ['moon', false],
        'night'  => ['Halloween-Grim-Reaper--Streamline-Ultimate.png', true],
        'system' => ['monitor', false],
    ];
    $labels = ['light' => 'Light', 'dark' => 'Dark', 'night' => 'Extra dark', 'system' => 'System'];
    // 'sm' matches the Help icon badge (h-6 w-6 rounded-full); 'md' is the default chrome size.
    $btnSize = $size === 'sm' ? 'h-6 w-6 rounded-full' : 'h-9 w-9 rounded-full';
    $iconSize = $size === 'sm' ? 'h-3.5 w-3.5' : 'h-5 w-5';
@endphp

@if ($variant === 'full')
    <div class="seg" role="group" aria-label="{{ __('Theme') }}">
        @foreach ($modes as $mode => [$icon, $isAsset])
            <button type="button" class="seg-btn" data-mode="{{ $mode }}" title="{{ __($labels[$mode]) }}"
                    onclick="window.PBTheme && window.PBTheme.set('{{ $mode }}')">
                @if ($isAsset)<x-img-icon :name="$icon" class="h-4 w-4" />@else<x-icon :name="$icon" class="h-4 w-4" />@endif
            </button>
        @endforeach
    </div>
@else
    <div x-data="{ open: false }" x-on:open-theme-menu.window="open = true" class="relative"
         @mouseenter="open = true" @mouseleave="open = false">
        <button type="button" @click="open = !open" title="{{ __('Theme') }}"
                {{ $attributes->merge(['class' => "grid $btnSize place-items-center ".($bare ? 'hover:surface-2' : 'border border-app surface')." text-body hover:text-strong transition"]) }}>
            <span class="theme-spin grid place-items-center">
                @foreach ($modes as $mode => [$icon, $isAsset])
                    @if ($isAsset)
                        <x-img-icon :name="$icon" class="{{ $iconSize }} ti ti-{{ $mode }}" />
                    @else
                        <x-icon :name="$icon" class="{{ $iconSize }} ti ti-{{ $mode }}" />
                    @endif
                @endforeach
            </span>
        </button>
        <div x-show="open" x-cloak @click.outside="open = false" x-transition
             class="card-solid absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-app p-1.5 shadow-lg">
            @foreach ($modes as $mode => [$icon, $isAsset])
                <button type="button" @click="window.PBTheme && window.PBTheme.set('{{ $mode }}'); open = false"
                        class="opt opt-{{ $mode }} flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-body hover:surface">
                    @if ($isAsset)<x-img-icon :name="$icon" class="h-4 w-4" />@else<x-icon :name="$icon" class="h-4 w-4" />@endif
                    <span>{{ __($labels[$mode]) }}</span>
                    <x-icon name="check" class="opt-check ml-auto h-4 w-4 text-brand-400" />
                </button>
            @endforeach
        </div>
    </div>
@endif
