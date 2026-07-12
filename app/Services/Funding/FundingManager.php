<?php

namespace App\Services\Funding;

use App\Services\Funding\Contracts\FundingProvider;
use InvalidArgumentException;

/**
 * Resolves the configured China-wallet funding provider.
 */
class FundingManager
{
    public function provider(?string $code = null): FundingProvider
    {
        $code ??= config('funding.default', 'alipay');
        $config = config("funding.providers.{$code}");

        if (! $config || empty($config['driver'])) {
            throw new InvalidArgumentException("Unknown funding provider [{$code}].");
        }

        // Admin-entered credentials (DB, encrypted) override .env config.
        $record = \App\Models\PaymentProvider::where('code', $code)->first();
        if ($record) {
            $config = array_merge($config, $record->overrides());
            if ($record->mode) {
                $config['mode'] = $record->mode;
            }
        }

        $driver = $config['driver'];

        return new $driver($config);
    }
}
