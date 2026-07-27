<?php

namespace Database\Seeders;

use App\Models\RiskRule;
use Illuminate\Database\Seeder;

class RiskRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['code' => 'blocked_country', 'name' => 'Blocked country', 'description' => 'User is in a country flagged for manual review.', 'action' => 'review', 'severity' => 'high', 'params' => []],
            ['code' => 'unverified_account', 'name' => 'Unverified account over threshold', 'description' => 'KYC not approved and amount above threshold.', 'action' => 'review', 'severity' => 'medium', 'params' => ['amount' => 200000]],
            ['code' => 'large_transaction', 'name' => 'Large transaction', 'description' => 'Transaction near or above the KYC per-tx limit.', 'action' => 'review', 'severity' => 'medium', 'params' => ['multiplier' => 0.9]],
            ['code' => 'velocity', 'name' => 'Transaction velocity', 'description' => 'Too many requests in a short window.', 'action' => 'review', 'severity' => 'medium', 'params' => ['count' => 5, 'window_minutes' => 30]],
            ['code' => 'failed_attempts', 'name' => 'Repeated failed payments', 'description' => 'Several failed payment attempts within 24h.', 'action' => 'review', 'severity' => 'high', 'params' => ['max' => 3]],
            ['code' => 'name_mismatch', 'name' => 'Recipient name mismatch', 'description' => 'Recipient name does not match the account holder.', 'action' => 'review', 'severity' => 'low', 'params' => []],
            ['code' => 'new_device_high_value', 'name' => 'New device, high value', 'description' => 'A large transaction shortly after a login from a device never seen before for this user.', 'action' => 'review', 'severity' => 'medium', 'params' => ['amount' => 200000]],
            ['code' => 'password_reset_then_transaction', 'name' => 'Transaction after password change', 'description' => 'A transaction was made shortly after the account password was changed.', 'action' => 'review', 'severity' => 'high', 'params' => ['window_hours' => 24]],
        ];
        foreach ($rules as $r) {
            RiskRule::updateOrCreate(['code' => $r['code']], $r + ['is_active' => true]);
        }
    }
}
