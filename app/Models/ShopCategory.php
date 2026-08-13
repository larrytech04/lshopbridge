<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShopCategory extends Model
{
    // Deliberately NOT HasSeoMetadata — already has native seo_title/
    // meta_description/canonical_url columns with a real admin form (see
    // Page.php's docblock for the same reasoning).
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'featured' => 'boolean',
            'menu_visible' => 'boolean',
            'restricted_countries' => 'array',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (ShopCategory $c) => $c->slug ??= Str::slug($c->name));
    }

    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    public function defaultFee(): BelongsTo
    {
        return $this->belongsTo(Fee::class, 'default_fee_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort');
    }

    public function scopeTopLevel($q)
    {
        return $q->whereNull('parent_id');
    }

    /**
     * Real enforcement for the Marketplace mega-menu and sidebar: active,
     * explicitly opted into menu navigation, and inside its availability
     * window. Country restriction is applied separately via
     * isAvailableForCountry() since it needs a request-time ISO code, not
     * something a query scope alone can resolve.
     */
    public function scopeVisibleInNavigation($q)
    {
        $now = now();

        return $q->where('is_active', true)
            ->where('menu_visible', true)
            ->where(fn ($sub) => $sub->whereNull('available_from')->orWhere('available_from', '<=', $now))
            ->where(fn ($sub) => $sub->whereNull('available_until')->orWhere('available_until', '>=', $now))
            ->orderBy('sort');
    }

    /** restricted_countries is a deny-list: null/empty means available everywhere. */
    public function isAvailableForCountry(?string $iso): bool
    {
        if (empty($this->restricted_countries)) {
            return true;
        }

        if (! $iso) {
            return true;
        }

        return ! in_array(strtoupper($iso), array_map('strtoupper', $this->restricted_countries), true);
    }

    public function isCurrentlyAvailable(): bool
    {
        $now = now();

        if ($this->available_from && $this->available_from->isFuture()) {
            return false;
        }
        if ($this->available_until && $this->available_until->isPast()) {
            return false;
        }

        return true;
    }

    /** Root-first ancestor trail, including this category. */
    public function breadcrumb(): array
    {
        $trail = [$this];
        $node = $this;

        while ($node->parent) {
            $node = $node->parent;
            array_unshift($trail, $node);
        }

        return $trail;
    }
}
