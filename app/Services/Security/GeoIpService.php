<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IP geolocation via ipinfo.io, gated on IPINFO_API_KEY — unconfigured means
 * a clean no-op (returns null), never a guessed/fabricated location.
 *
 * VPN/proxy detection is a SEPARATE, paid ipinfo.io add-on ("IP to Privacy
 * Detection") this app has no subscription for. isVpn() below calls that
 * endpoint and will return null (not false) whenever the account/plan
 * doesn't include it — a null here must be read as "unknown", never as "not
 * a VPN". "Impossible travel" (comparing login speed against geographic
 * distance) is not implemented at all: it needs a provider config this app
 * doesn't have configured. Only new-country detection is real here.
 */
class GeoIpService
{
    public function isConfigured(): bool
    {
        return (bool) config('services.ipinfo.api_key');
    }

    /** @return array{country: ?string, country_name: ?string, is_vpn: ?bool}|null */
    public function lookup(string $ip): ?array
    {
        if (! $this->isConfigured() || $this->isPrivateIp($ip)) {
            return null;
        }

        return Cache::remember("geoip:{$ip}", now()->addHours(6), function () use ($ip) {
            try {
                $response = Http::timeout(3)->get("https://ipinfo.io/{$ip}", [
                    'token' => config('services.ipinfo.api_key'),
                ]);

                if ($response->failed()) {
                    return null;
                }

                $data = $response->json();

                return [
                    'country' => $data['country'] ?? null,
                    'country_name' => $data['country'] ?? null,
                    'is_vpn' => $this->isVpn($ip),
                ];
            } catch (\Throwable $e) {
                Log::warning('GeoIP lookup failed', ['exception' => $e->getMessage()]);

                return null;
            }
        });
    }

    /** null = unknown (no privacy-detection subscription), not "confirmed clean". */
    private function isVpn(string $ip): ?bool
    {
        try {
            $response = Http::timeout(3)->get("https://ipinfo.io/{$ip}/privacy", [
                'token' => config('services.ipinfo.api_key'),
            ]);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();
            if (! isset($data['vpn'], $data['proxy'])) {
                return null;
            }

            return (bool) ($data['vpn'] || $data['proxy'] || ($data['hosting'] ?? false));
        } catch (\Throwable) {
            return null;
        }
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
