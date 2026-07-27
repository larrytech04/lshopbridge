<?php

namespace App\Services\Security\DTO;

class FormTimingResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?int $elapsedSeconds = null,
        public readonly bool $tooFast = false,
        public readonly ?string $reason = null, // missing | tampered | expired | reused | form-mismatch
    ) {}
}
