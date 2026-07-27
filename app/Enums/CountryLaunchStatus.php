<?php

namespace App\Enums;

enum CountryLaunchStatus: string
{
    case Active = 'active';
    case ComingSoon = 'coming_soon';
    case Restricted = 'restricted';
    case Maintenance = 'maintenance';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::ComingSoon => 'Coming soon',
            self::Restricted => 'Restricted',
            self::Maintenance => 'Maintenance',
            self::Disabled => 'Disabled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::ComingSoon => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::Restricted => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Maintenance => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Disabled => 'bg-gray-400/15 text-gray-600 ring-1 ring-gray-400/30',
        };
    }

    /** Whether this status should keep the legacy is_active flag true. */
    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
