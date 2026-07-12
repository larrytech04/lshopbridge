<?php

namespace App\Services\Payments;

use App\Services\Payments\DTO\ChargeResult;
use App\Services\Payments\Providers\AbstractPaymentProvider;

/**
 * In sandbox mode, providers return a ready-to-replay signed webhook payload
 * (ChargeResult::$sandboxWebhook). This service signs it with the same secret a
 * real provider would use and feeds it through the REAL WebhookProcessor, so the
 * entire automation path (verify -> dedupe -> settle) is exercised offline.
 *
 * This is the ONLY sandbox shortcut; production payments arrive via the genuine
 * provider webhook hitting WebhookController.
 */
class SandboxSimulator
{
    public function __construct(
        private PaymentManager $payments,
        private WebhookProcessor $processor,
    ) {}

    public function replay(string $providerCode, ChargeResult $charge): void
    {
        if (! $charge->sandboxWebhook) {
            return;
        }

        $provider = $this->payments->driver($providerCode);

        // Only the abstract sandbox provider exposes signing; live drivers skip.
        if (! $provider instanceof AbstractPaymentProvider) {
            return;
        }

        $rawBody = json_encode($charge->sandboxWebhook);
        $signature = $provider->sign($rawBody);

        $this->processor->handle(
            $providerCode,
            $rawBody,
            $signature,
            ['X-Sandbox' => 'true'],
            '127.0.0.1',
        );
    }
}
