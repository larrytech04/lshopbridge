<?php

namespace App\Models;

use App\Enums\ShippingRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ShippingRequestStatus::class,
            'documents' => 'array',
            'package_weight_kg' => 'decimal:2',
            'package_value' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'delivered_at' => 'datetime',
            'customer_viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(ShippingQuote::class);
    }

    public function acceptedQuote(): BelongsTo
    {
        return $this->belongsTo(ShippingQuote::class, 'accepted_quote_id');
    }
}
