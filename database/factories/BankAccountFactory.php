<?php

namespace Database\Factories;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'bank_name' => fake()->company().' Bank',
            'account_name' => fake()->name(),
            'account_number' => fake()->unique()->numerify('##########'),
            'is_active' => true,
            'purpose' => 'collection',
            'auto_reconciliation' => false,
            'sort' => 0,
        ];
    }
}
