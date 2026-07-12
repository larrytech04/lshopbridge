<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'is_automated' => 'boolean',
            'requires_proof' => 'boolean',
            'is_active' => 'boolean',
            'countries' => 'array',
            'fields' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort');
    }

    public function provider(): ?PaymentProvider
    {
        return $this->provider_code
            ? PaymentProvider::where('code', $this->provider_code)->first()
            : null;
    }
}
