<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycLevel extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'daily_limit' => 'decimal:2',
            'monthly_limit' => 'decimal:2',
            'per_transaction_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
