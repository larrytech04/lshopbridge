<?php

namespace App\Models;

use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\Concerns\HasSeoMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasFactory, HasSeoMetadata, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cities' => 'array',
            'shipping_methods' => 'array',
            'status' => AgentStatus::class,
            'agent_type' => AgentType::class,
            'rating' => 'decimal:2',
            'is_featured' => 'boolean',
            'verified_at' => 'datetime',
            'featured_from' => 'date',
            'featured_until' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Agent $agent) {
            $agent->slug ??= Str::slug($agent->business_name).'-'.Str::lower(Str::random(5));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOnline(): bool
    {
        return $this->user?->isOnline() ?? false;
    }

    public function warehouseCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'warehouse_country_id');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }

    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(AgentLead::class);
    }

    public function shippingQuotes(): HasMany
    {
        return $this->hasMany(ShippingQuote::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', AgentStatus::Approved->value);
    }

    public function scopeFeaturedActive($query)
    {
        return $query->where('is_featured', true)
            ->where(fn ($q) => $q->whereNull('featured_from')->orWhereDate('featured_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('featured_until')->orWhereDate('featured_until', '>=', now()));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function recalculateRating(): void
    {
        $approved = $this->reviews()->where('status', 'approved');
        $this->update([
            'rating' => round((float) $approved->avg('rating'), 2),
            'reviews_count' => $approved->count(),
        ]);
    }

    public function successRate(): ?float
    {
        $completed = $this->leads()->where('status', 'completed')->count();
        $closed = $this->leads()->where('status', 'closed')->count();
        $total = $completed + $closed;

        return $total > 0 ? round(($completed / $total) * 100, 1) : null;
    }
}
