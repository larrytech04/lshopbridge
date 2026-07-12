<?php

namespace App\Services\Funding\Contracts;

use App\Models\FundingRequest;
use App\Services\Funding\DTO\FundingResult;

/**
 * Contract for any China-wallet funding provider (Alipay / WeChat Pay / other).
 * Built provider-ready: sandbox simulates an instant payout; live mode wires the
 * partner API once an agreement is in place.
 */
interface FundingProvider
{
    public function code(): string;

    public function isSandbox(): bool;

    /** Submit a payout to the recipient's China wallet. */
    public function submit(FundingRequest $request): FundingResult;
}
