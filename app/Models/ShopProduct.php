<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShopProduct extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_best_deal' => 'boolean',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ShopProduct $p) {
            $p->slug ??= Str::slug($p->name).'-'.Str::lower(Str::random(4));
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ShopVariant::class)->orderBy('sort')->orderBy('price');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function fromPrice(): ?ShopVariant
    {
        return $this->variants->where('is_active', true)->sortBy('price')->first();
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
