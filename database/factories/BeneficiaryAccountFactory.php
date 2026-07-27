<?php

namespace Database\Factories;

use App\Models\BeneficiaryAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiaryAccount>
 */
class BeneficiaryAccountFactory extends Factory
{
    protected $model = BeneficiaryAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'app_type' => 'alipay',
            'account_name' => fake()->name(),
            'account_id' => fake()->unique()->userName().'@alipay.cn',
            'status' => 'pending',
            'is_default' => false,
            'resubmission_allowed' => true,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved', 'reviewed_at' => now()]);
    }
}
