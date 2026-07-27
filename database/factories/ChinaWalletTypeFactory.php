<?php

namespace Database\Factories;

use App\Models\ChinaWalletType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChinaWalletType>
 */
class ChinaWalletTypeFactory extends Factory
{
    protected $model = ChinaWalletType::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('wallet_????'),
            'name' => fake()->words(2, true),
            'account_identifier_type' => 'custom',
            'qr_required' => false,
            'account_name_required' => true,
            'phone_required' => false,
            'manual_funding' => true,
            'automated_funding' => false,
            'is_active' => true,
            'sort' => 0,
        ];
    }
}
