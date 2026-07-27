<?php

namespace App\Models;

use App\Enums\ShopOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopOrder extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ShopOrderStatus::class,
            'subtotal' => 'decimal:2',
            'fee' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'risk_flagged' => 'boolean',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShopOrderEvent::class)->orderBy('created_at');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(ShopRefund::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function totalRefunded(): float
    {
        return (float) $this->refunds()->where('status', 'completed')->sum('amount');
    }

    public function refundableAmount(): float
    {
        return max(0.0, round((float) $this->total - $this->totalRefunded(), 2));
    }

    public function hasPendingRefundRequest(): bool
    {
        return $this->refunds()->where('status', 'requested')->exists();
    }

    /**
     * Whether the customer themself can still ask for a refund: paid, still
     * has a refundable balance, no request already awaiting review, and
     * inside the admin-configured eligibility window (days since payment).
     */
    public function isRefundEligibleByCustomer(): bool
    {
        if (! $this->paid_at || $this->refundableAmount() <= 0 || $this->hasPendingRefundRequest()) {
            return false;
        }

        $windowDays = (int) setting('refund_window_days', 14);

        return $this->paid_at->diffInDays(now()) <= $windowDays;
    }
}
