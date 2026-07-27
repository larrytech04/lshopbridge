<?php

namespace App\Models;

use App\Enums\DepositStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Deposit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'status' => DepositStatus::class,
            'is_automated' => 'boolean',
            'risk_flagged' => 'boolean',
            'flagged_for_investigation' => 'boolean',
            'payer_details' => 'array',
            'fee_snapshot' => 'array',
            'meta' => 'array',
            'confirmed_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function feeRule(): BelongsTo
    {
        return $this->belongsTo(Fee::class, 'fee_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
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
        return $this->hasMany(DepositEvent::class)->latest('created_at');
    }

    public function riskFlags(): MorphMany
    {
        return $this->morphMany(RiskFlag::class, 'flaggable');
    }

    /** Disputes a customer raised specifically about this deposit. */
    public function disputes(): MorphMany
    {
        return $this->morphMany(Dispute::class, 'subject_ref');
    }

    public function webhookEvents(): MorphMany
    {
        return $this->morphMany(WebhookEvent::class, 'related');
    }
}
