@props(['label', 'value', 'icon' => 'chart', 'img' => null, 'tint' => '#7C5CFC', 'hint' => null, 'prefix' => '', 'suffix' => '', 'counter' => false, 'decimals' => 0, 'solid' => false])

<div @class(['rounded-2xl p-5 transition', 'card-solid border border-app shadow-sm hover:shadow-md' => $solid, 'glass glass-hover' => ! $solid])>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm text-muted">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-strong">
                @if ($counter)
                    <span>{{ $prefix }}</span><span x-data="counter({{ (float) $value }}, 1500, {{ $decimals }})" x-intersect.once="start()" x-text="display">0</span><span>{{ $suffix }}</span>
                @else
                    {{ $prefix }}{{ $value }}{{ $suffix }}
                @endif
            </p>
            @if ($hint)
                <p class="mt-1 text-xs text-faint">{{ $hint }}</p>
            @endif
        </div>
        @if ($img)
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-white shadow-sm" style="background: {{ $tint }}">
                <x-img-icon :name="$img" class="h-5 w-5" />
            </span>
        @else
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-slate-500/15 text-slate-500 ring-1 ring-app">
                <x-icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>
</div>
