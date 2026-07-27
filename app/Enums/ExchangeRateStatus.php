<?php

namespace App\Enums;

/**
 * Computed display status for a rate row — never stored. Derived from
 * is_active, rate_source, staleness, and any due/pending schedule.
 */
enum ExchangeRateStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Scheduled = 'scheduled';
    case Outdated = 'outdated';
    case ProviderUnavailable = 'provider_unavailable';
    case RequiresReview = 'requires_review';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Scheduled => 'Scheduled',
            self::Outdated => 'Outdated',
            self::ProviderUnavailable => 'Provider unavailable',
            self::RequiresReview => 'Requires review',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Inactive => 'bg-gray-400/15 text-gray-600 ring-1 ring-gray-400/30',
            self::Scheduled => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::Outdated, self::RequiresReview => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::ProviderUnavailable => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
        };
    }
}
