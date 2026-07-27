<?php

namespace Database\Factories;

use App\Models\PaymentProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentProvider>
 */
class PaymentProviderFactory extends Factory
{
    protected $model = PaymentProvider::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('provider_????'),
            'name' => fake()->company(),
            'kind' => 'collection',
            'mode' => 'sandbox',
            'is_active' => true,
            'supports' => ['momo'],
            'meta' => [],
            'priority' => 0,
        ];
    }
}
