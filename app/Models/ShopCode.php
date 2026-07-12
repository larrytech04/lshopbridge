<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopCode extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_used' => 'boolean', 'used_at' => 'datetime'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ShopVariant::class, 'shop_variant_id');
    }
}
