{{--
    Executive KPI grid, grouped by meaning (financial ≠ customer ≠ operational
    — never mixed). Ring geometry/colors are computed once here (SSR) so the
    page is correct even with JS disabled; kpiLive() below then re-fetches the
    same real numbers from AdminDashboardController::kpisLive() on an interval
    and morphs each card in place — same data source (DashboardReportService),
    just polled instead of a full reload.

    Ring color rule: a ring is always its own card's icon color (row.tint) —
    never a single shared "up" color — and only ever switches to the brand
    red when that metric's period-over-period change is genuinely negative.
    A null delta (no prior-period baseline to compare against) shows a plain
    dash, not a claimed 0%.
--}}
@php
    // Brand red (see resources/css/app.css --color-brand-700) — the ONLY
    // color a ring is ever allowed that isn't its own card's icon tint.
    $negativeColor = '#840a20';
    $ringR = 22;
    $ringCirc = 2 * M_PI * $ringR;
@endphp
<div x-data="kpiLive(@js($currency))" x-init="init()" class="space-y-4">
    @foreach (['financial' => 'Financial', 'customer' => 'Customer', 'operational' => 'Operational'] as $group => $title)
        <div>
            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-faint">{{ $title }}</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ($kpis[$group] as $row)
                    @php
                        $isNegative = ! is_null($row['delta']) && $row['delta'] < 0;
                        $ringColor = $isNegative ? $negativeColor : $row['tint'];
                        $deltaText = is_null($row['delta']) ? '-' : (($row['delta'] > 0 ? '+' : '').$row['delta'].'%');
                        $ringFraction = is_null($row['delta']) ? 0 : min(abs($row['delta']), 100) / 100;
                        $ringOffset = $ringCirc * (1 - $ringFraction);
                        $valueDisplay = $row['money'] ? number_format($row['value']).' '.$currency : number_format($row['value']);
                    @endphp
                    @if ($row['href'])
                        <a href="{{ $row['href'] }}" class="card-solid rounded-2xl border border-app p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" title="{{ $row['hint'] }}" data-kpi-key="{{ $row['key'] }}">
                    @else
                        <div class="card-solid rounded-2xl border border-app p-4 shadow-sm" title="{{ $row['hint'] }}" data-kpi-key="{{ $row['key'] }}">
                    @endif
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: {{ $row['tint'] }}"><x-img-icon :name="$row['img']" class="h-4 w-4" /></span>
                                <p class="truncate text-[11px] text-faint">{{ $row['label'] }}</p>
                            </div>
                            <div class="relative grid h-14 w-14 shrink-0 place-items-center" aria-label="{{ $deltaText }} {{ __('vs previous period') }}">
                                <svg viewBox="0 0 56 56" class="h-14 w-14 -rotate-90">
                                    <circle cx="28" cy="28" r="{{ $ringR }}" fill="none" stroke="color-mix(in srgb, {{ $row['tint'] }} 22%, transparent)" stroke-width="6" />
                                    <circle data-role="arc" data-tint="{{ $row['tint'] }}" cx="28" cy="28" r="{{ $ringR }}" fill="none" stroke-width="6" stroke-linecap="round"
                                            stroke-dasharray="{{ $ringCirc }}"
                                            style="stroke: {{ $ringColor }}; stroke-dashoffset: {{ $ringOffset }}; transition: stroke-dashoffset .9s cubic-bezier(.4,0,.2,1), stroke .45s ease;" />
                                </svg>
                                <span data-role="delta-text" class="absolute text-[11px] font-extrabold leading-none tracking-tight {{ is_null($row['delta']) ? 'text-faint' : '' }}" style="{{ is_null($row['delta']) ? '' : 'color: '.$ringColor.';' }} transition: color .45s ease;">{{ $deltaText }}</span>
                            </div>
                        </div>
                        <p data-role="value-text" class="mt-2 text-lg font-bold text-strong">{{ $valueDisplay }}</p>
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

@push('scripts')
<script>
function kpiLive(currency) {
    return {
        timer: null,
        // 25s — frequent enough to feel live on a page an admin keeps open,
        // without hammering the DB with aggregate queries on every tick.
        intervalMs: 25000,
        ringR: {{ $ringR }},
        ringCirc: {{ $ringCirc }},
        negativeColor: '{{ $negativeColor }}',
        init() {
            // Entrance draw-in: start every arc at 0 and animate to its real
            // value one frame after paint, so the ring visibly draws in on
            // load instead of just appearing already-filled.
            const arcs = this.$el.querySelectorAll('[data-role="arc"]');
            const targets = new Map();
            arcs.forEach(arc => {
                targets.set(arc, arc.style.strokeDashoffset);
                arc.style.transition = 'none';
                arc.style.strokeDashoffset = this.ringCirc;
            });
            requestAnimationFrame(() => requestAnimationFrame(() => {
                arcs.forEach(arc => {
                    arc.style.transition = 'stroke-dashoffset .9s cubic-bezier(.4,0,.2,1), stroke .45s ease';
                    arc.style.strokeDashoffset = targets.get(arc);
                });
            }));

            this.timer = setInterval(() => this.refresh(), this.intervalMs);
            // Pause polling when the tab isn't visible — no point refreshing
            // a dashboard nobody's looking at.
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) { clearInterval(this.timer); }
                else { this.refresh(); this.timer = setInterval(() => this.refresh(), this.intervalMs); }
            });
        },
        async refresh() {
            try {
                const res = await fetch('{{ route('admin.dashboard.kpis') }}?' + new URLSearchParams(window.location.search), {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                ['financial', 'customer', 'operational'].forEach(group => (data[group] || []).forEach(row => this.applyRow(row)));
            } catch (e) { /* silent — next tick tries again, current values stay put rather than showing an error state for a background refresh */ }
        },
        applyRow(row) {
            const card = this.$el.querySelector(`[data-kpi-key="${row.key}"]`);
            if (!card) return;

            const arc = card.querySelector('[data-role="arc"]');
            const isNegative = row.delta !== null && row.delta < 0;
            // arc.dataset.tint is that card's OWN icon color, set server-side
            // — never a shared "positive" color, matching the same rule the
            // initial render uses.
            const color = isNegative ? this.negativeColor : arc.dataset.tint;
            const fraction = row.delta === null ? 0 : Math.min(Math.abs(row.delta), 100) / 100;
            const offset = this.ringCirc * (1 - fraction);
            const deltaText = row.delta === null ? '-' : ((row.delta > 0 ? '+' : '') + row.delta + '%');

            arc.style.stroke = color;
            arc.style.strokeDashoffset = offset;

            const label = card.querySelector('[data-role="delta-text"]');
            label.textContent = deltaText;
            label.style.color = row.delta === null ? '' : color;
            label.classList.toggle('text-faint', row.delta === null);

            const value = card.querySelector('[data-role="value-text"]');
            if (value.textContent !== row.value_display) { value.textContent = row.value_display; }
        },
    };
}
</script>
@endpush
