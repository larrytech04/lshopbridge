<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EsimTopup extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_unlimited_data' => 'boolean',
            'price' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function provisioning(): BelongsTo
    {
        return $this->belongsTo(EsimProvisioning::class, 'esim_provisioning_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'shop_order_id');
    }
}
