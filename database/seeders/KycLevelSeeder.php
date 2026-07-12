<?php

namespace Database\Seeders;

use App\Models\KycLevel;
use Illuminate\Database\Seeder;

class KycLevelSeeder extends Seeder
{
    public function run(): void
    {
        $requirements = [
            0 => ['Email registration'],
            1 => ['Verified email', 'Verified phone (OTP)'],
            2 => ['Government ID', 'Selfie verification', 'Residential address'],
            3 => ['Business registration', 'Agent verification'],
        ];

        foreach (config('platform.kyc_levels') as $level => $data) {
            KycLevel::updateOrCreate(['level' => $level], [
                'name' => $data['name'],
                'description' => "Level {$level} verification tier.",
                'requirements' => $requirements[$level] ?? [],
                'daily_limit' => $data['daily'],
                'monthly_limit' => $data['monthly'],
                'per_transaction_limit' => $data['per_tx'],
                'currency' => config('platform.base_currency'),
                'is_active' => true,
            ]);
        }
    }
}
