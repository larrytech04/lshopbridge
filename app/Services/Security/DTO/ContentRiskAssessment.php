<?php

namespace App\Services\Security\DTO;

class ContentRiskAssessment
{
    /** @param  list<string>  $triggeredRules */
    public function __construct(
        public readonly int $score,
        public readonly string $level, // low | medium | high | critical
        public readonly array $triggeredRules,
    ) {}
}
