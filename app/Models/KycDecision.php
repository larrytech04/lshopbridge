<?php

namespace App\Models;

use App\Enums\KycDecisionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDecision extends Model
{
    protected $guarded = [];

    /** Immutable log — no updated_at, rows are never edited. */
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'decision_type' => KycDecisionType::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function kycVerification(): BelongsTo
    {
        return $this->belongsTo(KycVerification::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function reasonTemplate(): BelongsTo
    {
        return $this->belongsTo(KycDecisionTemplate::class, 'reason_template_id');
    }
}
