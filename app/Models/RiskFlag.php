<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RiskFlag extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['context' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flaggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
