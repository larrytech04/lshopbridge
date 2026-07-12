<?php

namespace App\Services\Funding\DTO;

/**
 * Outcome of pushing money to a China wallet via a funding provider.
 * status: succeeded | processing | failed | manual
 *  - succeeded : recipient credited
 *  - processing: accepted, final state will arrive by webhook/poll
 *  - failed    : provider rejected (triggers refund)
 *  - manual    : provider needs human handling (triggers manual_review)
 */
class FundingResult
{
    public function __construct(
        public string $status,
        public ?string $providerReference = null,
        public ?string $receipt = null,   // receipt note / URL / reference
        public ?string $message = null,
        public array $raw = [],
    ) {}

    public function succeeded(): bool
    {
        return $this->status === 'succeeded';
    }

    public function failed(): bool
    {
        return $this->status === 'failed';
    }

    public function needsManual(): bool
    {
        return $this->status === 'manual';
    }
}
