<?php

namespace App\Services\Payments\DTO;

/**
 * Normalised view of a provider webhook, regardless of provider format.
 * status: succeeded | failed | pending
 */
class WebhookResult
{
    public function __construct(
        public ?string $eventId,
        public ?string $reference,        // our PaymentIntent reference
        public string $status,
        public ?string $providerReference = null,
        public ?float $amount = null,
        public ?string $currency = null,
        public ?string $eventType = null,
        public array $raw = [],
    ) {}

    public function succeeded(): bool
    {
        return $this->status === 'succeeded';
    }
}
