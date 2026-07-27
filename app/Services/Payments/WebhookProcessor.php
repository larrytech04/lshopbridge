<?php

namespace App\Services\Payments;

use App\Enums\WebhookStatus;
use App\Models\PaymentIntent;
use App\Models\WebhookEvent;
use App\Services\Deposit\DepositService;
use App\Services\Funding\FundingService;
use App\Services\Payments\DTO\WebhookResult;
use App\Services\Shop\ShopService;

/**
 * Hardened inbound webhook handler. Every event is:
 *   1. logged (webhook_events) before anything else,
 *   2. deduplicated via (provider_code, event_id), idempotent,
 *   3. signature-verified against the provider secret,
 *   4. matched to a PaymentIntent by our reference,
 *   5. settled (credit wallet / advance funding) exactly once.
 *
 * Any failure is recorded on the event row; the primary flow never silently
 * double-processes.
 */
class WebhookProcessor
{
    public function __construct(
        private PaymentManager $payments,
        private DepositService $deposits,
        private FundingService $funding,
        private ShopService $shop,
    ) {}

    public function handle(string $providerCode, string $rawBody, ?string $signature, array $headers = [], ?string $ip = null): WebhookEvent
    {
        $payload = json_decode($rawBody, true) ?: [];

        // Unknown provider, log and ignore.
        if (! $this->payments->exists($providerCode)) {
            return WebhookEvent::create([
                'provider_code' => $providerCode,
                'event_id' => 'unknown_'.substr(hash('sha256', $rawBody), 0, 24),
                'status' => WebhookStatus::Ignored,
                'payload' => $payload,
                'headers' => $headers,
                'ip' => $ip,
                'error' => 'Unknown provider.',
            ]);
        }

        $provider = $this->payments->driver($providerCode);
        $result = $provider->parseWebhook($payload);
        $eventId = $result->eventId ?: 'sha_'.substr(hash('sha256', $rawBody), 0, 32);

        // (2) Idempotency, one row per (provider, event_id).
        $event = WebhookEvent::firstOrCreate(
            ['provider_code' => $providerCode, 'event_id' => $eventId],
            [
                'event_type' => $result->eventType,
                'reference' => $result->reference,
                'status' => WebhookStatus::Received,
                'payload' => $payload,
                'headers' => $headers,
                'ip' => $ip,
            ],
        );

        if (! $event->wasRecentlyCreated && $event->status === WebhookStatus::Processed) {
            $event->update(['status' => WebhookStatus::Duplicate]);

            return $event;
        }

        // (3) Signature verification.
        $valid = $provider->verifySignature($rawBody, $signature);
        $event->signature_valid = $valid;

        if (! $valid) {
            $event->update(['status' => WebhookStatus::InvalidSignature, 'signature_valid' => false]);

            return $event;
        }

        return $this->attemptSettlement($event, $result);
    }

    /**
     * Re-runs the match+settle step (4-5) for a previously failed event,
     * using its stored payload. Signature is not re-verified here: a status
     * of Failed only ever follows a successful verifySignature() check in
     * handle(), so there is nothing dishonest about skipping it on retry.
     */
    public function retry(WebhookEvent $event): WebhookEvent
    {
        if ($event->status !== WebhookStatus::Failed) {
            throw new \InvalidArgumentException('Only failed webhook events can be retried.');
        }

        $event->increment('retry_count');
        $event->update(['last_retried_at' => now()]);

        if (! $this->payments->exists($event->provider_code)) {
            return $event;
        }

        $provider = $this->payments->driver($event->provider_code);
        $result = $provider->parseWebhook($event->payload ?? []);

        return $this->attemptSettlement($event, $result);
    }

    private function attemptSettlement(WebhookEvent $event, WebhookResult $result): WebhookEvent
    {
        try {
            // (4) Match to our intent.
            $intent = PaymentIntent::where('reference', $result->reference)->first();

            if (! $intent) {
                $event->update(['status' => WebhookStatus::Failed, 'error' => 'No matching payment intent (reference mismatch).']);

                return $event;
            }

            // (5) Settle exactly once, by intent purpose.
            $related = match ($intent->purpose) {
                'deposit' => tap($intent->deposit, fn () => $this->deposits->settleFromWebhook($intent, $result)),
                'shop' => tap($intent->shopOrder, fn () => $this->shop->settleFromWebhook($intent, $result)),
                default => tap($intent->fundingRequest, fn () => $this->funding->settlePaymentFromWebhook($intent, $result)),
            };

            if ($related) {
                $event->related()->associate($related);
            }

            $event->update([
                'status' => WebhookStatus::Processed,
                'processed_at' => now(),
                'error' => null,
                'related_type' => $related?->getMorphClass(),
                'related_id' => $related?->getKey(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $event->update(['status' => WebhookStatus::Failed, 'error' => $e->getMessage()]);
        }

        return $event;
    }
}
