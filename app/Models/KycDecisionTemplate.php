<?php

namespace App\Models;

use App\Enums\KycDecisionType;
use Illuminate\Database\Eloquent\Model;

class KycDecisionTemplate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'decision_type' => KycDecisionType::class,
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDecision($query, KycDecisionType|string $type)
    {
        return $query->where('decision_type', $type instanceof KycDecisionType ? $type->value : $type);
    }
}
