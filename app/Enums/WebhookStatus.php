<?php

namespace App\Enums;

enum WebhookStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Duplicate = 'duplicate';
    case InvalidSignature = 'invalid_signature';
    case Failed = 'failed';
    case Ignored = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::InvalidSignature => 'Invalid signature',
            default => ucfirst($this->value),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Processed => 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/30',
            self::Received => 'bg-sky-500/15 text-sky-300 ring-1 ring-sky-400/30',
            self::Duplicate, self::Ignored => 'bg-slate-400/15 text-slate-300 ring-1 ring-slate-400/30',
            self::InvalidSignature, self::Failed => 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-400/30',
        };
    }
}
