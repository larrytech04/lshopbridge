<?php

namespace Database\Factories;

use App\Models\CryptoWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CryptoWallet>
 */
class CryptoWalletFactory extends Factory
{
    protected $model = CryptoWallet::class;

    public function definition(): array
    {
        return [
            'asset' => 'USDT',
            'network' => 'TRC20',
            'address' => fake()->unique()->regexify('T[A-Za-z0-9]{33}'),
            'is_active' => true,
            'purpose' => 'collection',
            'auto_reconciliation' => false,
            'sort' => 0,
        ];
    }
}
