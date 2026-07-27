<?php

namespace App\Enums;

enum KycDecisionType: string
{
    case Approve = 'approve';
    case ApproveLimited = 'approve_limited';
    case RequestMoreInfo = 'request_more_info';
    case ReturnForCorrection = 'return_for_correction';
    case Reject = 'reject';
    case Escalate = 'escalate';
    case Hold = 'hold';
    case FlagSuspicious = 'flag_suspicious';
    case FreezeAccount = 'freeze_account';

    public function label(): string
    {
        return match ($this) {
            self::Approve => 'Approve',
            self::ApproveLimited => 'Approve with limitation',
            self::RequestMoreInfo => 'Request more info',
            self::ReturnForCorrection => 'Return for correction',
            self::Reject => 'Reject',
            self::Escalate => 'Escalate',
            self::Hold => 'Hold',
            self::FlagSuspicious => 'Flag as suspicious',
            self::FreezeAccount => 'Freeze account',
        };
    }

    public function resultingStatus(): ?KycVerificationStatus
    {
        return match ($this) {
            self::Approve => KycVerificationStatus::Approved,
            self::ApproveLimited => KycVerificationStatus::ApprovedLimited,
            self::RequestMoreInfo => KycVerificationStatus::MoreInfoRequested,
            self::ReturnForCorrection => KycVerificationStatus::ReturnedForCorrection,
            self::Reject => KycVerificationStatus::Rejected,
            self::Escalate => KycVerificationStatus::Escalated,
            self::Hold => KycVerificationStatus::OnHold,
            // Flag/freeze are side actions layered on the case, not identity decisions —
            // they don't change the verification's own status.
            self::FlagSuspicious, self::FreezeAccount => null,
        };
    }

    /**
     * These decisions always need a sanitized, customer-facing message so a
     * reviewer never falls back to leaking the internal reason to the applicant.
     */
    public function requiresCustomerMessage(): bool
    {
        return in_array($this, [self::RequestMoreInfo, self::ReturnForCorrection, self::Reject, self::ApproveLimited], true);
    }

    /** Final identity decisions are never allowed via bulk actions. */
    public function isFinalIdentityDecision(): bool
    {
        return in_array($this, [self::Approve, self::ApproveLimited, self::Reject], true);
    }
}
