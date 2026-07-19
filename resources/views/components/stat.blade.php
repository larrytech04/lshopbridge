@props(['label', 'value', 'icon' => 'chart', 'hint' => null, 'prefix' => '', 'suffix' => '', 'counter' => false, 'decimals' => 0])

<div class="glass glass-hover rounded-2xl p-5">
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
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-slate-500/15 text-slate-500 ring-1 ring-app">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</div>
