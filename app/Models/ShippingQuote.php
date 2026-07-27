<?php

namespace App\Models;

use App\Enums\ShippingQuoteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingQuote extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ShippingQuoteStatus::class,
            'price' => 'decimal:2',
        ];
    }

    public function shippingRequest(): BelongsTo
    {
        return $this->belongsTo(ShippingRequest::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
