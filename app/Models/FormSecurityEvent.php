<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FormSecurityEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'triggered_rules' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            $event->reference ??= 'FSE-'.strtoupper(Str::random(8));
        });
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function related(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function scopeRiskLevel($query, string $level)
    {
        return $query->where('risk_level', $level);
    }
}
