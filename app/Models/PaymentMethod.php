<?php

namespace App\Models;

use App\Enums\PaymentMethodStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'is_automated' => 'boolean',
            'requires_proof' => 'boolean',
            'is_active' => 'boolean',
            'status' => PaymentMethodStatus::class,
            'deposit_enabled' => 'boolean',
            'marketplace_enabled' => 'boolean',
            'refund_support' => 'boolean',
            'partial_refund_support' => 'boolean',
            'requires_manual_review' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'countries' => 'array',
            'currencies' => 'array',
            'fields' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        $today = now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('available_from')->orWhere('available_from', '<=', $today))
            ->where(fn ($q) => $q->whereNull('available_until')->orWhere('available_until', '>=', $today))
            ->orderBy('sort');
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

    /** Whether the given user's KYC level meets this method's minimum, if one is set. */
    public function meetsKycRequirement(?User $user): bool
    {
        if ($this->kyc_level_required === null) {
            return true;
        }

        return $user && $user->kyc_level >= $this->kyc_level_required;
    }

    /** An empty/unset "countries" list means globally available — only a real, admin-entered list restricts it. */
    public function isAvailableInCountry(?string $iso2): bool
    {
        if (empty($this->countries)) {
            return true;
        }

        return $iso2 && in_array(strtoupper($iso2), array_map('strtoupper', $this->countries), true);
    }
}
