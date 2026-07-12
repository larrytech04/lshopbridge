<?php

namespace App\Models;

use App\Enums\ShopOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopOrder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ShopOrderStatus::class,
            'subtotal' => 'decimal:2',
            'fee' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}
