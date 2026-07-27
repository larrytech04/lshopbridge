<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeHistory extends Model
{
    const UPDATED_AT = null;

    protected $table = 'fee_history';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'min_fee' => 'decimal:2',
            'max_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
