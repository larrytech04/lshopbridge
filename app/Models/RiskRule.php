<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
