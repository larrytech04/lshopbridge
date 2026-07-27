<?php

namespace App\Services\Payments\Providers;

use App\Models\PaymentIntent;
use App\Services\Payments\DTO\ChargeResult;
use App\Services\Payments\DTO\WebhookResult;
use Illuminate\Support\Facades\Http;

/**
 * Flutterwave, card + mobile money collections (Standard / Charges API).
 * Docs: https://developer.flutterwave.com/
 *
 * Flutterwave signs webhooks by sending your "secret hash" verbatim in the
 * `verif-hash` header (not an HMAC), so verifySignature() is overridden.
 */
class FlutterwaveProvider extends AbstractPaymentProvider
{
    public function code(): string
    {
        return 'flutterwave';
    }

    public function signatureHeader(): string
    {
        return $this->isSandbox() ? 'X-PB-Signature' : 'verif-hash';
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        if ($this->isSandbox()) {
            return parent::verifySignature($rawBody, $signature);
        }

        // TODO[live]: Flutterwave sends the configured secret hash directly.
        return $signature !== null && hash_equals($this->secret(), $signature);
    }

    public function charge(PaymentIntent $intent, array $context = []): ChargeResult
    {
        if ($this->isSandbox()) {
            return parent::charge($intent, $context);
        }

        // TODO[live]: Create a Standard payment and redirect to the hosted link.
        $response = Http::withToken($this->config['secret_key'])
            ->post(rtrim((string) $this->config['base_url'], '/').'/payments', [
                'tx_ref' => $intent->reference,
                'amount' => $intent->amount,
                'currency' => $intent->currency,
                'redirect_url' => route('deposit.index'),
                'customer' => ['email' => $context['email'] ?? '', 'phonenumber' => $context['phone'] ?? ''],
                'customizations' => ['title' => config('platform.name').' wallet top-up'],
            ]);

        if ($response->failed() || $response->json('status') !== 'success') {
            return new ChargeResult('failed', message: 'Flutterwave could not start the payment.', raw: $response->json() ?? []);
        }

        return new ChargeResult(
            'processing',
            providerReference: $intent->reference,
            redirectUrl: $response->json('data.link'),
            raw: $response->json() ?? [],
        );
    }

    public function parseWebhook(array $payload): WebhookResult
    {
        if ($this->isSandbox()) {
            return parent::parseWebhook($payload);
        }

        // TODO[live]: map Flutterwave's "charge.completed" event shape.
        $data = $payload['data'] ?? [];
        $status = (($data['status'] ?? '') === 'successful') ? 'succeeded' : 'failed';

        return new WebhookResult(
            eventId: (string) ($data['id'] ?? $payload['id'] ?? ''),
            reference: $data['tx_ref'] ?? null,
            status: $status,
            providerReference: (string) ($data['flw_ref'] ?? ''),
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: $data['currency'] ?? null,
            eventType: $payload['event'] ?? null,
            raw: $payload,
        );
    }

    /** Real, non-money-moving check: read the account balance with the configured secret key. */
    public function testConnection(): array
    {
        if ($this->isSandbox()) {
            return parent::testConnection();
        }

        foreach (['secret_key', 'base_url'] as $key) {
            if (empty($this->config[$key])) {
                return ['ok' => false, 'message' => "Missing required credential: {$key}."];
            }
        }

        try {
            $response = Http::withToken($this->config['secret_key'])
                ->get(rtrim((string) $this->config['base_url'], '/').'/balances');

            return $response->successful()
                ? ['ok' => true, 'message' => 'Authenticated successfully.']
                : ['ok' => false, 'message' => 'Flutterwave rejected the credentials (HTTP '.$response->status().').'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Connection failed: '.$e->getMessage()];
        }
    }
}
