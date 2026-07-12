<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountryCurrencySeeder::class,
            KycLevelSeeder::class,
            RateFeeSeeder::class,
            PaymentSeeder::class,
            RiskRuleSeeder::class,
            SettingSeeder::class,
            ContentSeeder::class,
            ShopSeeder::class,
            DemoUserSeeder::class,
        ]);
    }
}
