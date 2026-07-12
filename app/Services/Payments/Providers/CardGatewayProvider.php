<?php

namespace App\Services\Payments\Providers;

use App\Models\PaymentIntent;
use App\Services\Payments\DTO\ChargeResult;

/**
 * Card / prepaid-card gateway placeholder (e.g. Stripe, Paystack, a processor
 * issuing prepaid cards). Hosted checkout + webhook confirmation.
 */
class CardGatewayProvider extends AbstractPaymentProvider
{
    public function code(): string
    {
        return 'card';
    }

    public function charge(PaymentIntent $intent, array $context = []): ChargeResult
    {
        if ($this->isSandbox()) {
            return parent::charge($intent, $context);
        }

        // TODO[live]: Create a hosted checkout session with your card processor
        // and return its redirect URL. Confirm via the processor's signed webhook.
        throw new \RuntimeException('Card gateway live mode is not configured yet. Add the processor API in CardGatewayProvider::charge().');
    }
}
