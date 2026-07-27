<?php

namespace Database\Seeders;

use App\Models\EsimDevice;
use Illuminate\Database\Seeder;

/**
 * Publicly documented eSIM device compatibility (Apple/Samsung/Google support
 * pages, GSMA device data), curated by hand for Phase 1 — not a live provider
 * feed, hence source=manual throughout. Deliberately includes the real
 * regional/carrier caveats (e.g. mainland China iPhones, US carrier-locked
 * Androids) rather than a blanket "supported" flag, per the spec's ban on
 * fake compatibility guarantees.
 */
class EsimDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            // ---- Apple ----
            ['brand' => 'Apple', 'model' => 'iPhone 15 (all models)', 'esim_supported' => true, 'min_os_version' => 'iOS 17', 'dual_sim_support' => true, 'max_active_esims' => 8, 'installation_method' => 'qr_manual', 'regional_restriction' => 'US models have no physical SIM tray and are eSIM-only.', 'carrier_lock_note' => null],
            ['brand' => 'Apple', 'model' => 'iPhone 14 (all models)', 'esim_supported' => true, 'min_os_version' => 'iOS 16', 'dual_sim_support' => true, 'max_active_esims' => 8, 'installation_method' => 'qr_manual', 'regional_restriction' => 'US models have no physical SIM tray and are eSIM-only.', 'carrier_lock_note' => null],
            ['brand' => 'Apple', 'model' => 'iPhone 13 (all models)', 'esim_supported' => true, 'min_os_version' => 'iOS 15', 'dual_sim_support' => true, 'max_active_esims' => 8, 'installation_method' => 'qr_manual', 'regional_restriction' => 'Mainland China and Hong Kong models do not support eSIM (dual physical nano-SIM instead).', 'carrier_lock_note' => null],
            ['brand' => 'Apple', 'model' => 'iPhone 12 (all models)', 'esim_supported' => true, 'min_os_version' => 'iOS 14', 'dual_sim_support' => true, 'max_active_esims' => 8, 'installation_method' => 'qr_manual', 'regional_restriction' => 'Mainland China and Hong Kong models do not support eSIM.', 'carrier_lock_note' => null],
            ['brand' => 'Apple', 'model' => 'iPhone 11 (all models)', 'esim_supported' => true, 'min_os_version' => 'iOS 13', 'dual_sim_support' => true, 'max_active_esims' => 2, 'installation_method' => 'qr_manual', 'regional_restriction' => 'Mainland China models do not support eSIM.', 'carrier_lock_note' => null],
            ['brand' => 'Apple', 'model' => 'iPhone XS / XS Max / XR', 'esim_supported' => true, 'min_os_version' => 'iOS 12.1', 'dual_sim_support' => true, 'max_active_esims' => 2, 'installation_method' => 'qr_manual', 'regional_restriction' => 'Mainland China models do not support eSIM.', 'carrier_lock_note' => null],
            ['brand' => 'Apple', 'model' => 'iPhone X and earlier', 'esim_supported' => false, 'min_os_version' => null, 'dual_sim_support' => false, 'max_active_esims' => 0, 'installation_method' => null, 'regional_restriction' => null, 'carrier_lock_note' => null],
            ['brand' => 'Apple', 'model' => 'iPad (5th generation Wi-Fi + Cellular and later)', 'esim_supported' => true, 'min_os_version' => 'iPadOS 12.1', 'dual_sim_support' => false, 'max_active_esims' => 1, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => null],

            // ---- Samsung ----
            ['brand' => 'Samsung', 'model' => 'Galaxy S24 / S24+ / S24 Ultra', 'esim_supported' => true, 'min_os_version' => 'Android 14', 'dual_sim_support' => true, 'max_active_esims' => 5, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => 'Some US carrier-locked units (e.g. certain prepaid variants) ship with eSIM disabled by the carrier.'],
            ['brand' => 'Samsung', 'model' => 'Galaxy S23 / S23+ / S23 Ultra', 'esim_supported' => true, 'min_os_version' => 'Android 13', 'dual_sim_support' => true, 'max_active_esims' => 5, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => 'Some US carrier-locked units ship with eSIM disabled by the carrier.'],
            ['brand' => 'Samsung', 'model' => 'Galaxy S22 / S22+ / S22 Ultra', 'esim_supported' => true, 'min_os_version' => 'Android 12', 'dual_sim_support' => true, 'max_active_esims' => 5, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => 'Some US carrier-locked units ship with eSIM disabled by the carrier.'],
            ['brand' => 'Samsung', 'model' => 'Galaxy S21 / S21+ / S21 Ultra', 'esim_supported' => true, 'min_os_version' => 'Android 11', 'dual_sim_support' => true, 'max_active_esims' => 5, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => 'Some US carrier-locked units ship with eSIM disabled by the carrier.'],
            ['brand' => 'Samsung', 'model' => 'Galaxy S20 / S20+ / S20 Ultra', 'esim_supported' => true, 'min_os_version' => 'Android 10', 'dual_sim_support' => true, 'max_active_esims' => 5, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => 'Some US carrier-locked units ship with eSIM disabled by the carrier.'],
            ['brand' => 'Samsung', 'model' => 'Galaxy Z Fold / Z Flip (all generations)', 'esim_supported' => true, 'min_os_version' => 'Android 10', 'dual_sim_support' => true, 'max_active_esims' => 5, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => 'Some carrier-locked units ship with eSIM disabled.'],
            ['brand' => 'Samsung', 'model' => 'Galaxy S10 / S10+ / S10e', 'esim_supported' => false, 'min_os_version' => null, 'dual_sim_support' => false, 'max_active_esims' => 0, 'installation_method' => null, 'regional_restriction' => null, 'carrier_lock_note' => 'Hardware does not support eSIM in most regions.'],

            // ---- Google ----
            ['brand' => 'Google', 'model' => 'Pixel 8 / 8 Pro / 8a', 'esim_supported' => true, 'min_os_version' => 'Android 14', 'dual_sim_support' => true, 'max_active_esims' => 5, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => null],
            ['brand' => 'Google', 'model' => 'Pixel 7 / 7 Pro / 7a', 'esim_supported' => true, 'min_os_version' => 'Android 13', 'dual_sim_support' => true, 'max_active_esims' => 5, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => null],
            ['brand' => 'Google', 'model' => 'Pixel 6 / 6 Pro / 6a', 'esim_supported' => true, 'min_os_version' => 'Android 12', 'dual_sim_support' => true, 'max_active_esims' => 5, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => null],
            ['brand' => 'Google', 'model' => 'Pixel 5 / 5a / 4 / 4a / 3 / 3a', 'esim_supported' => true, 'min_os_version' => 'Android 9', 'dual_sim_support' => true, 'max_active_esims' => 2, 'installation_method' => 'qr_manual', 'regional_restriction' => null, 'carrier_lock_note' => null],
            ['brand' => 'Google', 'model' => 'Pixel 2 and earlier', 'esim_supported' => false, 'min_os_version' => null, 'dual_sim_support' => false, 'max_active_esims' => 0, 'installation_method' => null, 'regional_restriction' => null, 'carrier_lock_note' => null],

            // ---- Huawei (notable gap: newer Huawei models lost Google/eSIM support region-dependently) ----
            ['brand' => 'Huawei', 'model' => 'P40 / P40 Pro, Mate 40 Pro (select markets)', 'esim_supported' => true, 'min_os_version' => null, 'dual_sim_support' => true, 'max_active_esims' => 1, 'installation_method' => 'qr_manual', 'regional_restriction' => 'eSIM availability varies significantly by market and firmware; confirm with the device settings before purchase.', 'carrier_lock_note' => null],
        ];

        foreach ($devices as $d) {
            EsimDevice::updateOrCreate(
                ['brand' => $d['brand'], 'model' => $d['model']],
                $d + ['source' => 'manual', 'status' => 'active', 'verified_date' => now()->subMonths(2)->toDateString()]
            );
        }
    }
}
