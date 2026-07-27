<?php

namespace App\Enums;

enum FundingStatus: string
{
    case PaymentPending = 'payment_pending';
    case PaymentSuccessful = 'payment_successful';
    case FundingProcessing = 'funding_processing';
    case FundingSuccessful = 'funding_successful';
    case FundingFailed = 'funding_failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case ManualReview = 'manual_review';

    public function label(): string
    {
        return match ($this) {
            self::PaymentPending => 'Awaiting payment',
            self::PaymentSuccessful => 'Payment received',
            self::FundingProcessing => 'Processing',
            self::FundingSuccessful => 'Completed',
            self::FundingFailed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
            self::ManualReview => 'Under review',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FundingSuccessful => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::PaymentSuccessful, self::FundingProcessing => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::PaymentPending, self::ManualReview => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::FundingFailed => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
            self::Cancelled => 'bg-gray-400/15 text-gray-600 ring-1 ring-gray-400/30',
            self::Refunded => 'bg-teal-500/15 text-teal-600 ring-1 ring-teal-400/30',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::FundingSuccessful, self::Refunded, self::Cancelled], true);
    }

    public function needsAdmin(): bool
    {
        return in_array($this, [self::ManualReview, self::FundingFailed], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::PaymentPending, self::PaymentSuccessful, self::FundingProcessing, self::ManualReview], true);
    }

    /** Money was debited at request-creation time — only a request that hasn't already
     *  been delivered or refunded can safely give that debit back. */
    public function canBeRefunded(): bool
    {
        return ! in_array($this, [self::FundingSuccessful, self::Refunded, self::Cancelled], true);
    }
}
