<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The real fulfilment record for one eSIM order item. `provider` is
 * "manual" until a connected EsimProviderConnector can fulfil automatically
 * — see App\Services\Esim\EsimOrderService. Sensitive fields are encrypted
 * at rest (Laravel's native encrypted cast, same pattern as
 * ImportSource::credentials) and are only ever exposed through the
 * owner-gated EsimDeliveryController, never a public URL or plain log line.
 */
class EsimProvisioning extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'iccid' => 'encrypted',
            'activation_code' => 'encrypted',
            'sm_dp_address' => 'encrypted',
            'confirmation_code' => 'encrypted',
            'lpa_string' => 'encrypted',
            'direct_install_url' => 'encrypted',
            'compatibility_confirmed' => 'boolean',
            'compatibility_confirmed_at' => 'datetime',
            'installation_deadline_at' => 'datetime',
            'installed_at' => 'datetime',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'first_qr_reveal_at' => 'datetime',
            'last_qr_reveal_at' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(ShopOrderItem::class, 'shop_order_item_id');
    }

    public function usageSnapshots(): HasMany
    {
        return $this->hasMany(EsimUsageSnapshot::class);
    }

    public function topups(): HasMany
    {
        return $this->hasMany(EsimTopup::class);
    }

    public function latestUsage(): ?EsimUsageSnapshot
    {
        return $this->usageSnapshots()->latest('fetched_at')->first();
    }

    public function hasWorkingActivationData(): bool
    {
        return filled($this->lpa_string) || (filled($this->sm_dp_address) && filled($this->activation_code));
    }

    public function recordQrReveal(): void
    {
        $this->update([
            'first_qr_reveal_at' => $this->first_qr_reveal_at ?? now(),
            'last_qr_reveal_at' => now(),
            'qr_reveal_count' => $this->qr_reveal_count + 1,
        ]);
    }
}
