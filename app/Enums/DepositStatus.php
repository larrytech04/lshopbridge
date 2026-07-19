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

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::UnderReview => 'Under review',
            self::Processing => 'Processing',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Rejected',
            self::Failed => 'Failed',
        };
    }

    /** Tailwind pill classes for the glass UI. */
    public function color(): string
    {
        return match ($this) {
            self::Confirmed => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Processing => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::UnderReview => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Pending => 'bg-slate-400/15 text-slate-600 ring-1 ring-slate-400/30',
            self::Rejected, self::Failed => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
        };
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Confirmed, self::Rejected, self::Failed], true);
    }
}
