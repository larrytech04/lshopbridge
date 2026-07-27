<?php

namespace App\Enums;

enum PaymentMethodStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Disabled = 'disabled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Disabled => 'Disabled',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Draft => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::Disabled => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Archived => 'bg-gray-400/15 text-gray-600 ring-1 ring-gray-400/30',
        };
    }
}
