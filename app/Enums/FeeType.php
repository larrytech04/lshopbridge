<?php

namespace App\Enums;

enum FeeType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case FixedPlusPercent = 'fixed_plus_percent';
    case Tiered = 'tiered';
    case ProviderPassed = 'provider_passed';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Percentage',
            self::Fixed => 'Fixed',
            self::FixedPlusPercent => 'Fixed + percentage',
            self::Tiered => 'Tiered',
            self::ProviderPassed => 'Provider-passed',
        };
    }
}
