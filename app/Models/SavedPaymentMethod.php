<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedPaymentMethod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
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

    /** Never shows the full account reference back — only enough to recognise it. */
    public function maskedAccountRef(): ?string
    {
        if (! $this->account_ref) {
            return null;
        }

        $tail = substr($this->account_ref, -3);

        return str_repeat('•', max(0, strlen($this->account_ref) - 3)).$tail;
    }
}
