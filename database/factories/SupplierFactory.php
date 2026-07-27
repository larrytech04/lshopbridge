<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => 'Test Supplier '.$this->faker->unique()->numberBetween(1000, 9999),
            'code' => 'sup-'.$this->faker->unique()->numberBetween(1000, 9999),
            'is_active' => true,
        ];
    }
}
