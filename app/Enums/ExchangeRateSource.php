<?php

namespace App\Enums;

enum ExchangeRateSource: string
{
    case Manual = 'manual';
    case Provider = 'provider';
    case ScheduledManual = 'scheduled_manual';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Provider => 'Automatic provider',
            self::ScheduledManual => 'Scheduled manual rate',
        };
    }
}
