<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /** Ordered category groups for the /legal hub — matches the Legal Center spec's grouping. */
    public const CATEGORIES = [
        'general' => 'General',
        'money' => 'Money & Payments',
        'marketplace' => 'Marketplace',
        'shipping' => 'Shipping & Agents',
        'identity' => 'Identity & Security',
        'programs' => 'Programs & Communication',
        'company' => 'Company',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'last_reviewed_at' => 'datetime',
            'effective_date' => 'date',
            'applicable_services' => 'array',
            'applicable_countries' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->latest('version');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeLegal(Builder $query): Builder
    {
        return $query->where('type', 'legal');
    }

    /**
     * Whether this policy should be shown given the platform's currently
     * active services (e.g. route names that exist). Null/empty
     * applicable_services means the policy applies platform-wide.
     */
    public function isApplicableToServices(array $activeServiceKeys): bool
    {
        if (empty($this->applicable_services)) {
            return true;
        }

        return count(array_intersect($this->applicable_services, $activeServiceKeys)) > 0;
    }
}
