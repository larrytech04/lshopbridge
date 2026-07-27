<?php

namespace App\Enums;

enum GuideDifficulty: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Beginner',
            self::Intermediate => 'Intermediate',
            self::Advanced => 'Advanced',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Beginner => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Intermediate => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Advanced => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
        };
    }
}
