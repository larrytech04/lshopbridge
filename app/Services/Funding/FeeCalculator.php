<?php

namespace App\Services\Funding;

use App\Models\User;
use App\Services\Fees\FeeCalculationService;

/**
 * Thin backward-compatible facade over FeeCalculationService, kept so
 * DepositService/FundingService don't need constructor changes. All real
 * matching/calculation logic lives in the one centralized engine.
 */
class FeeCalculator
{
    public function __construct(private FeeCalculationService $engine) {}

    public function feeFor(float $amount, string $appliesTo = 'funding', ?string $scope = null, ?User $user = null): float
    {
        return (float) $this->quote($amount, $appliesTo, $scope, $user)['calculated_fee'];
    }

    /** Full breakdown, so callers can freeze which rule priced a transaction. */
    public function quote(float $amount, string $appliesTo = 'funding', ?string $scope = null, ?User $user = null): array
    {
        return $this->engine->calculate($amount, $appliesTo, [
            'scope' => $scope,
            'user' => $user,
            'customer_role' => $user?->role?->value,
        ]);
    }
}
