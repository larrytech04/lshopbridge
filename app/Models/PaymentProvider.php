<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProvider extends Model
{
    protected $guarded = [];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'supports' => 'array',
            'meta' => 'array',
            'credentials' => 'encrypted:array', // admin-entered API keys (never plain in DB)
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Non-empty admin-entered credentials, used to override env config. */
    public function overrides(): array
    {
        return array_filter($this->credentials ?? [], fn ($v) => $v !== null && $v !== '');
    }
}
