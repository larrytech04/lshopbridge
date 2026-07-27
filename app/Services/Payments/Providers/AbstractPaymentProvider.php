<?php

namespace App\Services\Payments\Providers;

use App\Models\PaymentIntent;
use App\Services\Payments\Contracts\PaymentProvider;
use App\Services\Payments\DTO\ChargeResult;
use App\Services\Payments\DTO\WebhookResult;
use Illuminate\Support\Str;

/**
 * Shared behaviour for all collection providers:
 *  - HMAC-SHA256 webhook signing/verification (also used to sign the sandbox
 *    payloads we replay through the real webhook pipeline).
 *  - A safe sandbox charge that returns "processing" plus a ready-to-replay
 *    signed webhook, so the full automation path works with zero live calls.
 *
 * Concrete providers override charge()/parseWebhook() to add the real HTTP
 * calls inside the clearly-marked `// TODO[live]` sections.
 */
abstract class AbstractPaymentProvider implements PaymentProvider
{
    public function __construct(protected array $config) {}

    abstract public function code(): string;

    public function isSandbox(): bool
    {
        return ($this->config['mode'] ?? 'sandbox') !== 'live';
    }

    public function signatureHeader(): string
    {
        return 'X-PB-Signature';
    }

    protected function secret(): string
    {
        return (string) ($this->config['webhook_secret'] ?? '');
    }

    public function sign(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, $this->secret());
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        if ($signature === null || $this->secret() === '') {
            return false;
        }

        return hash_equals($this->sign($rawBody), $signature);
    }

    /**
     * Default charge = sandbox simulation. Live providers override this and wrap
     * a real API call, falling back to parent::charge() while in sandbox mode.
     */
    public function charge(PaymentIntent $intent, array $context = []): ChargeResult
    {
        $providerReference = strtoupper($this->code().'_'.Str::random(12));

        // Build the exact webhook the real provider WOULD send on success, so the
        // platform can replay it through WebhookController and exercise the full
        // signature-verify -> settle path. (See SandboxSimulator.)
        $payload = [
            'event' => 'payment.succeeded',
            'event_id' => 'evt_'.Str::random(16),
            'reference' => $intent->reference,
            'provider_reference' => $providerReference,
            'amount' => (float) $intent->amount,
            'currency' => $intent->currency,
            'status' => 'succeeded',
        ];

        return new ChargeResult(
            status: 'processing',
            providerReference: $providerReference,
            redirectUrl: null,
            message: 'Sandbox charge accepted, awaiting simulated webhook confirmation.',
            raw: ['sandbox' => true],
            sandboxWebhook: $payload,
        );
    }

    /**
     * Default parser understands the unified sandbox payload. Live providers
     * override to map their real payload shape onto WebhookResult.
     */
    public function parseWebhook(array $payload): WebhookResult
    {
        $status = ($payload['status'] ?? 'failed') === 'succeeded' ? 'succeeded' : 'failed';

        return new WebhookResult(
            eventId: $payload['event_id'] ?? null,
            reference: $payload['reference'] ?? null,
            status: $status,
            providerReference: $payload['provider_reference'] ?? null,
            amount: isset($payload['amount']) ? (float) $payload['amount'] : null,
            currency: $payload['currency'] ?? null,
            eventType: $payload['event'] ?? null,
            raw: $payload,
        );
    }

    /**
     * Default: sandbox mode has no live credentials to test; live mode with no
     * override is honestly reported as unimplemented. Concrete providers that
     * support a real, safe (non-money-moving) credential check override this.
     */
    public function testConnection(): array
    {
        if ($this->isSandbox()) {
            return ['ok' => true, 'message' => 'Sandbox mode active, no live credentials to test.'];
        }

        return ['ok' => false, 'message' => 'Live connection testing is not implemented for this provider yet.'];
    }
}
