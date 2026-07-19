<?php

namespace App\Enums;

enum KycStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Not submitted',
            self::Pending => 'Under review',
            self::Approved => 'Verified',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Approved => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Pending => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Rejected => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
            self::Unverified => 'bg-slate-400/15 text-slate-600 ring-1 ring-slate-400/30',
        };
    }
}
