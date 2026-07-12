<?php

namespace App\Services\Payments\Providers;

use App\Models\PaymentIntent;
use App\Services\Payments\DTO\ChargeResult;

/**
 * Crypto payment gateway placeholder (e.g. NOWPayments, Coinbase Commerce,
 * BitPay). The user is shown a hosted invoice / address; the gateway calls our
 * webhook on confirmation.
 */
class CryptoGatewayProvider extends AbstractPaymentProvider
{
    public function code(): string
    {
        return 'crypto';
    }

    public function charge(PaymentIntent $intent, array $context = []): ChargeResult
    {
        if ($this->isSandbox()) {
            return parent::charge($intent, $context);
        }

        // TODO[live]: Create a crypto invoice with your chosen gateway and return
        // the hosted invoice/payment URL. The gateway will POST an IPN/webhook
        // (often HMAC-signed) which the webhook pipeline already verifies.
        throw new \RuntimeException('Crypto gateway live mode is not configured yet. Add the provider API in CryptoGatewayProvider::charge().');
    }
}
