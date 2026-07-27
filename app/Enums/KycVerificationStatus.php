<?php

namespace App\Enums;

enum KycVerificationStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case MoreInfoRequested = 'more_info_requested';
    case ReturnedForCorrection = 'returned_for_correction';
    case Approved = 'approved';
    case ApprovedLimited = 'approved_limited';
    case Rejected = 'rejected';
    case Escalated = 'escalated';
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::InReview => 'In review',
            self::MoreInfoRequested => 'More info requested',
            self::ReturnedForCorrection => 'Returned for correction',
            self::Approved => 'Approved',
            self::ApprovedLimited => 'Approved (limited)',
            self::Rejected => 'Rejected',
            self::Escalated => 'Escalated',
            self::OnHold => 'On hold',
        };
    }

    /**
     * Semantic palette: green=approved, amber=needs action from user, red=rejected,
     * purple=escalated, gray=neutral/hold, blue=in progress. Kept deliberately restrained.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-slate-500/10 text-slate-600 dark:text-slate-300',
            self::InReview => 'bg-blue-500/10 text-blue-600 dark:text-blue-300',
            self::MoreInfoRequested => 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
            self::ReturnedForCorrection => 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
            self::Approved => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
            self::ApprovedLimited => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
            self::Rejected => 'bg-rose-500/10 text-rose-600 dark:text-rose-300',
            self::Escalated => 'bg-purple-500/10 text-purple-600 dark:text-purple-300',
            self::OnHold => 'bg-gray-500/10 text-gray-600 dark:text-gray-300',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::InReview, self::MoreInfoRequested, self::ReturnedForCorrection, self::Escalated, self::OnHold], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Approved, self::ApprovedLimited, self::Rejected], true);
    }
}
