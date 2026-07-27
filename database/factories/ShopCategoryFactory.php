<?php

namespace Database\Factories;

use App\Models\ShopCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopCategory>
 */
class ShopCategoryFactory extends Factory
{
    protected $model = ShopCategory::class;

    public function definition(): array
    {
        return [
            'name' => 'Test Category '.$this->faker->unique()->numberBetween(1000, 9999),
            'is_active' => true,
        ];
    }
}
