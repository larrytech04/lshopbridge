<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            default => ucfirst($this->value),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Resolved => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::InProgress => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::Open => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Closed => 'bg-slate-400/15 text-slate-600 ring-1 ring-slate-400/30',
        };
    }
}
