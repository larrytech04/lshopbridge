<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeExemption extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'applicable_services' => 'array',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCurrentlyEffective(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->toDateString();

        return $this->start_date->toDateString() <= $today
            && ($this->end_date === null || $this->end_date->toDateString() >= $today);
    }

    public function appliesToService(string $appliesTo): bool
    {
        return empty($this->applicable_services) || in_array($appliesTo, $this->applicable_services, true);
    }
}
