<?php

namespace App\Models;

use App\Enums\ExchangeRateMarginType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRateHistory extends Model
{
    protected $table = 'exchange_rate_history';

    protected $guarded = [];

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'margin_percent' => 'decimal:4',
            'margin_fixed' => 'decimal:8',
            'custom_effective_rate' => 'decimal:8',
            'effective_rate' => 'decimal:8',
            'margin_type' => ExchangeRateMarginType::class,
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function exchangeRate(): BelongsTo
    {
        return $this->belongsTo(ExchangeRate::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
