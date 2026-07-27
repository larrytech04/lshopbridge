<?php

namespace App\Services\Funding\Providers;

use App\Models\FundingRequest;
use App\Services\Funding\Contracts\FundingProvider;
use App\Services\Funding\DTO\FundingResult;
use Illuminate\Support\Str;

/**
 * Alipay / China wallet funding engine.
 *
 * IMPORTANT: There is no public "send money to any Alipay account" API. In
 * production this integrates with a licensed cross-border payout partner /
 * PSP that settles into Alipay/WeChat. This class is the single seam where that
 * partner API is wired, keep all partner specifics here.
 *
 * Sandbox mode simulates an instant, successful payout so the end-to-end
 * automation (payment -> auto-funding) is fully demonstrable offline.
 */
class AlipayFundingProvider implements FundingProvider
{
    public function __construct(protected array $config) {}

    public function code(): string
    {
        return 'alipay';
    }

    public function isSandbox(): bool
    {
        return ($this->config['mode'] ?? 'sandbox') !== 'live';
    }

    public function submit(FundingRequest $request): FundingResult
    {
        if ($this->isSandbox()) {
            return $this->simulate($request);
        }

        // TODO[live]: Call the cross-border payout partner API.
        //  - Authenticate (partner_id + api_key/secret, often RSA/HMAC signed).
        //  - Submit payout: { out_trade_no => $request->reference, payee =>
        //    { app: $request->app_type, account: $request->recipient_account,
        //      name: $request->recipient_name }, amount => $request->target_amount,
        //      currency => 'CNY', notify_url => webhook }.
        //  - If the partner settles asynchronously, return status 'processing'
        //    and complete the request from the funding webhook.
        throw new \RuntimeException('Alipay funding live mode is not configured. Wire the payout partner API in AlipayFundingProvider::submit().');
    }

    /** No payout partner API is wired yet (see submit()), so live mode is honestly reported as untestable. */
    public function testConnection(): array
    {
        if ($this->isSandbox()) {
            return ['ok' => true, 'message' => 'Sandbox mode active, no live credentials to test.'];
        }

        return ['ok' => false, 'message' => 'Live connection testing is not implemented for this provider yet — no payout partner API is wired.'];
    }

    private function simulate(FundingRequest $request): FundingResult
    {
        // Deterministic-ish sandbox behaviour: a tiny fraction route to manual
        // review so admins can exercise that path; everything else succeeds.
        $bucket = crc32($request->reference) % 100;

        if ($bucket < 5) {
            return new FundingResult('manual', message: 'Sandbox: routed to manual review for demonstration.');
        }

        return new FundingResult(
            status: 'succeeded',
            providerReference: 'ALI-'.strtoupper(Str::random(14)),
            receipt: 'SANDBOX RECEIPT: '.money($request->target_amount, $request->target_currency).' delivered to '.$request->recipient_account,
            message: 'Sandbox payout settled instantly.',
            raw: ['sandbox' => true],
        );
    }
}
