<?php

namespace App\Enums;

enum AgentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Approved => 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/30',
            self::Pending => 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-400/30',
            self::Rejected, self::Suspended => 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-400/30',
        };
    }
}
