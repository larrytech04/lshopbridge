<?php

namespace App\Models;

use App\Enums\CountryLaunchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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
