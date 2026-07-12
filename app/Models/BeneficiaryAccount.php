<?php

namespace App\Models;

use App\Enums\AppType;
use App\Enums\BeneficiaryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryAccount extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'app_type' => AppType::class,
            'status' => BeneficiaryStatus::class,
            'is_default' => 'boolean',
            'meta' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === BeneficiaryStatus::Approved;
    }
}
