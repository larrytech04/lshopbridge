<?php

namespace Database\Factories;

use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopOrder>
 */
class ShopOrderFactory extends Factory
{
    protected $model = ShopOrder::class;

    public function definition(): array
    {
        return [
            'reference' => 'PB-SHP-'.$this->faker->unique()->numerify('########'),
            'user_id' => User::factory(),
            'status' => 'paid',
            'subtotal' => 10000,
            'fee' => 0,
            'total' => 10000,
            'currency' => 'XAF',
            'payment_source' => 'wallet',
            'paid_at' => now(),
        ];
    }
}
