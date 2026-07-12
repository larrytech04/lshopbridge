<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Turnstile bot-protection. Keys come from the admin Integrations
 * page (settings) or .env. When disabled/unconfigured, verification is a no-op
 * so local/sandbox auth keeps working.
 */
class Turnstile
{
    public function siteKey(): ?string
    {
        return setting('turnstile_site_key', config('services.turnstile.site_key'));
    }

    public function secretKey(): ?string
    {
        return setting('turnstile_secret_key', config('services.turnstile.secret_key'));
    }

    public function enabled(): bool
    {
        return (bool) setting('turnstile_enabled', false) && $this->siteKey() && $this->secretKey();
    }

    public function verify(Request $request): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        try {
            $response = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $this->secretKey(),
                'response' => $request->input('cf-turnstile-response'),
                'remoteip' => $request->ip(),
            ]);

            return (bool) $response->json('success');
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
