<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadMessage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_agent' => 'boolean'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(AgentLead::class, 'agent_lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
