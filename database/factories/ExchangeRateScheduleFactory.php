<?php

namespace Database\Factories;

use App\Models\ExchangeRateSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRateSchedule>
 */
class ExchangeRateScheduleFactory extends Factory
{
    protected $model = ExchangeRateSchedule::class;

    public function definition(): array
    {
        return [
            'base_currency' => 'XAF',
            'quote_currency' => 'CNY',
            'rate' => 0.014,
            'margin_percent' => 1.5,
            'margin_type' => 'percentage',
            'effective_from' => now()->addDays(3),
            'effective_to' => null,
            'status' => 'scheduled',
            'reason' => 'Test schedule',
        ];
    }
}
