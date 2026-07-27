<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->currencyCode(),
            'symbol' => '$',
            'decimals' => 2,
            'thousands_separator' => ',',
            'decimal_separator' => '.',
            'is_active' => true,
            'wallet_enabled' => true,
            'deposit_enabled' => true,
            'marketplace_enabled' => true,
            'reporting_currency_enabled' => false,
        ];
    }
}
