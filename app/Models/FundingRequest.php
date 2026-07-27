<?php

namespace App\Models;

use App\Enums\AppType;
use App\Enums\FundingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FundingRequest extends Model
{
    use HasFactory;

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
            'flagged_for_investigation' => 'boolean',
            'fee_snapshot' => 'array',
            'meta' => 'array',
            'processed_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function feeRule(): BelongsTo
    {
        return $this->belongsTo(Fee::class, 'fee_id');
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function walletTransactions(): MorphMany
    {
        return $this->morphMany(WalletTransaction::class, 'source');
    }

    public function intents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(FundingEvent::class)->latest('created_at');
    }

    public function riskFlags(): MorphMany
    {
        return $this->morphMany(RiskFlag::class, 'flaggable');
    }

    public function disputes(): MorphMany
    {
        return $this->morphMany(Dispute::class, 'subject_ref');
    }

    public function webhookEvents(): MorphMany
    {
        return $this->morphMany(WebhookEvent::class, 'related');
    }
}
