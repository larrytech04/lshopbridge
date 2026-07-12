<?php

namespace App\Services\Funding;

use App\Models\ExchangeRate;

/**
 * Resolves the admin-managed exchange rate (including any configured margin)
 * for a currency pair. Admin sets live manual rates from the panel.
 */
class RateService
{
    public function rate(?string $base = null, ?string $quote = null): float
    {
        $base ??= config('platform.base_currency', 'XAF');
        $quote ??= config('platform.target_currency', 'CNY');

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
}
