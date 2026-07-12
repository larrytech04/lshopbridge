<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use App\Models\Fee;
use Illuminate\Database\Seeder;

class RateFeeSeeder extends Seeder
{
    public function run(): void
    {
        // 1 CNY ≈ 83 XAF  =>  1 XAF ≈ 0.0121 CNY
        ExchangeRate::updateOrCreate(
            ['base_currency' => 'XAF', 'quote_currency' => 'CNY'],
            ['rate' => 0.0121, 'margin_percent' => 1.5, 'is_active' => true],
        );
        ExchangeRate::updateOrCreate(
            ['base_currency' => 'NGN', 'quote_currency' => 'CNY'],
            ['rate' => 0.0047, 'margin_percent' => 1.5, 'is_active' => true],
        );
        ExchangeRate::updateOrCreate(
            ['base_currency' => 'GHS', 'quote_currency' => 'CNY'],
            ['rate' => 0.48, 'margin_percent' => 1.5, 'is_active' => true],
        );

        Fee::updateOrCreate(
            ['name' => 'Standard funding fee'],
            ['applies_to' => 'funding', 'type' => 'percent', 'value' => 2.5, 'min_fee' => 100, 'currency' => 'XAF', 'is_active' => true, 'sort' => 1],
        );
        Fee::updateOrCreate(
            ['name' => 'Deposit processing'],
            ['applies_to' => 'deposit', 'type' => 'percent', 'value' => 0, 'min_fee' => 0, 'currency' => 'XAF', 'is_active' => true, 'sort' => 2],
        );
    }
}
