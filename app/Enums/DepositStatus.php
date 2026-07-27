<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Processing = 'processing';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::UnderReview => 'Under review',
            self::Processing => 'Processing',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Rejected',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
            self::Reversed => 'Reversed',
        };
    }

    /** Tailwind pill classes for the glass UI. */
    public function color(): string
    {
        return match ($this) {
            self::Confirmed => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Processing => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::UnderReview, self::Pending => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Rejected, self::Failed => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
            self::Reversed => 'bg-purple-500/15 text-purple-600 ring-1 ring-purple-400/30',
            self::Cancelled => 'bg-gray-400/15 text-gray-600 ring-1 ring-gray-400/30',
            self::Refunded => 'bg-teal-500/15 text-teal-600 ring-1 ring-teal-400/30',
        };
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Confirmed, self::Rejected, self::Failed, self::Cancelled, self::Refunded, self::Reversed], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::UnderReview, self::Processing], true);
    }

    /** Only a Confirmed deposit has ever had a wallet credit that could later be undone. */
    public function canBeRefundedOrReversed(): bool
    {
        return $this === self::Confirmed;
    }
}
