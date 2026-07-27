<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatus::class,
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'pin_confirmed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function savedPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(SavedPaymentMethod::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function maskedDestinationRef(): ?string
    {
        if (! $this->destination_account_ref) {
            return null;
        }

        $tail = substr($this->destination_account_ref, -3);

        return str_repeat('•', max(0, strlen($this->destination_account_ref) - 3)).$tail;
    }
}
