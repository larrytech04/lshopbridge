<?php

namespace App\Services\Payments\DTO;

/**
 * Outcome of asking a provider to charge the user.
 * status: processing | succeeded | failed
 *  - processing: provider accepted, final result will arrive by webhook
 *  - succeeded : provider settled synchronously (rare)
 *  - failed    : provider rejected the charge up-front
 */
class ChargeResult
{
    public function __construct(
        public string $status,
        public ?string $providerReference = null,
        public ?string $redirectUrl = null,
        public ?string $message = null,
        public array $raw = [],
        /** Unified signed payload a sandbox can replay through the webhook pipeline. */
        public ?array $sandboxWebhook = null,
    ) {}

    public function processing(): bool
    {
        return $this->status === 'processing';
    }

    public function failed(): bool
    {
        return $this->status === 'failed';
    }
}
