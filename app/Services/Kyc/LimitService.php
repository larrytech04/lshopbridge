<?php

namespace App\Services\Kyc;

use App\Models\KycLevel;
use App\Models\User;
use Carbon\Carbon;

/**
 * Enforces per-KYC-level transaction limits (per-transaction, daily, monthly).
 * Limits are admin-configurable in the kyc_levels table.
 */
class LimitService
{
    /**
     * @return array{ok: bool, reason: ?string, level: ?KycLevel}
     */
    public function check(User $user, float $amount): array
    {
        $level = KycLevel::where('level', $user->kyc_level)->first();

        if (! $level || ! $level->is_active) {
            return ['ok' => false, 'reason' => 'Your verification level does not allow transactions yet.', 'level' => $level];
        }

        if ($level->per_transaction_limit > 0 && $amount > (float) $level->per_transaction_limit) {
            return ['ok' => false, 'reason' => 'Amount exceeds your per-transaction limit of '.money($level->per_transaction_limit, $level->currency).'.', 'level' => $level];
        }

        $dailyUsed = $this->usedSince($user, Carbon::today());
        if ($level->daily_limit > 0 && ($dailyUsed + $amount) > (float) $level->daily_limit) {
            return ['ok' => false, 'reason' => 'This would exceed your daily limit of '.money($level->daily_limit, $level->currency).'.', 'level' => $level];
        }

        $monthlyUsed = $this->usedSince($user, Carbon::now()->startOfMonth());
        if ($level->monthly_limit > 0 && ($monthlyUsed + $amount) > (float) $level->monthly_limit) {
            return ['ok' => false, 'reason' => 'This would exceed your monthly limit of '.money($level->monthly_limit, $level->currency).'.', 'level' => $level];
        }

        return ['ok' => true, 'reason' => null, 'level' => $level];
    }

    /** Sum of funding totals charged since a given moment (in-flight + done). */
    private function usedSince(User $user, Carbon $since): float
    {
        return (float) $user->fundingRequests()
            ->where('created_at', '>=', $since)
            ->whereNotIn('status', ['refunded', 'funding_failed'])
            ->sum('total_charged');
    }
}
