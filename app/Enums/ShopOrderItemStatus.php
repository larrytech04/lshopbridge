<?php

namespace App\Enums;

enum ShopOrderItemStatus: string
{
    case Pending = 'pending';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    // Paid, but no working provider/staff fulfilment exists yet — see
    // EsimOrderService. Never silently treated as Fulfilled.
    case PendingProvisioning = 'pending_provisioning';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Fulfilled => 'Fulfilled',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
            self::PendingProvisioning => 'Pending Provisioning',
        };
    }
}
