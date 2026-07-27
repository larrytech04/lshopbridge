<?php

namespace App\Services\Security\DTO;

class FormGuardResult
{
    /** @param  list<string>  $triggeredRules */
    public function __construct(
        public readonly string $outcome, // accepted | challenge_required | held | rate_limited | silently_discarded
        public readonly string $riskLevel,
        public readonly array $triggeredRules = [],
        public readonly ?int $reviewCaseId = null,
    ) {}

    public static function allow(): self
    {
        return new self('accepted', 'low');
    }

    public function isAccepted(): bool
    {
        return $this->outcome === 'accepted';
    }

    public function needsFakeSuccessResponse(): bool
    {
        return in_array($this->outcome, ['held', 'silently_discarded'], true);
    }
}
