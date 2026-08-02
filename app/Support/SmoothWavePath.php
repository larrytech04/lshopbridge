<?php

namespace App\Support;

/**
 * Monotone cubic Hermite interpolation (Fritsch-Carlson), converted to SVG
 * cubic Bezier segments. Unlike a plain Catmull-Rom spline, this never
 * overshoots past the value of its neighbouring points — a sharp spike
 * surrounded by near-zero points stays smooth without the curve dipping
 * below the baseline (or above the peak) on either side of it.
 */
class SmoothWavePath
{
    /** @param array<int, array{0: float, 1: float}> $points Screen-space [x, y] pairs, already sorted by x. */
    public static function build(array $points): string
    {
        $n = count($points);
        if ($n === 0) {
            return '';
        }
        if ($n === 1) {
            return sprintf('M %F %F L %F %F', $points[0][0], $points[0][1], $points[0][0], $points[0][1]);
        }

        $xs = array_column($points, 0);
        $ys = array_column($points, 1);

        // Secant slope between each consecutive pair.
        $d = [];
        for ($i = 0; $i < $n - 1; $i++) {
            $dx = $xs[$i + 1] - $xs[$i];
            $d[$i] = $dx == 0.0 ? 0.0 : ($ys[$i + 1] - $ys[$i]) / $dx;
        }

        // Initial tangents: average of the two adjacent secants (endpoints
        // just take the one secant they have).
        $m = [];
        $m[0] = $d[0];
        for ($i = 1; $i < $n - 1; $i++) {
            $m[$i] = ($d[$i - 1] + $d[$i]) / 2;
        }
        $m[$n - 1] = $d[$n - 2];

        // Fritsch-Carlson monotonicity correction: rescale each pair of
        // tangents flanking a secant so the curve can't overshoot it.
        for ($i = 0; $i < $n - 1; $i++) {
            if ($d[$i] == 0.0) {
                $m[$i] = 0.0;
                $m[$i + 1] = 0.0;

                continue;
            }
            $alpha = $m[$i] / $d[$i];
            $beta = $m[$i + 1] / $d[$i];
            $sumSq = $alpha ** 2 + $beta ** 2;
            if ($sumSq > 9) {
                $tau = 3 / sqrt($sumSq);
                $m[$i] = $tau * $alpha * $d[$i];
                $m[$i + 1] = $tau * $beta * $d[$i];
            }
        }

        $path = sprintf('M %F %F ', $xs[0], $ys[0]);
        for ($i = 0; $i < $n - 1; $i++) {
            $h = $xs[$i + 1] - $xs[$i];
            $cp1x = $xs[$i] + $h / 3;
            $cp1y = $ys[$i] + $m[$i] * $h / 3;
            $cp2x = $xs[$i + 1] - $h / 3;
            $cp2y = $ys[$i + 1] - $m[$i + 1] * $h / 3;
            $path .= sprintf('C %F %F, %F %F, %F %F ', $cp1x, $cp1y, $cp2x, $cp2y, $xs[$i + 1], $ys[$i + 1]);
        }

        return $path;
    }
}
