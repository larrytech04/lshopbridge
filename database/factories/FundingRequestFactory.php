<?php

namespace Database\Factories;

use App\Models\FundingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundingRequest>
 */
class FundingRequestFactory extends Factory
{
    protected $model = FundingRequest::class;

    public function definition(): array
    {
        $source = fake()->randomFloat(2, 5000, 200000);
        $rate = 0.0119;

        return [
            'reference' => 'PB-FND-'.strtoupper(fake()->unique()->bothify('########')),
            'user_id' => User::factory(),
            'app_type' => 'alipay',
            'recipient_name' => fake()->name(),
            'recipient_account' => fake()->userName().'@alipay.cn',
            'source_amount' => $source,
            'source_currency' => 'XAF',
            'exchange_rate' => $rate,
            'target_amount' => round($source * $rate, 2),
            'target_currency' => 'CNY',
            'fee' => 0,
            'total_charged' => $source,
            'funding_source' => 'wallet',
            'status' => 'payment_pending',
            'risk_flagged' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'funding_successful',
            'provider_reference' => 'ALI-'.strtoupper(fake()->bothify('??######')),
            'processed_at' => now(),
        ]);
    }
}
