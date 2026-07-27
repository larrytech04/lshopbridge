<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EsimUsageSnapshot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['fetched_at' => 'datetime'];
    }

    public function provisioning(): BelongsTo
    {
        return $this->belongsTo(EsimProvisioning::class, 'esim_provisioning_id');
    }

    public function percentUsed(): ?float
    {
        if (! $this->total_mb) {
            return null;
        }

        return round((($this->used_mb ?? 0) / $this->total_mb) * 100, 1);
    }
}
