<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('method_????'),
            'name' => fake()->words(2, true),
            'type' => 'momo',
            'provider_code' => 'mtn_momo',
            'description' => fake()->sentence(),
            'currency' => 'XAF',
            'min_amount' => 500,
            'max_amount' => 500000,
            'is_automated' => true,
            'requires_proof' => false,
            'is_active' => true,
            'status' => 'active',
            'deposit_enabled' => true,
            'marketplace_enabled' => true,
            'refund_support' => true,
            'partial_refund_support' => false,
            'requires_manual_review' => false,
            'sort' => 0,
        ];
    }
}
