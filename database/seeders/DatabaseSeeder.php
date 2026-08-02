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
            ChinaWalletTypeSeeder::class,
            RiskRuleSeeder::class,
            SettingSeeder::class,
            ContentSeeder::class,
            GuideSeeder::class,
            ShopSeeder::class,
            EsimDeviceSeeder::class,
            DemoUserSeeder::class,
            KycDecisionTemplateSeeder::class,
            KycVerificationSeeder::class,
        ]);
    }
}
