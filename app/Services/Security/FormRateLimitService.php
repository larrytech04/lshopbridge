<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Per-form rate limiting keyed by IP, session, and (when available) a hash
 * of the submitted email — not Laravel's route-level RateLimiter::for(),
 * since these checks run from inside FormProtectionService rather than
 * `throttle:` route middleware. Named per form type so each form's limiter
 * is independently tunable and distinguishable in FormSecurityEvent logs.
 */
class FormRateLimitService
{
    private const LIMITS = [
        'contact' => ['max' => 5, 'decay' => 300],
        'guest_support' => ['max' => 5, 'decay' => 300],
        'newsletter' => ['max' => 5, 'decay' => 300],
        'registration' => ['max' => 10, 'decay' => 3600],
        'agent_registration' => ['max' => 5, 'decay' => 3600],
        'password_reset' => ['max' => 5, 'decay' => 900],
        'review_feedback' => ['max' => 5, 'decay' => 3600],
        'referral' => ['max' => 5, 'decay' => 3600],
        'guide_feedback' => ['max' => 10, 'decay' => 600],
    ];

    public function tooManyAttempts(string $formType, Request $request, ?string $emailHash = null): bool
    {
        foreach ($this->keysFor($formType, $request, $emailHash) as $key) {
            if (RateLimiter::tooManyAttempts($key, $this->maxAttempts($formType))) {
                return true;
            }
        }

        return false;
    }

    public function hit(string $formType, Request $request, ?string $emailHash = null): void
    {
        $decay = self::LIMITS[$formType]['decay'] ?? 300;
        foreach ($this->keysFor($formType, $request, $emailHash) as $key) {
            RateLimiter::hit($key, $decay);
        }
    }

    public function clear(string $formType, Request $request, ?string $emailHash = null): void
    {
        foreach ($this->keysFor($formType, $request, $emailHash) as $key) {
            RateLimiter::clear($key);
        }
    }

    private function maxAttempts(string $formType): int
    {
        return self::LIMITS[$formType]['max'] ?? 5;
    }

    /** @return list<string> */
    private function keysFor(string $formType, Request $request, ?string $emailHash): array
    {
        $keys = [
            "form-limit:{$formType}:ip:".$request->ip(),
            "form-limit:{$formType}:session:".$request->session()->getId(),
        ];

        if ($emailHash) {
            $keys[] = "form-limit:{$formType}:email:{$emailHash}";
        }

        return $keys;
    }
}
