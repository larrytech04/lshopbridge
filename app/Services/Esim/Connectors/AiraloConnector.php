<?php

namespace App\Services\Esim\Connectors;

use App\Models\ImportSource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Airalo Partner API connector.
 *
 * IMPORTANT: built from documented knowledge of Airalo's Partner API shape
 * (OAuth2 client-credentials auth, /v2/packages, /v2/orders, /v2/sims/{iccid}/usage),
 * NOT verified against a live sandbox — no credentials exist yet (tracked:
 * the user is in the process of obtaining Airalo Partner API access). Every
 * endpoint path, field name, and the webhook signature scheme below MUST be
 * re-checked against Airalo's current official documentation the moment
 * real sandbox credentials are available, before this is trusted with a
 * single real order. Until then this class is exercised only by
 * tests/Feature/Esim/AiraloConnectorTest.php using Http::fake() — it has
 * never made a real network call.
 *
 * ImportSource::credentials (encrypted:array) expected shape:
 *   ['client_id' => ..., 'client_secret' => ..., 'environment' => 'sandbox'|'production']
 */
class AiraloConnector extends AbstractEsimConnector
{
    public function capabilities(): array
    {
        return [
            'testConnection', 'getAccountBalance', 'fetchPlans', 'fetchPlan',
            'validatePlan', 'createOrder', 'getOrder', 'retrieveProvisioning',
            'getEsimStatus', 'getUsage', 'getTopupPlans', 'createTopup', 'handleWebhook',
        ];
        // Deliberately NOT declaring: fetchDestinations, fetchRegions (fold into
        // fetchPlans' own country/region fields instead of a separate real
        // endpoint I'm not confident exists), cancelOrder, requestRefund,
        // suspendEsim, reactivateEsim — Airalo eSIMs are provisioned near-
        // instantly and are not typically cancellable/suspendable through the
        // partner API once issued. Confirm against real docs before adding.
    }

    public function testConnection(ImportSource $source): array
    {
        try {
            $this->authenticate($source);

            return ['connected' => true, 'message' => 'Authenticated successfully.'];
        } catch (\Throwable $e) {
            return ['connected' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAccountBalance(ImportSource $source): ?array
    {
        $response = $this->client($source)->get('balance');
        if ($response->failed()) {
            return null;
        }

        $data = $response->json('data', []);

        return ['currency' => $data['currency'] ?? 'USD', 'balance' => (float) ($data['balance'] ?? 0)];
    }

    public function fetchPlans(ImportSource $source, array $filters = []): array
    {
        $response = $this->client($source)->get('packages', array_filter($filters));
        $response->throw();

        return $response->json('data', []);
    }

    public function fetchPlan(ImportSource $source, string $providerPackageId): array
    {
        $response = $this->client($source)->get("packages/{$providerPackageId}");
        $response->throw();

        return $response->json('data', []);
    }

    /** Re-confirms the package before charging the customer (section 11 of the eSIM spec: never trust a stale price). */
    public function validatePlan(ImportSource $source, string $providerPackageId): array
    {
        $plan = $this->fetchPlan($source, $providerPackageId);

        return [
            'exists' => ! empty($plan),
            'available' => (bool) ($plan['is_active'] ?? true),
            'provider_cost' => (float) ($plan['net_price'] ?? $plan['price'] ?? 0),
            'currency' => $plan['currency'] ?? 'USD',
            'raw' => $plan,
        ];
    }

    public function createOrder(ImportSource $source, string $providerPackageId, string $idempotencyKey): array
    {
        $response = $this->client($source)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->post('orders', [
                'package_id' => $providerPackageId,
                'quantity' => 1,
                'description' => 'LshopBridge order '.$idempotencyKey,
            ]);
        $response->throw();

        return $response->json('data', []);
    }

    public function getOrder(ImportSource $source, string $providerOrderId): array
    {
        $response = $this->client($source)->get("orders/{$providerOrderId}");
        $response->throw();

        return $response->json('data', []);
    }

    /** Extracts the customer-facing activation data from an order's response payload. */
    public function retrieveProvisioning(ImportSource $source, string $providerOrderId): array
    {
        $order = $this->getOrder($source, $providerOrderId);
        $sim = $order['sims'][0] ?? [];

        return [
            'iccid' => $sim['iccid'] ?? null,
            'lpa_string' => $sim['lpa'] ?? null,
            'sm_dp_address' => $sim['rsp'] ?? null,
            'activation_code' => $sim['matching_id'] ?? null,
            'confirmation_code' => $sim['confirmation_code'] ?? null,
            'qr_code_url' => $sim['qrcode_url'] ?? null,
            'direct_install_url' => $sim['direct_apple_installation_url'] ?? null,
        ];
    }

    public function getEsimStatus(ImportSource $source, string $iccid): array
    {
        $response = $this->client($source)->get("sims/{$iccid}");
        $response->throw();

        return $response->json('data', []);
    }

    public function getUsage(ImportSource $source, string $iccid): array
    {
        $response = $this->client($source)->get("sims/{$iccid}/usage");
        $response->throw();

        $data = $response->json('data', []);

        return [
            'total_mb' => $data['total'] ?? null,
            'used_mb' => $data['remaining'] !== null && $data['total'] !== null ? $data['total'] - $data['remaining'] : null,
            'remaining_mb' => $data['remaining'] ?? null,
        ];
    }

    public function getTopupPlans(ImportSource $source, string $iccid): array
    {
        $response = $this->client($source)->get("sims/{$iccid}/topups");
        $response->throw();

        return $response->json('data', []);
    }

    public function createTopup(ImportSource $source, string $iccid, string $providerTopupPackageId, string $idempotencyKey): array
    {
        $response = $this->client($source)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->post('orders/topup', [
                'iccid' => $iccid,
                'package_id' => $providerTopupPackageId,
            ]);
        $response->throw();

        return $response->json('data', []);
    }

    /**
     * Signature scheme unconfirmed — Airalo webhooks are documented to carry
     * a signature header, but the exact header name/HMAC algorithm must be
     * verified against real docs before this is trusted. Fails closed
     * (invalid) rather than skipping verification.
     */
    public function handleWebhook(ImportSource $source, array $headers, string $rawBody): array
    {
        $secret = $source->credentials['webhook_secret'] ?? null;
        $signatureHeader = $headers['x-airalo-signature'][0] ?? $headers['X-Airalo-Signature'][0] ?? null;

        if (! $secret || ! $signatureHeader) {
            return ['valid' => false, 'event' => null, 'payload' => []];
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);
        if (! hash_equals($expected, $signatureHeader)) {
            return ['valid' => false, 'event' => null, 'payload' => []];
        }

        $payload = json_decode($rawBody, true) ?? [];

        return ['valid' => true, 'event' => $payload['event'] ?? null, 'payload' => $payload];
    }

    /* -------------------------------------------------- internals */

    private function authenticate(ImportSource $source): string
    {
        $cacheKey = "airalo:token:{$source->id}";

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($source) {
            $credentials = $source->credentials ?? [];
            $response = Http::baseUrl($this->baseUrl($source))->asForm()->post('token', [
                'client_id' => $credentials['client_id'] ?? '',
                'client_secret' => $credentials['client_secret'] ?? '',
                'grant_type' => 'client_credentials',
            ]);
            $response->throw();

            $token = $response->json('data.access_token') ?? $response->json('access_token');
            if (! $token) {
                throw new \RuntimeException('Airalo authentication succeeded but no access_token was returned.');
            }

            return $token;
        });
    }

    private function baseUrl(ImportSource $source): string
    {
        $environment = $source->credentials['environment'] ?? 'sandbox';

        return $environment === 'production'
            ? 'https://partners-api.airalo.com/v2/'
            : 'https://sandbox-partners-api.airalo.com/v2/';
    }

    private function client(ImportSource $source)
    {
        return Http::baseUrl($this->baseUrl($source))
            ->withToken($this->authenticate($source))
            ->acceptJson();
    }
}
