<?php

namespace App\Models;

use App\Enums\ExchangeRateMarginType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRateSchedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'margin_percent' => 'decimal:4',
            'margin_fixed' => 'decimal:8',
            'custom_effective_rate' => 'decimal:8',
            'margin_type' => ExchangeRateMarginType::class,
            'effective_from' => 'date',
            'effective_to' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function effectiveRate(): float
    {
        return ExchangeRate::computeEffectiveRate(
            (float) $this->rate,
            $this->margin_type,
            (float) $this->margin_percent,
            $this->margin_fixed !== null ? (float) $this->margin_fixed : null,
            $this->custom_effective_rate !== null ? (float) $this->custom_effective_rate : null,
        );
    }

    public function isDue(): bool
    {
        return $this->status === 'scheduled'
            && $this->effective_from->lte(now())
            && ($this->effective_to === null || $this->effective_to->gte(now()));
    }

    public function isExpired(): bool
    {
        return $this->effective_to !== null && $this->effective_to->lt(now());
    }
}
