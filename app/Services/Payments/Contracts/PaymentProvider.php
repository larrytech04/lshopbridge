<?php

namespace App\Services\Payments\Contracts;

use App\Models\PaymentIntent;
use App\Services\Payments\DTO\ChargeResult;
use App\Services\Payments\DTO\WebhookResult;

/**
 * Common surface every collection provider (MoMo, Orange, Flutterwave, crypto,
 * card) implements. Controllers/services never talk to a provider directly —
 * they go through PaymentManager which returns one of these.
 */
interface PaymentProvider
{
    public function code(): string;

    public function isSandbox(): bool;

    /** Ask the provider to collect funds for an intent. */
    public function charge(PaymentIntent $intent, array $context = []): ChargeResult;

    /** Verify the authenticity of an inbound webhook (raw body + signature header). */
    public function verifySignature(string $rawBody, ?string $signature): bool;

    /** The HTTP header the provider sends its signature in. */
    public function signatureHeader(): string;

    /** Normalise a decoded webhook payload into a WebhookResult. */
    public function parseWebhook(array $payload): WebhookResult;
}
