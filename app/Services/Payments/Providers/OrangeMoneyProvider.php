<?php

namespace App\Services\Payments\Providers;

use App\Models\PaymentIntent;
use App\Services\Payments\DTO\ChargeResult;
use Illuminate\Support\Facades\Http;

/**
 * Orange Money — Web Payment / Mobile Money API.
 * Docs: https://developer.orange.com/apis/
 */
class OrangeMoneyProvider extends AbstractPaymentProvider
{
    public function code(): string
    {
        return 'orange_money';
    }

    public function charge(PaymentIntent $intent, array $context = []): ChargeResult
    {
        if ($this->isSandbox()) {
            return parent::charge($intent, $context);
        }

        // TODO[live]: Implement Orange Money Web Payment.
        //  1. POST /oauth/v3/token (client_credentials) for a bearer token.
        //  2. POST /orange-money-webpay/.../webpayment with { amount, currency,
        //     order_id => our reference, return_url, cancel_url, notif_url }.
        //  3. Redirect the user to payment_url; Orange calls notif_url (webhook).
        $response = Http::withToken($this->accessToken())
            ->post(rtrim((string) $this->config['base_url'], '/').'/orange-money-webpay/dev/v1/webpayment', [
                'merchant_key' => $this->config['client_id'],
                'currency' => $intent->currency,
                'order_id' => $intent->reference,
                'amount' => (int) $intent->amount,
                'return_url' => route('deposit.index'),
                'cancel_url' => route('deposit.index'),
                'notif_url' => route('webhooks.payments', ['provider' => 'orange_money']),
            ]);

        if ($response->failed()) {
            return new ChargeResult('failed', message: 'Orange Money declined the request.', raw: $response->json() ?? []);
        }

        return new ChargeResult(
            'processing',
            providerReference: $response->json('pay_token'),
            redirectUrl: $response->json('payment_url'),
            raw: $response->json() ?? [],
        );
    }

    private function accessToken(): string
    {
        $res = Http::asForm()->withBasicAuth($this->config['client_id'], $this->config['client_secret'])
            ->post(rtrim((string) $this->config['base_url'], '/').'/oauth/v3/token', ['grant_type' => 'client_credentials']);

        return (string) ($res->json('access_token') ?? '');
    }
}
