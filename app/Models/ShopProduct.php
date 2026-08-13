<?php

namespace App\Models;

use App\Enums\ShopProductStatus;
use App\Enums\ShopProductType;
use App\Models\Concerns\HasSeoMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShopProduct extends Model
{
    use HasFactory, HasSeoMetadata, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => ShopProductType::class,
            'status' => ShopProductStatus::class,
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_best_deal' => 'boolean',
            'scheduled_publish_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'meta' => 'array',
            'esim_coverage_countries' => 'array',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function importSource(): BelongsTo
    {
        return $this->belongsTo(ImportSource::class);
    }

    public function productImport(): BelongsTo
    {
        return $this->belongsTo(ProductImport::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    public function isImported(): bool
    {
        return $this->source !== 'native';
    }
}
