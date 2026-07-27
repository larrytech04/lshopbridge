<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReferralLead extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $lead) {
            $lead->reference ??= 'PB-REF-'.strtoupper(Str::random(6));
        });
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function contactedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_by');
    }
}
