<?php

namespace App\Services\Admin;

use App\Models\Currency;
use App\Models\User;
use App\Services\Audit\AuditLogger;

/**
 * Currency metadata and availability only — exchange rates between
 * currencies are managed on the existing Exchange Rates page, not here.
 */
class CurrencyAdminService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(array $data, User $admin): Currency
    {
        $currency = Currency::create($this->normalize($data) + ['updated_by' => $admin->id]);
        $this->audit->log('admin.currency.created', "Created currency {$currency->code}", $currency, [], $admin->id);

        return $currency;
    }

    public function update(Currency $currency, array $data, User $admin): Currency
    {
        // The code is the join key used by Country.currency_code, PaymentMethod
        // pricing, and the Exchange Rates table — never rename it here.
        unset($data['code']);
        $currency->update($this->normalize($data) + ['updated_by' => $admin->id]);
        $this->audit->log('admin.currency.updated', "Updated currency {$currency->code}", $currency, [], $admin->id);

        return $currency->fresh();
    }

    private function normalize(array $data): array
    {
        foreach (['is_active', 'wallet_enabled', 'deposit_enabled', 'marketplace_enabled', 'reporting_currency_enabled'] as $flag) {
            $data[$flag] = ! empty($data[$flag]);
        }

        return $data;
    }

    public function setActive(Currency $currency, bool $active, User $admin): Currency
    {
        $currency->update(['is_active' => $active, 'updated_by' => $admin->id]);
        $this->audit->log('admin.currency.'.($active ? 'activated' : 'deactivated'), "{$currency->code} ".($active ? 'activated' : 'deactivated'), $currency, [], $admin->id);

        return $currency->fresh();
    }

    public function summary(): array
    {
        $currencies = Currency::all();

        return [
            'total' => $currencies->count(),
            'active' => $currencies->where('is_active', true)->count(),
            'wallet_enabled' => $currencies->where('wallet_enabled', true)->count(),
            'reporting' => $currencies->where('reporting_currency_enabled', true)->count(),
        ];
    }
}
