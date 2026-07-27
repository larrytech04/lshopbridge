<?php

namespace App\Models;

use App\Enums\ExchangeRateMarginType;
use App\Enums\ExchangeRateSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'margin_percent' => 'decimal:4',
            'margin_fixed' => 'decimal:8',
            'custom_effective_rate' => 'decimal:8',
            'margin_type' => ExchangeRateMarginType::class,
            'rate_source' => ExchangeRateSource::class,
            'is_active' => 'boolean',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ExchangeRateHistory::class)->latest('created_at');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExchangeRateSchedule::class, 'base_currency', 'base_currency')
            ->where('quote_currency', $this->quote_currency ?? '');
    }

    public function pair(): string
    {
        return "{$this->base_currency} → {$this->quote_currency}";
    }

    /** Effective rate including the admin margin/spread, honoring the selected margin type. */
    public function effectiveRate(): float
    {
        return self::computeEffectiveRate(
            (float) $this->rate,
            $this->margin_type ?? ExchangeRateMarginType::Percentage,
            (float) $this->margin_percent,
            $this->margin_fixed !== null ? (float) $this->margin_fixed : null,
            $this->custom_effective_rate !== null ? (float) $this->custom_effective_rate : null,
        );
    }

    public static function computeEffectiveRate(float $rate, ExchangeRateMarginType $type, float $marginPercent = 0, ?float $marginFixed = null, ?float $customEffectiveRate = null): float
    {
        return match ($type) {
            ExchangeRateMarginType::Percentage => $rate * (1 - ($marginPercent / 100)),
            ExchangeRateMarginType::Fixed => $rate - (float) $marginFixed,
            ExchangeRateMarginType::Custom => (float) $customEffectiveRate,
        };
    }
}
