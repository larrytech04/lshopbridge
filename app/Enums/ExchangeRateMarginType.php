<?php

namespace App\Enums;

enum ExchangeRateMarginType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Fixed => 'Fixed adjustment',
            self::Custom => 'Custom effective rate',
        };
    }
}
