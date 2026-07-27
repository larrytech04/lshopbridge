<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    /** Where "view details" should send the customer, when the source record has its own page. */
    public function sourceUrl(): ?string
    {
        if (! $this->source) {
            return null;
        }

        return match ($this->source_type) {
            \App\Models\ShopOrder::class => route('shop.orders.show', $this->source),
            \App\Models\FundingRequest::class => route('funding.show', $this->source),
            \App\Models\Deposit::class => route('deposit.show', $this->source),
            \App\Models\WithdrawalRequest::class => route('withdrawals.index'),
            \App\Models\ShippingRequest::class => route('shipping-requests.show', $this->source),
            default => null,
        };
    }
}
