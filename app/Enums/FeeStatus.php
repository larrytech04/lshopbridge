<?php

namespace App\Enums;

/**
 * Computed display status for a fee row — never stored. Derived from
 * is_active, under_review, effective date window, and any upcoming schedule.
 */
enum FeeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Scheduled = 'scheduled';
    case Expired = 'expired';
    case UnderReview = 'under_review';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Scheduled => 'Scheduled',
            self::Expired => 'Expired',
            self::UnderReview => 'Under review',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Inactive, self::Archived => 'bg-gray-400/15 text-gray-600 ring-1 ring-gray-400/30',
            self::Scheduled => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::UnderReview => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Expired => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
        };
    }
}
