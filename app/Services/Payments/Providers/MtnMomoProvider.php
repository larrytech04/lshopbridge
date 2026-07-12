<?php

namespace App\Services\Payments\Providers;

use App\Models\PaymentIntent;
use App\Services\Payments\DTO\ChargeResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * MTN Mobile Money — Collections API (RequestToPay).
 * Docs: https://momodeveloper.mtn.com/
 */
class MtnMomoProvider extends AbstractPaymentProvider
{
    public function code(): string
    {
        return 'mtn_momo';
    }

    public function charge(PaymentIntent $intent, array $context = []): ChargeResult
    {
        if ($this->isSandbox()) {
            return parent::charge($intent, $context);
        }

        // TODO[live]: Implement MTN MoMo Collections "RequestToPay".
        //  1. Obtain an OAuth access token (POST /collection/token/ with the
        //     API user + API key, authenticated by the subscription key).
        //  2. POST /collection/v1_0/requesttopay with header X-Reference-Id
        //     (a UUID = our externalId), body { amount, currency, externalId,
        //     payer: { partyIdType: MSISDN, partyId: <phone> }, ... }.
        //  3. MTN pushes the result to our webhook (or poll the reference).
        $externalId = (string) Str::uuid();

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->config['subscription_key'],
            'X-Reference-Id' => $externalId,
            'X-Target-Environment' => 'production',
        ])->withToken($this->accessToken())
            ->post(rtrim((string) $this->config['base_url'], '/').'/collection/v1_0/requesttopay', [
                'amount' => (string) $intent->amount,
                'currency' => $intent->currency,
                'externalId' => $intent->reference,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $context['phone'] ?? '',
                ],
                'payerMessage' => 'Wallet top-up '.$intent->reference,
                'payeeNote' => config('platform.name'),
            ]);

        if ($response->failed()) {
            return new ChargeResult('failed', message: 'MTN MoMo declined the request.', raw: $response->json() ?? []);
        }

        return new ChargeResult('processing', providerReference: $externalId, raw: $response->json() ?? []);
    }

    private function accessToken(): string
    {
        // TODO[live]: cache and refresh the OAuth token.
        $res = Http::withBasicAuth($this->config['api_user'], $this->config['api_key'])
            ->withHeaders(['Ocp-Apim-Subscription-Key' => $this->config['subscription_key']])
            ->post(rtrim((string) $this->config['base_url'], '/').'/collection/token/');

        return (string) ($res->json('access_token') ?? '');
    }
}
