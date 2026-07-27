{{-- Financial Performance Center: combined chart + supporting breakdowns. --}}
<x-glass-card solid>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="font-semibold text-strong">Financial performance · {{ $period['label'] }}</h3>
        <form method="GET" class="flex items-center gap-2">
            @foreach (request()->except('granularity') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <select name="granularity" onchange="this.form.requestSubmit()" class="field !py-1 text-xs">
                <option value="daily" @selected($financialSeries['granularity']==='daily')>Daily</option>
                <option value="weekly" @selected($financialSeries['granularity']==='weekly')>Weekly</option>
                <option value="monthly" @selected($financialSeries['granularity']==='monthly')>Monthly</option>
            </select>
        </form>
    </div>
    <div class="mt-3 flex items-center gap-3 text-xs">
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span><span class="text-muted">Deposits</span></span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-500"></span><span class="text-muted">Funding</span></span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-accent-500"></span><span class="text-muted">Sales</span></span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-500"></span><span class="text-muted">Refunds</span></span>
    </div>

    @php
        $rawPoints = $financialSeries['points'];
        $n = count($rawPoints);
        $max = max(1, collect($rawPoints)->flatMap(fn ($d) => [$d['deposits'], $d['funding'], $d['sales'], $d['refunds']])->max());

        // Fixed per-point width (matches the old bars' min-w-[1.75rem] slot) so
        // the chart keeps every label readable via horizontal scroll instead of
        // cramming 30+ dates into one fluid width.
        $pointWidth = 28;
        $chartWidth = max($n * $pointWidth, 200);
        $chartHeight = 208;
        $padTop = 16;
        $padBottom = 6;
        $plotHeight = $chartHeight - $padTop - $padBottom;

        $xFor = fn (int $i) => $n <= 1 ? $chartWidth / 2 : $i * $pointWidth + $pointWidth / 2;
        $yFor = fn (float $v) => $chartHeight - $padBottom - ($v / $max) * $plotHeight;

        $seriesColors = ['deposits' => '#10b981', 'funding' => '#bf1f39', 'sales' => '#e25c74', 'refunds' => '#f43f5e'];

        // Catmull-Rom -> cubic Bezier smoothing: turns the point-to-point zig-zag
        // into one flowing curve through every value, i.e. a "wave" line instead
        // of straight segments (and, before this, instead of bars entirely).
        $smoothPath = function (array $pts): string {
            $count = count($pts);
            if ($count === 0) return '';
            if ($count === 1) return sprintf('M %F %F L %F %F', $pts[0][0], $pts[0][1], $pts[0][0], $pts[0][1]);
            $d = sprintf('M %F %F ', $pts[0][0], $pts[0][1]);
            for ($i = 0; $i < $count - 1; $i++) {
                $p0 = $pts[$i - 1] ?? $pts[$i];
                $p1 = $pts[$i];
                $p2 = $pts[$i + 1];
                $p3 = $pts[$i + 2] ?? $p2;
                $cp1x = $p1[0] + ($p2[0] - $p0[0]) / 6;
                $cp1y = $p1[1] + ($p2[1] - $p0[1]) / 6;
                $cp2x = $p2[0] - ($p3[0] - $p1[0]) / 6;
                $cp2y = $p2[1] - ($p3[1] - $p1[1]) / 6;
                $d .= sprintf('C %F %F, %F %F, %F %F ', $cp1x, $cp1y, $cp2x, $cp2y, $p2[0], $p2[1]);
            }
            return $d;
        };

        $paths = [];
        foreach ($seriesColors as $key => $color) {
            $pts = [];
            foreach ($rawPoints as $i => $d) {
                $pts[] = [$xFor($i), $yFor((float) $d[$key])];
            }
            $paths[$key] = $smoothPath($pts);
        }

        $chartPoints = collect($rawPoints)->map(fn ($d, $i) => [
            'label' => $d['label'], 'x' => $xFor($i),
            'deposits' => $d['deposits'], 'yDeposits' => $yFor((float) $d['deposits']),
            'funding' => $d['funding'], 'yFunding' => $yFor((float) $d['funding']),
            'sales' => $d['sales'], 'ySales' => $yFor((float) $d['sales']),
            'refunds' => $d['refunds'], 'yRefunds' => $yFor((float) $d['refunds']),
        ])->values();

        $baselineY = $chartHeight - $padBottom;
    @endphp

    <div class="no-scrollbar relative mt-5 overflow-x-auto" x-data="financialWaveChart(@js($chartPoints), '{{ $currency }}')">
        <svg width="{{ $chartWidth }}" height="{{ $chartHeight }}" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="h-52 max-w-none touch-none"
             @mousemove="handleMove($event)" @mouseleave="clear()" @touchstart.passive="handleTouch($event)" @touchmove.passive="handleTouch($event)">
            <line x1="0" y1="{{ $baselineY }}" x2="{{ $chartWidth }}" y2="{{ $baselineY }}" stroke="var(--border)" stroke-width="1" />

            <line x-show="hover !== null" x-cloak :x1="activeX" :x2="activeX" y1="0" y2="{{ $baselineY }}"
                  stroke="var(--border)" stroke-width="1.5" stroke-dasharray="3,3" />

            @foreach ($paths as $key => $d)
                <path d="{{ $d }}" fill="none" stroke="{{ $seriesColors[$key] }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
            @endforeach

            {{-- x-show (not x-if): a fast mouse sweep toggles `active` many
                 times a second, and x-if's repeated template clone/teardown
                 cycle can't keep up (throws on the clone step mid-sweep).
                 x-show only flips display, so there's no clone/remove churn —
                 the nested bindings stay guarded since the group still exists,
                 just hidden, while `active` is null. --}}
            <g x-show="active" x-cloak>
                <circle :cx="activeX" :cy="active ? active.yDeposits : 0" r="3.5" fill="{{ $seriesColors['deposits'] }}" stroke="var(--glass-strong-bg)" stroke-width="1.5" />
                <circle :cx="activeX" :cy="active ? active.yFunding : 0" r="3.5" fill="{{ $seriesColors['funding'] }}" stroke="var(--glass-strong-bg)" stroke-width="1.5" />
                <circle :cx="activeX" :cy="active ? active.ySales : 0" r="3.5" fill="{{ $seriesColors['sales'] }}" stroke="var(--glass-strong-bg)" stroke-width="1.5" />
                <circle :cx="activeX" :cy="active ? active.yRefunds : 0" r="3.5" fill="{{ $seriesColors['refunds'] }}" stroke="var(--glass-strong-bg)" stroke-width="1.5" />
            </g>
        </svg>

        <div id="financial-chart-tooltip" class="glass-strong pointer-events-none absolute top-0 z-10 -translate-x-1/2 whitespace-nowrap rounded-xl px-3 py-2 text-[11px] shadow-lg"
             style="min-width: 9rem;" x-show="active" x-cloak :style="{ left: activeX + 'px' }">
            <p class="mb-1 font-semibold text-strong" x-text="active ? active.label : ''"></p>
            <div class="flex items-center justify-between gap-3"><span class="flex items-center gap-1.5 text-muted"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>Deposits</span><span class="font-semibold text-strong" x-text="active ? fmt(active.deposits) + ' {{ $currency }}' : ''"></span></div>
            <div class="flex items-center justify-between gap-3"><span class="flex items-center gap-1.5 text-muted"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>Funding</span><span class="font-semibold text-strong" x-text="active ? fmt(active.funding) + ' CNY' : ''"></span></div>
            <div class="flex items-center justify-between gap-3"><span class="flex items-center gap-1.5 text-muted"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>Sales</span><span class="font-semibold text-strong" x-text="active ? fmt(active.sales) + ' {{ $currency }}' : ''"></span></div>
            <div class="flex items-center justify-between gap-3"><span class="flex items-center gap-1.5 text-muted"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500"></span>Refunds</span><span class="font-semibold text-strong" x-text="active ? fmt(active.refunds) + ' {{ $currency }}' : ''"></span></div>
        </div>

        <div class="no-scrollbar mt-1 flex" style="width: {{ $chartWidth }}px">
            @foreach ($rawPoints as $d)
                <span class="flex-none text-center text-[9px] text-faint" style="width: {{ $pointWidth }}px">{{ $d['label'] }}</span>
            @endforeach
        </div>
    </div>

    <div class="mt-6 grid gap-5 border-t border-app pt-5 sm:grid-cols-2">
        <div>
            <p class="mb-2 text-xs font-semibold uppercase text-faint">Deposits by payment method</p>
            <div class="space-y-1.5">
                @forelse ($financialSeries['depositsByMethod'] as $m)
                    <div class="flex items-center justify-between text-sm"><span class="text-body">{{ $m->name }}</span><span class="font-semibold text-strong">{{ money($m->total, $currency) }}</span></div>
                @empty
                    <p class="text-xs text-faint">No confirmed deposits in this period.</p>
                @endforelse
            </div>
        </div>
        <div>
            <p class="mb-2 text-xs font-semibold uppercase text-faint">Funding by China wallet type</p>
            <div class="space-y-1.5">
                @forelse ($financialSeries['fundingByWallet'] as $w)
                    <div class="flex items-center justify-between text-sm"><span class="text-body">{{ $w['app_type'] }}</span><span class="font-semibold text-strong">{{ money($w['total'], 'CNY') }}</span></div>
                @empty
                    <p class="text-xs text-faint">No successful funding in this period.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-glass-card>
