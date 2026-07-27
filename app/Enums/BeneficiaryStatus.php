<?php

namespace App\Enums;

enum BeneficiaryStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case MoreInfoRequested = 'more_info_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InReview => 'Under Review',
            self::MoreInfoRequested => 'More Information Required',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Approved => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Pending => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::MoreInfoRequested => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::InReview => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::Rejected, self::Suspended => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::InReview, self::MoreInfoRequested], true);
    }
}
