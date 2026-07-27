<?php

namespace App\Enums;

enum ShopOrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case PartiallyFulfilled = 'partially_fulfilled';
    case Fulfilled = 'fulfilled';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case RefundRequested = 'refund_requested';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Disputed = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Paid => 'Paid · delivering',
            self::Processing => 'Processing',
            self::PartiallyFulfilled => 'Partially fulfilled',
            self::Fulfilled => 'Delivered',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::RefundRequested => 'Refund requested',
            self::PartiallyRefunded => 'Partially refunded',
            self::Refunded => 'Refunded',
            self::Disputed => 'Disputed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Fulfilled, self::Delivered => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::Paid, self::Processing, self::Shipped => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::Pending, self::PartiallyFulfilled, self::RefundRequested => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Failed, self::Disputed => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
            self::Cancelled => 'bg-gray-400/15 text-gray-600 ring-1 ring-gray-400/30',
            self::Refunded, self::PartiallyRefunded => 'bg-violet-500/15 text-violet-600 ring-1 ring-violet-400/30',
        };
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Fulfilled, self::Delivered, self::Failed, self::Cancelled, self::Refunded], true);
    }
}
