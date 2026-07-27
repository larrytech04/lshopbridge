<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SpamReviewCase extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'triggered_rules' => 'array',
            'safe_payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $case) {
            $case->reference ??= 'SRC-'.strtoupper(Str::random(8));
        });
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }
}
