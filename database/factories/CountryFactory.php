<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->country(),
            'iso2' => strtoupper(fake()->unique()->lexify('??')),
            'dial_code' => '+'.fake()->numberBetween(1, 299),
            'currency_code' => 'USD',
            'flag_emoji' => '🏳️',
            'is_active' => true,
            'is_blocked' => false,
            'launch_status' => 'active',
            'sort' => 0,
        ];
    }
}
