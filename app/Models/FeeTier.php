<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeTier extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'percent' => 'decimal:4',
            'fixed' => 'decimal:2',
        ];
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }
}
