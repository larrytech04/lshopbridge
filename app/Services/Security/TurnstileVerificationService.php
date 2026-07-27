<?php

namespace App\Services\Security;

use App\Services\Security\DTO\TurnstileResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Turnstile bot-protection. Keys come from .env only
 * (TURNSTILE_SITE_KEY / TURNSTILE_SECRET_KEY), never admin-editable — only
 * the site key (public by design) and a configured/not-configured status are
 * ever shown in the admin UI. When disabled/unconfigured, verification is a
 * no-op success so forms keep working without Turnstile configured.
 *
 * Cloudflare's siteverify endpoint itself rejects expired and already-used
 * tokens (surfaced here as the `timeout-or-duplicate` error code) — this
 * class doesn't need its own replay cache for Turnstile tokens specifically.
 */
class TurnstileVerificationService
{
    public function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    public function secretKey(): ?string
    {
        return config('services.turnstile.secret_key');
    }

    public function configured(): bool
    {
        return (bool) $this->siteKey() && (bool) $this->secretKey();
    }

    public function enabled(): bool
    {
        return (bool) setting('turnstile_enabled', false) && $this->configured();
    }

    /**
     * @param  string|null  $expectedAction  The `data-action` value the widget on this form was rendered with.
     */
    public function verify(Request $request, ?string $expectedAction = null): TurnstileResult
    {
        if (! $this->enabled()) {
            return new TurnstileResult(success: true);
        }

        $token = (string) $request->input('cf-turnstile-response', '');
        if ($token === '') {
            return new TurnstileResult(success: false, errorCodes: ['missing-input-response']);
        }

        try {
            $response = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $this->secretKey(),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return new TurnstileResult(success: false, errorCodes: ['provider-unavailable'], providerUnavailable: true);
        }

        $success = (bool) $response->json('success');
        $hostname = $response->json('hostname');
        $action = $response->json('action');
        $errorCodes = $response->json('error-codes', []);

        if ($success) {
            // Compare against the LIVE request host, not a static config value — a
            // dev port or preview host would otherwise always fail this check
            // (same footgun documented for the WebAuthn origin check elsewhere).
            $expectedHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $requestHost = $request->getHost();

            if ($hostname && $hostname !== $requestHost && $hostname !== $expectedHost) {
                return new TurnstileResult(success: false, hostname: $hostname, action: $action, errorCodes: ['hostname-mismatch']);
            }

            if ($expectedAction && $action && $action !== $expectedAction) {
                return new TurnstileResult(success: false, hostname: $hostname, action: $action, errorCodes: ['action-mismatch']);
            }
        }

        return new TurnstileResult(
            success: $success,
            hostname: $hostname,
            action: $action,
            challengeTs: $response->json('challenge_ts'),
            errorCodes: $errorCodes,
        );
    }
}
