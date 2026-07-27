<?php

namespace Database\Factories;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition(): array
    {
        return [
            'base_currency' => 'XAF',
            'quote_currency' => 'CNY',
            'rate' => 0.0121,
            'margin_percent' => 1.5,
            'margin_type' => 'percentage',
            'rate_source' => 'manual',
            'is_active' => true,
        ];
    }
}
