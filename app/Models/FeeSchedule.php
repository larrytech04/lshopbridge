<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeSchedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'new_value' => 'decimal:4',
            'new_min_fee' => 'decimal:2',
            'new_max_fee' => 'decimal:2',
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDue(): bool
    {
        return $this->status === 'scheduled'
            && $this->effective_start_date->lte(now())
            && ($this->effective_end_date === null || $this->effective_end_date->gte(now()));
    }

    public function isExpired(): bool
    {
        return $this->effective_end_date !== null && $this->effective_end_date->lt(now());
    }
}
