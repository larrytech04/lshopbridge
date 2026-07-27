<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Processing = 'processing';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Approved => 'Approved',
            self::Processing => 'Processing',
            self::Paid => 'Paid',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Paid => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Approved, self::Processing => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::Pending => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Rejected, self::Cancelled => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Rejected, self::Cancelled], true);
    }
}
