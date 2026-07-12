<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentProvider;
use InvalidArgumentException;

/**
 * Resolves a configured payment provider driver by its code. Provider classes +
 * (non-secret) config come from config/payments.php; secrets come from env.
 */
class PaymentManager
{
    /** @var array<string, PaymentProvider> */
    private array $resolved = [];

    public function driver(string $code): PaymentProvider
    {
        if (isset($this->resolved[$code])) {
            return $this->resolved[$code];
        }

        $config = config("payments.providers.{$code}");

        if (! $config || empty($config['driver'])) {
            throw new InvalidArgumentException("Unknown payment provider [{$code}].");
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

        return $this->resolved[$code] = new $driver($config);
    }

    public function exists(string $code): bool
    {
        return (bool) config("payments.providers.{$code}");
    }

    /** @return array<string, array> */
    public function all(): array
    {
        return config('payments.providers', []);
    }
}
