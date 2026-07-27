<?php

namespace App\Services\Fees;

use App\Enums\FeePayer;
use App\Enums\FeeType;
use App\Models\Fee;
use App\Models\FeeExemption;
use App\Models\User;

/**
 * The single fee-calculation engine for the whole platform. Every caller —
 * DepositService, FundingService (via FeeCalculator, which now just delegates
 * here), the admin Fee Calculator, and the add/edit-fee live preview — goes
 * through calculate()/resolve() so there is exactly one fee formula, not one
 * per screen.
 *
 * Only "deposit" and "funding" applies_to categories are actually called by a
 * real transaction flow today (see FeeCalculator). Every other configured
 * category (withdrawal, marketplace_order, agent_commission, etc.) can be
 * priced here and previewed in the calculator, but nothing in the codebase
 * charges it yet — that is a genuine gap in the platform, not something this
 * page can fake.
 */
class FeeCalculationService
{
    /**
     * Find the single best-matching active fee rule for a category + context.
     * "Best" = most specific (most narrowing conditions satisfied), then
     * lowest `sort` (admin-controlled priority) as a tiebreaker — this mirrors
     * the original FeeCalculator's "prefer a scoped fee over a generic one"
     * rule, generalized to every matchable dimension.
     *
     * @param  array{scope?:?string, amount?:?float, country?:?string, customer_role?:?string,
     *   kyc_level?:?int, payment_provider?:?string, china_wallet_type?:?string, currency?:?string}  $context
     */
    public function resolve(string $appliesTo, array $context = []): ?Fee
    {
        $today = now()->toDateString();

        $candidates = Fee::active()
            ->with('tiers')
            ->where(fn ($q) => $q->where('applies_to', $appliesTo)->orWhere('applies_to', 'all'))
            ->where(fn ($q) => $q->whereNull('effective_start_date')->orWhereDate('effective_start_date', '<=', $today))
            ->where(fn ($q) => $q->whereNull('effective_end_date')->orWhereDate('effective_end_date', '>=', $today))
            ->get();

        $amount = $context['amount'] ?? null;
        // "currency" is deliberately excluded: it's required on every fixed/fixed_plus_percent
        // fee just to say what currency the flat number is in, not a targeting condition — this
        // platform doesn't route fees by transaction currency, and no caller supplies it in
        // context, so treating it as a hard-match dimension would make every fixed fee unmatchable.
        $dimensions = ['scope', 'country', 'customer_role', 'kyc_level', 'payment_provider', 'china_wallet_type'];

        $matches = $candidates->filter(function (Fee $fee) use ($context, $amount, $dimensions) {
            foreach ($dimensions as $dimension) {
                $ruleValue = $fee->{$dimension};
                if ($ruleValue === null || $ruleValue === '') {
                    continue; // this rule doesn't narrow on this dimension
                }
                if (! array_key_exists($dimension, $context) || $context[$dimension] === null || $context[$dimension] === '') {
                    return false; // rule requires a dimension the caller didn't supply
                }
                if (strcasecmp((string) $ruleValue, (string) $context[$dimension]) !== 0) {
                    return false;
                }
            }

            if ($amount !== null) {
                if ($fee->min_amount !== null && $amount < (float) $fee->min_amount) {
                    return false;
                }
                if ($fee->max_amount !== null && $amount > (float) $fee->max_amount) {
                    return false;
                }
            }

            return true;
        });

        return $matches->reduce(function (?Fee $best, Fee $candidate) {
            if ($best === null) {
                return $candidate;
            }

            $candidateScore = $this->specificity($candidate);
            $bestScore = $this->specificity($best);

            if ($candidateScore !== $bestScore) {
                return $candidateScore > $bestScore ? $candidate : $best;
            }

            return $candidate->sort < $best->sort ? $candidate : $best;
        });
    }

    /**
     * Full fee breakdown for a transaction — the same primitives real
     * transactions use, with the intermediate figures shown for the
     * admin calculator / live preview.
     */
    public function calculate(float $amount, string $appliesTo, array $context = []): array
    {
        $exemption = $this->matchingExemption($appliesTo, $context);

        if ($exemption) {
            return $this->breakdown($amount, null, 0.0, $appliesTo, $exemption);
        }

        $fee = $this->resolve($appliesTo, array_merge($context, ['amount' => $amount]));

        if (! $fee) {
            return $this->breakdown($amount, null, 0.0, $appliesTo, null);
        }

        return $this->breakdown($amount, $fee, $this->computeForFee($fee, $amount), $appliesTo, null);
    }

    private function computeForFee(Fee $fee, float $amount): float
    {
        $raw = match ($fee->type) {
            FeeType::Percent => $amount * (float) $fee->value / 100,
            FeeType::Fixed => (float) $fee->value,
            FeeType::FixedPlusPercent => (float) ($fee->fixed_value ?? 0) + ($amount * (float) $fee->value / 100),
            FeeType::Tiered => $this->tieredAmount($fee, $amount),
            FeeType::ProviderPassed => ($amount * (float) $fee->value / 100) + ($amount * (float) ($fee->provider_markup_percent ?? 0) / 100),
        };

        $value = max($raw, (float) $fee->min_fee);
        if ($fee->max_fee !== null) {
            $value = min($value, (float) $fee->max_fee);
        }

        // Financial safety net: a fee must never exceed the amount it's charged against.
        $value = min($value, $amount);

        return round($value, 2);
    }

    private function tieredAmount(Fee $fee, float $amount): float
    {
        $tier = $fee->tiers->first(fn ($t) => $amount >= (float) $t->min_amount && ($t->max_amount === null || $amount <= (float) $t->max_amount));

        if (! $tier) {
            return 0.0;
        }

        return (float) $tier->fixed + ($amount * (float) $tier->percent / 100);
    }

    private function specificity(Fee $fee): int
    {
        $dimensions = ['scope', 'country', 'customer_role', 'kyc_level', 'payment_provider', 'china_wallet_type', 'min_amount', 'max_amount'];

        return collect($dimensions)->filter(fn ($d) => $fee->{$d} !== null && $fee->{$d} !== '')->count();
    }

    /**
     * @param  array{user?:?User, customer_role?:?string, country?:?string, coupon_code?:?string, vip_level?:?string}  $context
     */
    private function matchingExemption(string $appliesTo, array $context): ?FeeExemption
    {
        $today = now()->toDateString();
        $user = $context['user'] ?? null;

        $exemptions = FeeExemption::where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->get();

        foreach ($exemptions as $exemption) {
            if (! $exemption->appliesToService($appliesTo)) {
                continue;
            }

            $matched = match ($exemption->exemption_type) {
                'customer', 'agent', 'merchant' => $user && (string) $exemption->user_id === (string) $user->id,
                'role' => $user && $exemption->target_value === ($context['customer_role'] ?? $user->role?->value),
                'country' => $exemption->target_value === ($context['country'] ?? null),
                'promotion', 'coupon' => $exemption->target_value === ($context['coupon_code'] ?? null),
                'vip_level' => $exemption->target_value === ($context['vip_level'] ?? null),
                'internal_test', 'admin_exception' => $user && (string) $exemption->user_id === (string) $user->id,
                default => false,
            };

            if ($matched) {
                return $exemption;
            }
        }

        return null;
    }

    private function breakdown(float $amount, ?Fee $fee, float $calculatedFee, string $appliesTo, ?FeeExemption $exemption): array
    {
        $exempt = $exemption !== null;
        $fee_ = $calculatedFee;

        return [
            'applies_to' => $appliesTo,
            'base_amount' => round($amount, 2),
            'matched_fee_id' => $fee?->id,
            'matched_fee_name' => $fee?->name,
            'matched_fee_code' => $fee?->code,
            'fee_type' => $fee?->type?->value,
            'percent_rate' => $fee && in_array($fee->type, [FeeType::Percent, FeeType::FixedPlusPercent, FeeType::ProviderPassed], true) ? (float) $fee->value : 0.0,
            'fixed_charge' => $fee ? (float) ($fee->type === FeeType::Fixed ? $fee->value : ($fee->fixed_value ?? 0)) : 0.0,
            'provider_markup_percent' => $fee?->type === FeeType::ProviderPassed ? (float) ($fee->provider_markup_percent ?? 0) : 0.0,
            'currency' => $fee?->currency,
            'tax' => 0.0, // no tax-rate configuration exists anywhere in this platform
            'exempt' => $exempt,
            'exemption_reason' => $exemption?->reason,
            'fee_payer' => ($fee?->fee_payer ?? FeePayer::Customer)->value,
            'calculated_fee' => round($fee_, 2),
            'amount_plus_fee' => round($amount + $fee_, 2),
            'amount_minus_fee' => round($amount - $fee_, 2),
        ];
    }
}
