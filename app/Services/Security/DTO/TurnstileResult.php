<?php

namespace App\Services\Security\DTO;

/** Safe validation metadata from a Turnstile check — never carries the secret key or form content. */
class TurnstileResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $hostname = null,
        public readonly ?string $action = null,
        public readonly ?string $challengeTs = null,
        public readonly array $errorCodes = [],
        public readonly bool $providerUnavailable = false,
    ) {}

    public function reasonCode(): ?string
    {
        return $this->errorCodes[0] ?? null;
    }
}
