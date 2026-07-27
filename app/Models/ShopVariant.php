<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopVariant extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'denomination' => 'decimal:2',
            'is_active' => 'boolean',
            'is_unlimited_data' => 'boolean',
            'network_speeds' => 'array',
            'networks' => 'array',
            'hotspot_supported' => 'boolean',
            'voice_supported' => 'boolean',
            'sms_supported' => 'boolean',
            'topup_supported' => 'boolean',
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

    public function isLowStock(): bool
    {
        return $this->stock !== null && $this->low_stock_threshold !== null && $this->stock <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock !== null && $this->stock <= 0;
    }

    public function saleIsActive(): bool
    {
        if ($this->sale_price === null) {
            return false;
        }

        $now = now();

        return ($this->sale_starts_at === null || $this->sale_starts_at->lte($now))
            && ($this->sale_ends_at === null || $this->sale_ends_at->gte($now));
    }

    public function effectivePrice(): float
    {
        return $this->saleIsActive() ? (float) $this->sale_price : (float) $this->price;
    }

    public function profitAmount(): ?float
    {
        return $this->cost_price !== null ? round($this->effectivePrice() - (float) $this->cost_price, 2) : null;
    }

    public function profitMarginPercent(): ?float
    {
        $price = $this->effectivePrice();

        return ($this->cost_price !== null && $price > 0) ? round((($price - (float) $this->cost_price) / $price) * 100, 2) : null;
    }
}
