<?php

namespace App\Enums;

enum ShopOrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Fulfilled = 'fulfilled';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Paid => 'Paid · delivering',
            self::Fulfilled => 'Delivered',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Fulfilled => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Paid => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::Pending => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Failed => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
            self::Refunded => 'bg-violet-500/15 text-violet-600 ring-1 ring-violet-400/30',
        };
    }
}
