<?php

namespace Database\Factories;

use App\Models\ShopProduct;
use App\Models\ShopVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopVariant>
 */
class ShopVariantFactory extends Factory
{
    protected $model = ShopVariant::class;

    public function definition(): array
    {
        return [
            'shop_product_id' => ShopProduct::factory(),
            'name' => 'Standard',
            'price' => 10000,
            'currency' => 'XAF',
            'is_active' => true,
        ];
    }
}
