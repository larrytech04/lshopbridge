<?php

namespace App\Services\Risk;

use App\Models\KycLevel;
use App\Models\RiskFlag;
use App\Models\RiskRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Automatic risk/fraud screening. Each active rule (admin-managed in risk_rules)
 * is evaluated; trips raise a RiskFlag and may force a manual review or block.
 *
 * Returns an assessment: whether the transaction may proceed automatically,
 * needs manual review, or must be blocked outright.
 */
class RiskEngine
{
    /**
     * @param  string  $context  deposit|funding
     * @param  array   $data     extra signals: ['recipient_name' => ..., 'ip' => ...]
     * @return array{requires_review: bool, blocked: bool, reasons: array<string>, flags: array<RiskFlag>}
     */
    public function evaluate(User $user, float $amount, string $context, ?Model $subject = null, array $data = []): array
    {
        $requiresReview = false;
        $blocked = false;
        $reasons = [];
        $flags = [];

        foreach (RiskRule::active()->get() as $rule) {
            $tripped = $this->checkRule($rule, $user, $amount, $context, $data);

            if (! $tripped) {
                continue;
            }

            $flags[] = $this->flag($user, $rule, $tripped, $subject, $data);
            $reasons[] = $tripped;

            if ($rule->action === 'block') {
                $blocked = true;
            }
            if (in_array($rule->action, ['review', 'block'], true)) {
                $requiresReview = true;
            }
        }

        return [
            'requires_review' => $requiresReview,
            'blocked' => $blocked,
            'reasons' => $reasons,
            'flags' => $flags,
        ];
    }

    /** Returns a human reason string if the rule trips, otherwise null. */
    private function checkRule(RiskRule $rule, User $user, float $amount, string $context, array $data): ?string
    {
        $params = $rule->params ?? [];

        return match ($rule->code) {
            'blocked_country' => $this->blockedCountry($user),
            'unverified_account' => $this->unverifiedAccount($user, $amount, $params),
            'large_transaction' => $this->largeTransaction($user, $amount, $params),
            'velocity' => $this->velocity($user, $params),
            'failed_attempts' => $this->failedAttempts($user, $params),
            'name_mismatch' => $context === 'funding' ? $this->nameMismatch($user, $data) : null,
            default => null,
        };
    }

    private function blockedCountry(User $user): ?string
    {
        if ($user->country && $user->country->is_blocked) {
            return "Transactions from {$user->country->name} require manual review.";
        }

        return null;
    }

    private function unverifiedAccount(User $user, float $amount, array $params): ?string
    {
        $threshold = (float) ($params['amount'] ?? 0);

        if (! $user->isKycApproved() && $amount > $threshold) {
            return 'Account is not fully verified for this amount.';
        }

        return null;
    }

    private function largeTransaction(User $user, float $amount, array $params): ?string
    {
        $multiplier = (float) ($params['multiplier'] ?? config('platform.risk.large_tx_multiplier', 0.9));
        $level = KycLevel::where('level', $user->kyc_level)->first();
        $perTx = $level ? (float) $level->per_transaction_limit : 0;

        if ($perTx > 0 && $amount >= ($perTx * $multiplier)) {
            return 'Large transaction relative to your verification limit.';
        }

        $absolute = (float) ($params['absolute'] ?? 0);
        if ($absolute > 0 && $amount >= $absolute) {
            return 'Transaction exceeds the large-transaction threshold.';
        }

        return null;
    }

    private function velocity(User $user, array $params): ?string
    {
        $count = (int) ($params['count'] ?? config('platform.risk.velocity_count', 5));
        $minutes = (int) ($params['window_minutes'] ?? config('platform.risk.velocity_window_minutes', 30));

        $recent = $user->fundingRequests()
            ->where('created_at', '>=', Carbon::now()->subMinutes($minutes))
            ->count();

        if ($recent >= $count) {
            return "High activity: {$recent} requests in {$minutes} minutes.";
        }

        return null;
    }

    private function failedAttempts(User $user, array $params): ?string
    {
        $max = (int) ($params['max'] ?? config('platform.risk.max_failed_payments', 3));

        $failed = \App\Models\PaymentIntent::where('user_id', $user->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->count();

        if ($failed >= $max) {
            return "Multiple failed payment attempts ({$failed}) in 24h.";
        }

        return null;
    }

    private function nameMismatch(User $user, array $data): ?string
    {
        $recipient = (string) ($data['recipient_name'] ?? '');

        if ($recipient === '') {
            return null;
        }

        // Soft check: only flag when there is no token overlap at all.
        $userTokens = $this->tokens($user->name);
        $recipientTokens = $this->tokens($recipient);

        if (! array_intersect($userTokens, $recipientTokens)) {
            return 'Recipient name does not match the account holder.';
        }

        return null;
    }

    private function tokens(string $value): array
    {
        return array_filter(explode(' ', Str::of($value)->lower()->ascii()->replaceMatches('/[^a-z ]/', '')->toString()));
    }

    private function flag(User $user, RiskRule $rule, string $reason, ?Model $subject, array $data): RiskFlag
    {
        $flag = new RiskFlag([
            'rule_code' => $rule->code,
            'severity' => $rule->severity,
            'reason' => $reason,
            'status' => 'open',
            'context' => $data ?: null,
        ]);
        $flag->user()->associate($user);

        if ($subject) {
            $flag->flaggable()->associate($subject);
        }

        $flag->save();

        return $flag;
    }
}
