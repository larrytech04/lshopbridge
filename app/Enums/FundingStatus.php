<?php

namespace App\Enums;

enum FundingStatus: string
{
    case PaymentPending = 'payment_pending';
    case PaymentSuccessful = 'payment_successful';
    case FundingProcessing = 'funding_processing';
    case FundingSuccessful = 'funding_successful';
    case FundingFailed = 'funding_failed';
    case Refunded = 'refunded';
    case ManualReview = 'manual_review';

    public function label(): string
    {
        return match ($this) {
            self::PaymentPending => 'Awaiting payment',
            self::PaymentSuccessful => 'Payment received',
            self::FundingProcessing => 'Funding in progress',
            self::FundingSuccessful => 'Completed',
            self::FundingFailed => 'Funding failed',
            self::Refunded => 'Refunded',
            self::ManualReview => 'Manual review',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FundingSuccessful => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::PaymentSuccessful, self::FundingProcessing => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::PaymentPending => 'bg-slate-400/15 text-slate-600 ring-1 ring-slate-400/30',
            self::ManualReview => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::FundingFailed => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
            self::Refunded => 'bg-violet-500/15 text-violet-600 ring-1 ring-violet-400/30',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::FundingSuccessful, self::Refunded], true);
    }

    public function needsAdmin(): bool
    {
        return in_array($this, [self::ManualReview, self::FundingFailed], true);
    }
}
