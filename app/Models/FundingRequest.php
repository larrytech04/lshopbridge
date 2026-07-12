<?php

namespace App\Models;

use App\Enums\AppType;
use App\Enums\FundingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FundingRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'source_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:8',
            'target_amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'total_charged' => 'decimal:2',
            'status' => FundingStatus::class,
            'app_type' => AppType::class,
            'risk_flagged' => 'boolean',
            'meta' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryAccount::class, 'beneficiary_account_id');
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function walletTransactions(): MorphMany
    {
        return $this->morphMany(WalletTransaction::class, 'source');
    }

    public function intents()
    {
        return $this->hasMany(PaymentIntent::class);
    }
}
