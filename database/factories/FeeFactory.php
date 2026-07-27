<?php

namespace Database\Factories;

use App\Models\Fee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fee>
 */
class FeeFactory extends Factory
{
    protected $model = Fee::class;

    public function definition(): array
    {
        return [
            'name' => 'Standard funding fee',
            'applies_to' => 'funding',
            'type' => 'percent',
            'value' => 2.5,
            'min_fee' => 0,
            'fee_payer' => 'customer',
            'is_active' => true,
        ];
    }
}
