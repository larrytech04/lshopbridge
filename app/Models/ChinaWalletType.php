<?php

namespace App\Models;

use App\Enums\WalletIdentifierType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuration (limits, identifier requirements, instructions) for the
 * wallet types this platform already delivers to (alipay, wechat, other —
 * matching App\Enums\AppType). This model does not let an admin add a new
 * payment rail; it only configures the existing fixed set.
 */
class ChinaWalletType extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'account_identifier_type' => WalletIdentifierType::class,
            'qr_required' => 'boolean',
            'account_name_required' => 'boolean',
            'phone_required' => 'boolean',
            'country_restrictions' => 'array',
            'min_kyc_level' => 'integer',
            'min_funding_amount' => 'decimal:2',
            'max_funding_amount' => 'decimal:2',
            'daily_limit' => 'decimal:2',
            'monthly_limit' => 'decimal:2',
            'automated_funding' => 'boolean',
            'manual_funding' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function provider(): ?PaymentProvider
    {
        return $this->provider_code
            ? PaymentProvider::where('code', $this->provider_code)->first()
            : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort');
    }

    public function allowsCountry(?string $iso2): bool
    {
        if (empty($this->country_restrictions) || $iso2 === null) {
            return true;
        }

        return in_array($iso2, $this->country_restrictions, true);
    }
}
