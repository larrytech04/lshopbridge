<?php

namespace Database\Seeders;

use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(SettingsService $settings): void
    {
        $defaults = [
            ['site_name', config('platform.name'), 'string'],
            ['tagline', config('platform.tagline'), 'string'],
            ['support_email', 'support@lshopbridge.com', 'string'],
            ['support_phone', '+237 6 70 00 00 00', 'string'],
            ['whatsapp_link', 'https://wa.me/237670000000', 'string'],
            ['telegram_link', '', 'string'],
            ['payments_automation_enabled', '1', 'bool'],
            ['funding_automation_enabled', '1', 'bool'],
            ['require_proof_of_payment', '1', 'bool'],
            ['maintenance_mode', '0', 'bool'],
            ['display_fee_percent', '2.5', 'float'],
        ];

        foreach ($defaults as [$key, $value, $type]) {
            $settings->set($key, $value, $type, 'general');
        }
    }
}
