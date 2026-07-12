@props(['steps' => [], 'start' => null, 'end' => null])
@php
    $n = count($steps);

    // ---- Desktop: horizontal serpentine "road" -----------------------------
    $perRow = 3;
    $rows = max(1, (int) ceil($n / $perRow));
    $vw = 1200; $rowH = 300; $vh = $rows * $rowH;
    $colXTop = [200, 600, 1000];   // even rows
    $colXBot = [350, 750, 1050];   // odd rows — offset so waves don't line up
    $nodes = [];
    foreach ($steps as $i => $s) {
        $r = intdiv($i, $perRow);
        $c = $i % $perRow;
        if ($r % 2 === 1) { $c = $perRow - 1 - $c; }   // snake: reverse odd rows
        $cols = ($r % 2 === 0) ? $colXTop : $colXBot;
        $nodes[$i] = ['x' => $cols[$c], 'y' => $r * $rowH + intval($rowH / 2), 'r' => $r, 'c' => $c];
    }
    // Winding path: lead-in → nodes (waves + U-turns) → tail.
    $y0 = $nodes[0]['y']; $x0 = $nodes[0]['x'];
    $d = "M60,{$y0} C120,{$y0} " . ($x0 - 70) . ",{$y0} {$x0},{$y0}";
    for ($i = 1; $i < $n; $i++) {
        $x = $nodes[$i]['x']; $y = $nodes[$i]['y'];
        $px = $nodes[$i - 1]['x']; $py = $nodes[$i - 1]['y'];
        if ($nodes[$i]['r'] === $nodes[$i - 1]['r']) {
            $midY = $py + (($i % 2 === 0) ? 150 : -150);
            $c1x = $px + intval(($x - $px) / 3); $c2x = $px + intval(2 * ($x - $px) / 3);
            $d .= " C{$c1x},{$midY} {$c2x},{$midY} {$x},{$y}";
        } else {
            $bulge = ($nodes[$i - 1]['c'] === $perRow - 1) ? 160 : -160;
            $cx = $px + $bulge;
            $d .= " C{$cx},{$py} {$cx},{$y} {$x},{$y}";
        }
    }
    $lx = $nodes[$n - 1]['x']; $ly = $nodes[$n - 1]['y'];
    $tdir = $nodes[$n - 1]['c'] === 0 ? -1 : 1;
    $d .= " C" . ($lx + $tdir * 70) . ",{$ly} " . ($lx + $tdir * 110) . ",{$ly} " . ($lx + $tdir * 150) . ",{$ly}";

    $pctX = fn ($x) => round($x / $vw * 100, 3);
    $pctY = fn ($y) => round($y / $vh * 100, 3);

    // ---- Mobile: vertical wave spine ---------------------------------------
    $waveD = 'M20,0';
    for ($wy = 0; $wy < 1200; $wy += 100) {
        $cx = (intval($wy / 100) % 2 === 0) ? 34 : 6;
        $waveD .= " C{$cx}," . ($wy + 33) . " {$cx}," . ($wy + 67) . " 20," . ($wy + 100);
    }
@endphp

<div data-journey>
    {{-- Desktop: horizontal winding road with milestones on the curve --}}
    <div class="relative mx-auto hidden max-w-6xl lg:block" style="height: {{ $rows * 400 }}px;">
        <svg class="absolute inset-0 h-full w-full" viewBox="0 0 {{ $vw }} {{ $vh }}" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path class="journey-track" d="{{ $d }}" stroke="var(--color-brand-500)" stroke-width="2.5" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            <path class="journey-fill" d="{{ $d }}" stroke="var(--color-brand-600)" stroke-width="3" stroke-linecap="round" vector-effect="non-scaling-stroke" />
        </svg>

        @if ($start)
            <span class="absolute z-20 -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand-600 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-white shadow-lg"
                  style="left: {{ $pctX(60) }}%; top: {{ $pctY($nodes[0]['y']) }}%;">{{ $start }}</span>
        @endif

        @foreach ($steps as $i => $step)
            @php $node = $nodes[$i]; $above = $node['r'] % 2 === 0; @endphp
            <span class="absolute z-20 grid h-11 w-11 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-brand-600 text-base font-extrabold text-white shadow-lg ring-4"
                  style="left: {{ $pctX($node['x']) }}%; top: {{ $pctY($node['y']) }}%; --tw-ring-color: var(--bg);">{{ $i + 1 }}</span>
            <div class="absolute z-10 w-72 text-center"
                 style="left: {{ $pctX($node['x']) }}%; top: {{ $pctY($node['y']) }}%; transform: translate(-50%, {{ $above ? 'calc(-100% - 42px)' : '42px' }});">
                <div class="mb-1.5 flex items-center justify-center gap-2">
                    <span class="grid h-9 w-9 shrink-0 place-items-center text-brand-600"><x-img-icon :name="$step[0]" class="h-7 w-7" /></span>
                    <h3 class="text-sm font-bold text-strong">{{ $step[1] }}</h3>
                </div>
                <p class="text-xs leading-relaxed text-muted">{{ $step[2] }}</p>
            </div>
        @endforeach

        @if ($end)
            <span class="absolute z-20 inline-flex -translate-x-1/2 -translate-y-1/2 items-center gap-1 rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-white shadow-lg"
                  style="left: {{ $pctX($lx + $tdir * 150) }}%; top: {{ $pctY($ly) }}%;"><x-icon name="check" class="h-3 w-3" /> {{ $end }}</span>
        @endif
    </div>

    {{-- Mobile / tablet: vertical wave timeline --}}
    <div class="relative mx-auto max-w-2xl lg:hidden">
        <svg class="journey-wave pointer-events-none absolute bottom-2 top-2 left-[1.4rem] w-16 -translate-x-1/2" viewBox="0 0 40 1200" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path class="journey-track" d="{{ $waveD }}" stroke="var(--color-brand-500)" stroke-width="2.5" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            <path class="journey-fill" d="{{ $waveD }}" stroke="var(--color-brand-600)" stroke-width="3" stroke-linecap="round" vector-effect="non-scaling-stroke" />
        </svg>

        @if ($start)
            <div class="relative flex pb-6 pl-16">
                <span class="rounded-full bg-brand-600 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-white shadow-lg">{{ $start }}</span>
            </div>
        @endif

        @foreach ($steps as $i => $step)
            <div class="relative pb-8 pl-16">
                <span class="absolute left-[1.4rem] top-0 z-10 grid h-11 w-11 -translate-x-1/2 place-items-center rounded-full bg-brand-600 text-base font-extrabold text-white shadow-lg ring-4" style="--tw-ring-color: var(--bg);">{{ $i + 1 }}</span>
                <div class="rounded-2xl border border-app card-solid p-5 shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-9 w-9 shrink-0 place-items-center text-brand-600"><x-img-icon :name="$step[0]" class="h-7 w-7" /></span>
                        <h3 class="font-bold text-strong">{{ $step[1] }}</h3>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $step[2] }}</p>
                </div>
            </div>
        @endforeach

        @if ($end)
            <div class="relative flex pl-16">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-white shadow-lg"><x-icon name="check" class="h-3.5 w-3.5" /> {{ $end }}</span>
            </div>
        @endif
    </div>
</div>
