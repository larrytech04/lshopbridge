<?php

namespace Database\Factories;

use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopProduct>
 */
class ShopProductFactory extends Factory
{
    protected $model = ShopProduct::class;

    public function definition(): array
    {
        return [
            'shop_category_id' => ShopCategory::factory(),
            'name' => 'Test Product '.$this->faker->unique()->numberBetween(1000, 9999),
            'type' => 'giftcard',
            'status' => 'active',
            'source' => 'native',
            'is_active' => true,
        ];
    }
}
