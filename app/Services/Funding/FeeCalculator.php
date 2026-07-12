<?php

namespace App\Services\Funding;

use App\Models\Fee;

/**
 * Resolves the active admin-defined fee for a given context and computes the
 * fee amount. Fees are fully managed from the admin panel (fees table).
 */
class FeeCalculator
{
    public function feeFor(float $amount, string $appliesTo = 'funding', ?string $scope = null): float
    {
        $fee = $this->resolveFee($appliesTo, $scope);

        if (! $fee) {
            return 0.0;
        }

        $value = $fee->type === 'percent'
            ? ($amount * (float) $fee->value) / 100
            : (float) $fee->value;

        $value = max($value, (float) $fee->min_fee);

        if ($fee->max_fee !== null) {
            $value = min($value, (float) $fee->max_fee);
        }

        return round($value, 2);
    }

    private function resolveFee(string $appliesTo, ?string $scope): ?Fee
    {
        $query = Fee::active()->where(function ($q) use ($appliesTo) {
            $q->where('applies_to', $appliesTo)->orWhere('applies_to', 'all');
        });

        // Prefer a scoped fee (e.g. specific method) over a generic one.
        if ($scope) {
            $scoped = (clone $query)->where('scope', $scope)->orderBy('sort')->first();
            if ($scoped) {
                return $scoped;
            }
        }

        return $query->whereNull('scope')->orderBy('sort')->first();
    }
}
