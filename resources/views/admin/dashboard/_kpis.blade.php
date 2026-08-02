{{-- Executive KPI grid, grouped by meaning (financial ≠ customer ≠ operational — never mixed). --}}
<div class="space-y-4">
    @foreach (['financial' => 'Financial', 'customer' => 'Customer', 'operational' => 'Operational'] as $group => $title)
        <div>
            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-faint">{{ $title }}</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ($kpis[$group] as $row)
                    @php
                        $deltaColor = is_null($row['delta']) ? 'text-faint' : ($row['delta'] > 0 ? 'text-emerald-600' : ($row['delta'] < 0 ? 'text-rose-600' : 'text-faint'));
                        $deltaText = is_null($row['delta']) ? '—' : (($row['delta'] > 0 ? '+' : '').$row['delta'].'%');
                        // Ring fill is capped at a full circle (100%) — the exact
                        // number (which can exceed 100%) still prints in the center.
                        $ringR = 16;
                        $ringCirc = 2 * M_PI * $ringR;
                        $ringFraction = is_null($row['delta']) ? 0 : min(abs($row['delta']), 100) / 100;
                        $ringOffset = $ringCirc * (1 - $ringFraction);
                        $ringColor = (is_null($row['delta']) || $row['delta'] == 0) ? null : ($row['delta'] > 0 ? '#10b981' : '#f43f5e');
                    @endphp
                    @if ($row['href'])
                        <a href="{{ $row['href'] }}" class="card-solid rounded-2xl border border-app p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" title="{{ $row['hint'] }}">
                    @else
                        <div class="card-solid rounded-2xl border border-app p-4 shadow-sm" title="{{ $row['hint'] }}">
                    @endif
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: {{ $row['tint'] }}"><x-img-icon :name="$row['img']" class="h-4 w-4" /></span>
                                <p class="truncate text-[11px] text-faint">{{ $row['label'] }}</p>
                            </div>
                            <div class="relative grid h-10 w-10 shrink-0 place-items-center" aria-label="{{ $deltaText }} {{ __('vs previous') }}">
                                <svg viewBox="0 0 40 40" class="h-10 w-10 -rotate-90">
                                    <circle cx="20" cy="20" r="{{ $ringR }}" fill="none" stroke="color-mix(in srgb, {{ $row['tint'] }} 25%, transparent)" stroke-width="4.5" />
                                    @if ($ringColor)
                                        <circle cx="20" cy="20" r="{{ $ringR }}" fill="none" stroke="{{ $ringColor }}" stroke-width="4.5" stroke-linecap="round"
                                                stroke-dasharray="{{ $ringCirc }}" stroke-dashoffset="{{ $ringOffset }}" />
                                    @endif
                                </svg>
                                <span class="absolute text-[8.5px] font-bold leading-none {{ $deltaColor }}">{{ $deltaText }}</span>
                            </div>
                        </div>
                        <p class="mt-2 text-lg font-bold text-strong">{{ $row['money'] ? number_format($row['value']).' '.$currency : number_format($row['value']) }}</p>
                    @if ($row['href'])
                        </a>
                    @else
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>
