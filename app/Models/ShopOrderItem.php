<?php

namespace App\Models;

use App\Enums\ShopOrderItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShopOrderItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'delivered' => 'array',
            'status' => ShopOrderItemStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'shop_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ShopVariant::class, 'shop_variant_id');
    }

    public function esimProvisioning(): HasOne
    {
        return $this->hasOne(\App\Models\EsimProvisioning::class);
    }
}
