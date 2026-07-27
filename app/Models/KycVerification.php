<?php

namespace App\Models;

use App\Enums\KycPriority;
use App\Enums\KycVerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class KycVerification extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'document_expiry_date' => 'date',
            'reviewed_at' => 'datetime',
            'locked_at' => 'datetime',
            'is_pep' => 'boolean',
            'status' => KycVerificationStatus::class,
            'priority' => KycPriority::class,
            'review_checks' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(KycDecision::class)->latest('created_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(KycNote::class)->latest();
    }

    public function riskFlags(): MorphMany
    {
        return $this->morphMany(RiskFlag::class, 'flaggable');
    }

    public function isLocked(): bool
    {
        return $this->locked_by !== null && $this->locked_at !== null
            && $this->locked_at->gt(now()->subMinutes(15));
    }

    public function lockedByOther(int $userId): bool
    {
        return $this->isLocked() && $this->locked_by !== $userId;
    }

    public function reviewCheck(string $key): array
    {
        return $this->review_checks[$key] ?? ['status' => 'not_checked'];
    }
}
