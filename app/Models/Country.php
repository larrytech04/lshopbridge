<?php

namespace App\Models;

use App\Enums\CountryLaunchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Country extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_blocked' => 'boolean',
            'launch_status' => CountryLaunchStatus::class,
        ];
    }

    protected static function booted(): void
    {
        // Same convention as Agent/ShopProduct: any creation path (factory,
        // admin form, tinker, a future seeder) gets a slug automatically —
        // not just the ones that happen to go through CountryFactory. Keeps
        // the NOT NULL/unique slug column (added for country landing pages)
        // from ever being a surprise for code that predates it.
        static::creating(function (Country $country) {
            $country->slug ??= Str::slug($country->name).'-'.Str::lower(Str::random(5));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function momoNumbers(): HasMany
    {
        return $this->hasMany(MomoNumber::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort')->orderBy('name');
    }

    /**
     * Whether this country has real, admin-configured payment
     * infrastructure (at least one active Mobile Money number) — the
     * signal a country page uses to decide whether it has enough genuine,
     * distinct content to be indexable at all (see brief section 6: only
     * create indexable pages when they provide distinct, meaningful
     * information). is_active/launch_status alone just mean "allowed to
     * register from here", not "we have real local payment rails yet".
     */
    public function hasRealPaymentInfrastructure(): bool
    {
        return $this->momoNumbers()->where('is_active', true)->exists();
    }

    /**
     * Keeps the legacy is_active/is_blocked booleans in sync with the richer
     * launch_status so every existing consumer of those flags (RiskEngine,
     * scopeActive, etc.) keeps working unchanged.
     */
    public function syncLegacyStatusFlags(): void
    {
        $status = $this->launch_status instanceof CountryLaunchStatus
            ? $this->launch_status
            : CountryLaunchStatus::from($this->launch_status ?? 'active');

        $this->is_active = $status->isActive();
        $this->is_blocked = $status === CountryLaunchStatus::Restricted;
    }
}
