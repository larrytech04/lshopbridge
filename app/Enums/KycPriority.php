<?php

namespace App\Enums;

enum KycPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-500/10 text-slate-600 dark:text-slate-300',
            self::Medium => 'bg-blue-500/10 text-blue-600 dark:text-blue-300',
            self::High => 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
            self::Critical => 'bg-rose-500/10 text-rose-600 dark:text-rose-300',
        };
    }

    /** SLA target in hours before a case of this priority is considered breached. */
    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 72,
            self::Medium => 48,
            self::High => 24,
            self::Critical => 4,
        };
    }
}
