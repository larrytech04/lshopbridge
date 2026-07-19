<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentLead extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'customer_confirmed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LeadMessage::class)->orderBy('created_at');
    }

    /** Idempotent: awards the agent completion reputation exactly once, however completion was triggered. */
    public function markCompleted(): void
    {
        if ($this->status !== 'completed') {
            $this->agent->increment('completed_orders');
            $this->agent->increment('points', 10);
        }

        $this->status = 'completed';
        $this->save();
    }
}
