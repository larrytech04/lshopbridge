<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopVariant extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'denomination' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(ShopCode::class);
    }

    public function inStock(int $qty = 1): bool
    {
        if ($this->stock === null) {
            return true; // unlimited / auto-generated
        }

        return $this->stock >= $qty;
    }
}
