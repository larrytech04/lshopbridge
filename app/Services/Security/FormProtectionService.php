<?php

namespace App\Services\Security;

use App\Models\FormAllowlistEntry;
use App\Models\ProtectedFormSubmission;
use App\Models\SpamReviewCase;
use App\Models\TemporaryFormRestriction;
use App\Services\Security\DTO\ContentRiskAssessment;
use App\Services\Security\DTO\FormGuardResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Single orchestration point for every protected form: honeypot, signed
 * timing token, rate limiting, duplicate/fingerprint detection, free-text
 * content risk, and Cloudflare Turnstile. Each layer has its own settings
 * toggle and is skipped honestly (not faked) when off. Never called for
 * login/registration/password-reset — those forms carry no free-text
 * content to risk-score and already have their own direct Turnstile calls
 * plus (for login) an existing manual rate limiter; wiring them through here
 * as well would duplicate, not improve, real protection.
 *
 * Decision policy (mirrors the spec's confidence tiers):
 *  - restricted IP, 2+ high-confidence signals, or an admin-blocklisted
 *    fingerprint => silently_discarded (never a real record, never an email)
 *  - exactly 1 high-confidence signal, or a moderate score => held for
 *    review, or challenge_required if Turnstile is available and wasn't
 *    already attempted this submission
 *  - a successfully solved Turnstile challenge can downgrade a discard to a
 *    held case (never accepted outright) unless the honeypot also fired
 *  - otherwise => accepted
 */
class FormProtectionService
{
    public function __construct(
        private TurnstileVerificationService $turnstile,
        private HoneypotValidationService $honeypot,
        private FormTimingService $timing,
        private FormFingerprintService $fingerprint,
        private FormRateLimitService $rateLimit,
        private ContactFormRiskService $contentRisk,
        private BotSecurityEventService $events,
        private GeoIpService $geoIp,
        private SpamReviewService $spamReview,
    ) {}

    /**
     * @param  array<string, mixed>  $safeFields  Non-sensitive fields only (name/email/subject/message). Never pass a password, OTP, or card data.
     * @param  array{require_turnstile?: bool, turnstile_action?: string, protection_setting_key?: string, allow_authenticated_bypass?: bool}  $options
     */
    public function guard(Request $request, string $formType, array $safeFields = [], array $options = []): FormGuardResult
    {
        if (! setting('bot_protection_enabled', true)) {
            return $this->ledger($formType, FormGuardResult::allow(), null, null);
        }

        if (($settingKey = $options['protection_setting_key'] ?? null) && ! setting($settingKey, true)) {
            return $this->ledger($formType, FormGuardResult::allow(), null, null);
        }

        $ipHash = $this->hashIp($request->ip());
        $country = ($this->geoIp->lookup($request->ip()) ?? [])['country'] ?? null;
        $logOnly = (bool) setting('bot_protection_log_only_mode', false);

        if (setting('temporary_ip_restriction_enabled', true) && TemporaryFormRestriction::isRestricted('ip', $ipHash, $formType)) {
            return $this->finalize($formType, 'silently_discarded', 'critical', ['temporary_restriction'], $ipHash, $country, $request, $logOnly, safeFields: $safeFields);
        }

        $bypassed = $this->isAllowlisted($request)
            || (($options['allow_authenticated_bypass'] ?? false) && Auth::check());

        if ($bypassed) {
            return $this->ledger($formType, FormGuardResult::allow(), $ipHash, $country);
        }

        $signals = [];
        $highConfidence = 0;
        $score = 0;

        if (setting('honeypot_enabled', true) && $this->honeypot->triggered($request)) {
            $signals[] = 'honeypot_triggered';
            $highConfidence++;
        }

        if (setting('form_timing_protection_enabled', true)) {
            $timingResult = $this->timing->validate($request->input('_form_timing'), $formType);
            if (! $timingResult->valid && in_array($timingResult->reason, ['tampered', 'reused'], true)) {
                $signals[] = 'timing_'.$timingResult->reason;
                $highConfidence++;
            } elseif (! $timingResult->valid && $timingResult->reason === 'expired') {
                $signals[] = 'timing_expired';
                $score += 15;
            } elseif ($timingResult->tooFast) {
                $signals[] = 'timing_too_fast';
                $score += 10;
            }
        }

        $emailHash = filled($safeFields['email'] ?? null) ? hash('sha256', strtolower($safeFields['email'])) : null;
        if (setting('rate_limiting_enabled', true)) {
            if ($this->rateLimit->tooManyAttempts($formType, $request, $emailHash)) {
                return $this->finalize($formType, 'rate_limited', 'medium', ['rate_limit_exceeded'], $ipHash, $country, $request, $logOnly, safeFields: $safeFields);
            }
            $this->rateLimit->hit($formType, $request, $emailHash);
        }

        if (setting('duplicate_detection_enabled', true) && ! empty($safeFields)) {
            $fingerprintHash = $this->fingerprint->fingerprint($safeFields);
            $fingerprintRow = $this->fingerprint->record($fingerprintHash, $formType, $ipHash);

            if ($fingerprintRow->blocked) {
                $signals[] = 'blocked_fingerprint';
                $highConfidence++;
            } elseif ($this->fingerprint->isSuspicious($fingerprintRow)) {
                $signals[] = 'duplicate_payload';
                $score += 35;
            }
        }

        if ((setting('spam_link_detection', true) || setting('suspicious_keyword_detection', true)) && ! empty($safeFields)) {
            $content = $this->contentRisk->evaluate($safeFields, $request);
            $score += $content->score;
            $signals = [...$signals, ...$content->triggeredRules];
            if ($content->level === 'critical') {
                $highConfidence++;
            }
        }

        $turnstileTokenProvided = filled($request->input('cf-turnstile-response'));
        $turnstileSucceeded = false;
        if ($turnstileTokenProvided && $this->turnstile->enabled()) {
            $result = $this->turnstile->verify($request, $options['turnstile_action'] ?? $formType);
            if ($result->providerUnavailable) {
                $this->events->alertProviderUnavailable('Cloudflare Turnstile');
                $signals[] = 'turnstile_unavailable';
                $score += 10;
            } elseif (! $result->success) {
                $signals[] = 'turnstile_failed';
                $highConfidence++;
            } else {
                $turnstileSucceeded = true;
            }
        }

        $outcome = match (true) {
            in_array('blocked_fingerprint', $signals, true) => 'silently_discarded',
            $highConfidence >= 2 => 'silently_discarded',
            $highConfidence === 1, $score >= 40 => $this->turnstile->enabled() && ! $turnstileTokenProvided
                ? 'challenge_required'
                : 'held',
            default => 'accepted',
        };

        // A solved challenge is strong evidence of a human — never discard on
        // its account alone, unless the honeypot also fired.
        if ($outcome === 'silently_discarded' && $turnstileSucceeded && ! in_array('honeypot_triggered', $signals, true) && $highConfidence < 2) {
            $outcome = 'held';
        }

        // With silent discard turned off, a human always sees the message
        // eventually — high-confidence submissions still never become a real
        // record automatically, they just wait in the review queue instead
        // of being thrown away.
        if ($outcome === 'silently_discarded' && ! setting('silent_bot_discard_enabled', true)) {
            $outcome = 'held';
        }

        return $this->finalize($formType, $outcome, $this->levelFor($score, $highConfidence), $signals, $ipHash, $country, $request, $logOnly, score: $score, safeFields: $safeFields);
    }

    private function finalize(
        string $formType,
        string $outcome,
        string $riskLevel,
        array $signals,
        ?string $ipHash,
        ?string $country,
        Request $request,
        bool $logOnly,
        ?int $score = null,
        array $safeFields = [],
    ): FormGuardResult {
        $reviewCaseId = null;

        if ($logOnly && $outcome !== 'accepted' && $outcome !== 'rate_limited') {
            // Log-only mode: record what WOULD have happened, but let it through.
            $this->events->record("form.would_have_{$outcome}", $formType, $riskLevel, 'allowed_log_only', [
                'triggered_rules' => $signals,
                'ip_hash' => $ipHash,
                'country' => $country,
                'user_agent' => (string) $request->userAgent(),
            ]);

            return $this->ledger($formType, new FormGuardResult('accepted', $riskLevel, $signals), $ipHash, $country);
        }

        if ($outcome === 'held') {
            $case = $this->spamReview->hold($formType, $safeFields, new ContentRiskAssessment($score ?? 0, $riskLevel, $signals), [
                'ip_hash' => $ipHash,
                'country' => $country,
                'user_agent' => (string) $request->userAgent(),
            ]);
            $reviewCaseId = $case->id;
        }

        if (in_array($outcome, ['held', 'silently_discarded', 'rate_limited'], true)) {
            $eventType = match ($outcome) {
                'held' => 'form.held_for_review',
                'rate_limited' => 'form.rate_limit_exceeded',
                default => in_array('honeypot_triggered', $signals, true) ? 'form.honeypot_triggered' : 'form.silently_discarded',
            };

            $this->events->record($eventType, $formType, $riskLevel, $outcome, [
                'triggered_rules' => $signals,
                'ip_hash' => $ipHash,
                'country' => $country,
                'user_agent' => (string) $request->userAgent(),
                'related_type' => $reviewCaseId ? SpamReviewCase::class : null,
                'related_id' => $reviewCaseId,
            ]);
        }

        if ($outcome === 'silently_discarded' && ! app()->runningUnitTests()) {
            // Randomized, normal-looking delay so timing doesn't reveal the discard.
            usleep(random_int(150_000, 450_000));
        }

        return $this->ledger($formType, new FormGuardResult($outcome, $riskLevel, $signals, $reviewCaseId), $ipHash, $country);
    }

    private function ledger(string $formType, FormGuardResult $result, ?string $ipHash, ?string $country): FormGuardResult
    {
        ProtectedFormSubmission::create([
            'form_type' => $formType,
            'outcome' => $result->outcome,
            'risk_level' => $result->riskLevel,
            'ip_hash' => $ipHash,
            'country' => $country,
        ]);

        return $result;
    }

    private function isAllowlisted(Request $request): bool
    {
        if (FormAllowlistEntry::allows('ip', $request->ip())) {
            return true;
        }

        $email = $request->input('email');
        if (filled($email) && str_contains($email, '@')) {
            $domain = strtolower(substr(strrchr($email, '@'), 1));

            return FormAllowlistEntry::allows('email_domain', $domain);
        }

        return false;
    }

    private function levelFor(int $score, int $highConfidence): string
    {
        if ($highConfidence >= 2) {
            return 'critical';
        }
        if ($highConfidence === 1) {
            return 'high';
        }

        return match (true) {
            $score >= 40 => 'medium',
            default => 'low',
        };
    }

    private function hashIp(?string $ip): ?string
    {
        return $ip ? hash_hmac('sha256', $ip, config('app.key')) : null;
    }
}
