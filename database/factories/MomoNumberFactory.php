<?php

namespace Database\Factories;

use App\Models\MomoNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MomoNumber>
 */
class MomoNumberFactory extends Factory
{
    protected $model = MomoNumber::class;

    public function definition(): array
    {
        return [
            'provider' => 'mtn',
            'number' => fake()->unique()->numerify('233#########'),
            'account_name' => fake()->name(),
            'is_active' => true,
            'purpose' => 'collection',
            'auto_reconciliation' => false,
            'sort' => 0,
        ];
    }
}
