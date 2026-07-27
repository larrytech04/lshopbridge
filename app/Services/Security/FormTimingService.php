<?php

namespace App\Services\Security;

use App\Services\Security\DTO\FormTimingResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Signed, expiring "when was this form actually rendered" token. Laravel's
 * encrypter authenticates the payload (tamper => DecryptException), so a
 * forged or hand-edited timestamp is rejected outright. Reuse is tracked via
 * a short-lived cache entry keyed by the token's nonce, expiring alongside
 * the token itself.
 *
 * Being "too fast" is only ever returned as a signal (tooFast) for the risk
 * engine to weigh — never a hard rejection on its own. Accessibility tools,
 * autofill, and returning customers can legitimately submit in a couple of
 * seconds.
 */
class FormTimingService
{
    private const MAX_LIFETIME_SECONDS = 3600;
    private const MIN_HUMAN_SECONDS = 3;

    public function issue(string $formType): string
    {
        $payload = [
            'form_type' => $formType,
            'issued_at' => now()->timestamp,
            'nonce' => Str::random(24),
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    public function validate(?string $token, string $formType): FormTimingResult
    {
        if (! $token) {
            return new FormTimingResult(valid: false, reason: 'missing');
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable) {
            return new FormTimingResult(valid: false, reason: 'tampered');
        }

        if (! is_array($payload) || ($payload['form_type'] ?? null) !== $formType) {
            return new FormTimingResult(valid: false, reason: 'form-mismatch');
        }

        $elapsed = now()->timestamp - (int) ($payload['issued_at'] ?? 0);

        if ($elapsed > self::MAX_LIFETIME_SECONDS || $elapsed < 0) {
            return new FormTimingResult(valid: false, elapsedSeconds: $elapsed, reason: 'expired');
        }

        $cacheKey = 'form-timing-used:'.($payload['nonce'] ?? '');
        if (Cache::has($cacheKey)) {
            return new FormTimingResult(valid: false, elapsedSeconds: $elapsed, reason: 'reused');
        }
        Cache::put($cacheKey, true, self::MAX_LIFETIME_SECONDS);

        return new FormTimingResult(
            valid: true,
            elapsedSeconds: $elapsed,
            tooFast: $elapsed < self::MIN_HUMAN_SECONDS,
        );
    }
}
