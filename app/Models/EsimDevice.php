<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsimDevice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'model_variants' => 'array',
            'esim_supported' => 'boolean',
            'dual_sim_support' => 'boolean',
            'verified_date' => 'date',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
