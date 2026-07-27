<?php

namespace App\Enums;

enum ShippingRequestStatus: string
{
    case Draft = 'draft';
    case AwaitingQuotes = 'awaiting_quotes';
    case QuoteReceived = 'quote_received';
    case Accepted = 'accepted';
    case AwaitingPickup = 'awaiting_pickup';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Disputed = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::AwaitingQuotes => 'Awaiting quotes',
            self::QuoteReceived => 'Quote received',
            self::Accepted => 'Accepted',
            self::AwaitingPickup => 'Awaiting pickup',
            self::InTransit => 'In transit',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Disputed => 'Disputed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Delivered => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Accepted, self::AwaitingPickup, self::InTransit => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::Draft, self::AwaitingQuotes, self::QuoteReceived => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Cancelled, self::Disputed => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
        };
    }

    public function isOpenForQuotes(): bool
    {
        return in_array($this, [self::AwaitingQuotes, self::QuoteReceived], true);
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::Draft, self::AwaitingQuotes, self::QuoteReceived], true);
    }
}
