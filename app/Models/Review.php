<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'is_guest' => 'boolean'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewerName(): string
    {
        return $this->is_guest ? ($this->guest_name ?: 'Guest') : ($this->user->name ?? 'Unknown');
    }
}
