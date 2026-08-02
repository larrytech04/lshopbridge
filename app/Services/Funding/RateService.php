<?php

namespace App\Services\Funding;

use App\Models\ExchangeRate;
use App\Models\ExchangeRateSchedule;

/**
 * Resolves the admin-managed exchange rate (including any configured margin)
 * for a currency pair. Admin sets live manual rates from the panel.
 *
 * The only two places rate math happens in this app: ExchangeRate::effectiveRate()
 * (the live/scheduled row) and this service's rate()/quote() (what callers use).
 * The admin calculator, FundingService::quote(), and the public marketing pages
 * all resolve through this same service, so there is exactly one exchange-rate
 * formula in the codebase, not one per screen.
 */
class RateService
{
    public function rate(?string $base = null, ?string $quote = null): float
    {
        $base ??= config('platform.base_currency', 'XAF');
        $quote ??= config('platform.target_currency', 'CNY');

        if ($due = $this->dueSchedule($base, $quote)) {
            return $due->effectiveRate();
        }

        $rate = ExchangeRate::where('base_currency', $base)
            ->where('quote_currency', $quote)
            ->where('is_active', true)
            ->first();

        if ($rate) {
            return $rate->effectiveRate();
        }

        // Fallback: admin-set default in settings, else 1.0 (never silently wrong in prod).
        return (float) setting("rate_{$base}_{$quote}", 0) ?: 1.0;
    }

    /**
     * Full quote breakdown for the admin calculator (and reusable anywhere else
     * that needs a currency-conversion preview) — the same primitives real
     * transactions use, just exposed with the intermediate figures shown.
     *
     * @return array{base_currency:string, quote_currency:string, source_amount:float,
     *   base_rate:float, effective_rate:float, base_conversion:float,
     *   margin_amount:float, additional_fee:float, delivered_amount:float,
     *   rate_updated_at:?string, rate_available:bool}
     */
    public function quote(float $amount, ?string $base = null, ?string $quote = null, float $additionalFee = 0): array
    {
        $base ??= config('platform.base_currency', 'XAF');
        $quote ??= config('platform.target_currency', 'CNY');

        $due = $this->dueSchedule($base, $quote);
        $row = ExchangeRate::where('base_currency', $base)->where('quote_currency', $quote)->where('is_active', true)->first();

        $baseRate = $due?->rate ?? $row?->rate ?? (float) setting("rate_{$base}_{$quote}", 0) ?: 1.0;
        $effectiveRate = $due?->effectiveRate() ?? $row?->effectiveRate() ?? (float) $baseRate;

        $baseConversion = $amount * (float) $baseRate;
        $deliveredAmount = ($amount * $effectiveRate) - $additionalFee;

        return [
            'base_currency' => $base,
            'quote_currency' => $quote,
            'source_amount' => $amount,
            'base_rate' => (float) $baseRate,
            'effective_rate' => (float) $effectiveRate,
            'base_conversion' => round($baseConversion, 8),
            'margin_amount' => round($baseConversion - ($amount * $effectiveRate), 8),
            'additional_fee' => $additionalFee,
            'delivered_amount' => round($deliveredAmount, 2),
            'rate_updated_at' => ($due?->updated_at ?? $row?->updated_at)?->toIso8601String(),
            'rate_available' => $due !== null || $row !== null,
        ];
    }

    /** Any schedule whose effective window has arrived for this pair, if one exists. */
    public function dueSchedule(string $base, string $quote): ?ExchangeRateSchedule
    {
        return ExchangeRateSchedule::where('base_currency', $base)
            ->where('quote_currency', $quote)
            ->where('status', 'scheduled')
            ->whereDate('effective_from', '<=', now())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', now()))
            ->orderByDesc('effective_from')
            ->first();
    }

    /** The next not-yet-due schedule for a pair, for display purposes. */
    public function upcomingSchedule(string $base, string $quote): ?ExchangeRateSchedule
    {
        return ExchangeRateSchedule::where('base_currency', $base)
            ->where('quote_currency', $quote)
            ->where('status', 'scheduled')
            ->whereDate('effective_from', '>', now())
            ->orderBy('effective_from')
            ->first();
    }
}
