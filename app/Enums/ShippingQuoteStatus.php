<?php

namespace App\Enums;

enum ShippingQuoteStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Accepted => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Pending => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Rejected, self::Withdrawn => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
        };
    }
}
