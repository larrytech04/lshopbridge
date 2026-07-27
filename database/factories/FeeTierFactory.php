<?php

namespace Database\Factories;

use App\Models\Fee;
use App\Models\FeeTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeTier>
 */
class FeeTierFactory extends Factory
{
    protected $model = FeeTier::class;

    public function definition(): array
    {
        return [
            'fee_id' => Fee::factory(),
            'min_amount' => 0,
            'max_amount' => null,
            'percent' => 1.5,
            'fixed' => 0,
            'sort' => 0,
        ];
    }
}
