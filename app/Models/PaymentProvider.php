<?php

namespace App\Models;

use App\Enums\ProviderConnectionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'supports' => 'array',
            'meta' => 'array',
            'countries' => 'array',
            'currencies' => 'array',
            'priority' => 'integer',
            'last_tested_at' => 'datetime',
            'last_test_ok' => 'boolean',
            'credentials' => 'encrypted:array', // admin-entered API keys (never plain in DB)
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('priority');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Non-empty admin-entered credentials, used to override env config. */
    public function overrides(): array
    {
        return array_filter($this->credentials ?? [], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Honest, derived connection status. Never fabricated: driven only by
     * is_active, whether any credentials have been entered, and the result
     * of the last real testConnection() call (last_test_ok/last_tested_at).
     */
    public function connectionStatus(): ProviderConnectionStatus
    {
        if (! $this->is_active) {
            return ProviderConnectionStatus::Disabled;
        }

        if ($this->overrides() === [] && $this->mode === 'sandbox') {
            return ProviderConnectionStatus::NotConfigured;
        }

        if ($this->last_tested_at === null) {
            return ProviderConnectionStatus::NotConfigured;
        }

        if ($this->last_test_ok === false) {
            return str_contains((string) $this->last_test_message, 'auth')
                ? ProviderConnectionStatus::AuthenticationFailed
                : ProviderConnectionStatus::ProviderOffline;
        }

        return ProviderConnectionStatus::Connected;
    }
}
