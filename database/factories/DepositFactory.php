<?php

namespace Database\Factories;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    protected $model = Deposit::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 1000, 200000);

        return [
            'reference' => 'PB-DEP-'.strtoupper(fake()->unique()->bothify('########')),
            'user_id' => User::factory(),
            'amount' => $amount,
            'fee' => 0,
            'net_amount' => $amount,
            'currency' => 'XAF',
            'status' => 'pending',
            'is_automated' => false,
            'risk_flagged' => false,
        ];
    }

    public function automated(): static
    {
        return $this->state(fn () => [
            'is_automated' => true,
            'provider_code' => 'mtn_momo',
            'provider_reference' => 'PROV-'.strtoupper(fake()->bothify('??######')),
            'status' => 'processing',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }
}
