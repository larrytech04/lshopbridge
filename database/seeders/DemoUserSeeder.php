<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\BeneficiaryAccount;
use App\Models\Country;
use App\Models\Deposit;
use App\Models\FundingRequest;
use App\Models\PaymentMethod;
use App\Models\Review;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUserSeeder extends Seeder
{
    public function run(WalletService $wallet): void
    {
        // Never hardcode a known demo password in committed source: generate one per
        // run (or pin it via DEMO_PASSWORD in .env for repeatable local logins) and
        // print it once so it never needs to live in the repo or the README.
        $password = env('DEMO_PASSWORD') ?: Str::password(14);

        $gh = Country::where('iso2', 'GH')->first();
        $cm = Country::where('iso2', 'CM')->first();
        $ng = Country::where('iso2', 'NG')->first();
        $cn = Country::where('iso2', 'CN')->first();

        // ---- Staff ----
        User::updateOrCreate(['email' => 'superadmin@paybridge.test'], [
            'name' => 'Super Admin', 'password' => Hash::make($password), 'role' => 'super_admin',
            'phone' => '+237600000000', 'country_id' => $cm?->id, 'kyc_level' => 3, 'kyc_status' => 'approved',
            'email_verified_at' => now(), 'phone_verified_at' => now(),
        ]);
        User::updateOrCreate(['email' => 'admin@paybridge.test'], [
            'name' => 'Amina Admin', 'password' => Hash::make($password), 'role' => 'admin',
            'phone' => '+237600000001', 'country_id' => $cm?->id, 'kyc_level' => 3, 'kyc_status' => 'approved',
            'email_verified_at' => now(), 'phone_verified_at' => now(),
        ]);

        // ---- Demo user with history ----
        $user = User::updateOrCreate(['email' => 'kofi@example.com'], [
            'name' => 'Kofi Mensah', 'password' => Hash::make($password), 'role' => 'user',
            'phone' => '+233200000000', 'country_id' => $gh?->id, 'city' => 'Accra', 'address' => '12 Independence Ave',
            'kyc_level' => 2, 'kyc_status' => 'approved', 'email_verified_at' => now(), 'phone_verified_at' => now(),
        ]);

        $w = $user->primaryWallet();
        if ((float) $w->balance < 1) {
            $wallet->credit($w, 500000, 'adjustment', null, 'Welcome demo balance');
        }

        $beneficiary = BeneficiaryAccount::updateOrCreate(
            ['user_id' => $user->id, 'account_id' => 'kofi@alipay.cn'],
            ['app_type' => 'alipay', 'account_name' => 'Kofi Mensah', 'status' => 'approved', 'is_default' => true, 'reviewed_at' => now()],
        );

        $mtn = PaymentMethod::where('code', 'mtn_momo')->first();
        Deposit::updateOrCreate(['reference' => 'PB-DEP-DEMO0001'], [
            'user_id' => $user->id, 'payment_method_id' => $mtn?->id, 'provider_code' => 'mtn_momo',
            'amount' => 200000, 'fee' => 0, 'net_amount' => 200000, 'currency' => 'XAF',
            'status' => 'confirmed', 'is_automated' => true, 'provider_reference' => 'MTN_DEMO_REF', 'confirmed_at' => now()->subDays(3),
        ]);
        Deposit::updateOrCreate(['reference' => 'PB-DEP-DEMO0002'], [
            'user_id' => $user->id, 'payment_method_id' => PaymentMethod::where('code', 'bank_transfer')->value('id'),
            'amount' => 150000, 'fee' => 0, 'net_amount' => 150000, 'currency' => 'XAF', 'status' => 'under_review', 'is_automated' => false,
        ]);

        FundingRequest::updateOrCreate(['reference' => 'PB-FND-DEMO0001'], [
            'user_id' => $user->id, 'beneficiary_account_id' => $beneficiary->id, 'app_type' => 'alipay',
            'recipient_name' => 'Kofi Mensah', 'recipient_account' => 'kofi@alipay.cn',
            'source_amount' => 100000, 'source_currency' => 'XAF', 'exchange_rate' => 0.0119, 'target_amount' => 1190,
            'target_currency' => 'CNY', 'fee' => 2500, 'total_charged' => 102500, 'funding_source' => 'wallet',
            'status' => 'funding_successful', 'provider_code' => 'alipay', 'provider_reference' => 'ALI-DEMO-REF', 'processed_at' => now()->subDays(2),
        ]);

        // ---- Verified agent ----
        $agentUser = User::updateOrCreate(['email' => 'agent@example.com'], [
            'name' => 'Li Wei', 'password' => Hash::make($password), 'role' => 'agent',
            'phone' => '+8613800000000', 'country_id' => $cn?->id, 'kyc_level' => 3, 'kyc_status' => 'approved',
            'email_verified_at' => now(), 'phone_verified_at' => now(),
        ]);
        $agentUser->primaryWallet();

        $agent = Agent::updateOrCreate(['user_id' => $agentUser->id], [
            'business_name' => 'Guangzhou Cargo Express', 'slug' => 'guangzhou-cargo-express',
            'bio' => 'Trusted procurement & freight from Guangzhou to West & Central Africa. Air, sea and express options with consolidated shipping.',
            'warehouse_country_id' => $cn?->id, 'warehouse_city' => 'Guangzhou', 'phone' => '+8613800000000', 'whatsapp' => '+8613800000000',
            'shipping_methods' => ['air', 'sea', 'express'], 'status' => 'approved', 'verified_at' => now(),
            'is_featured' => true, 'completed_orders' => 24, 'points' => 240,
        ]);
        $agent->countries()->sync(array_filter([$cm?->id, $ng?->id, $gh?->id]));

        $agent->shippingRates()->updateOrCreate(['method' => 'air', 'destination_country_id' => $cm?->id], ['price_per_kg' => 12, 'currency' => 'USD', 'estimated_days_min' => 7, 'estimated_days_max' => 12, 'is_active' => true]);
        $agent->shippingRates()->updateOrCreate(['method' => 'sea', 'destination_country_id' => $ng?->id], ['price_per_cbm' => 320, 'currency' => 'USD', 'estimated_days_min' => 35, 'estimated_days_max' => 50, 'is_active' => true]);

        Review::updateOrCreate(['agent_id' => $agent->id, 'user_id' => $user->id], ['rating' => 5, 'comment' => 'Fast shipping and great communication!', 'status' => 'approved']);
        $agent->recalculateRating();

        // ---- More verified agents (so the row looks full) ----
        $moreAgents = [
            ['email' => 'agent2@example.com', 'name' => 'Chen Hua', 'business' => 'Shenzhen Global Sourcing', 'city' => 'Shenzhen',
                'bio' => 'Electronics, gadgets & accessories sourced from Shenzhen with quality checks and fast air freight to Africa.',
                'methods' => ['air', 'express'], 'rating' => 4.8, 'reviews' => 12, 'featured' => true, 'orders' => 56, 'kg' => 11],
            ['email' => 'agent3@example.com', 'name' => 'Wang Mei', 'business' => 'Yiwu Trade Bridge', 'city' => 'Yiwu',
                'bio' => 'Wholesale market sourcing from Yiwu — fashion, accessories & home goods with sea and consolidated shipping.',
                'methods' => ['sea', 'express'], 'rating' => 4.7, 'reviews' => 9, 'featured' => false, 'orders' => 38, 'kg' => 13],
        ];
        foreach ($moreAgents as $a) {
            $u = User::updateOrCreate(['email' => $a['email']], [
                'name' => $a['name'], 'password' => Hash::make($password), 'role' => 'agent',
                'country_id' => $cn?->id, 'kyc_level' => 3, 'kyc_status' => 'approved',
                'email_verified_at' => now(), 'phone_verified_at' => now(),
            ]);
            $u->primaryWallet();
            $ag = Agent::updateOrCreate(['user_id' => $u->id], [
                'business_name' => $a['business'], 'bio' => $a['bio'],
                'warehouse_country_id' => $cn?->id, 'warehouse_city' => $a['city'],
                'shipping_methods' => $a['methods'], 'status' => 'approved', 'verified_at' => now(),
                'is_featured' => $a['featured'], 'completed_orders' => $a['orders'], 'points' => $a['orders'] * 10,
                'rating' => $a['rating'], 'reviews_count' => $a['reviews'],
            ]);
            $ag->countries()->sync(array_filter([$cm?->id, $ng?->id, $gh?->id]));
            $ag->shippingRates()->updateOrCreate(['method' => 'air', 'destination_country_id' => $cm?->id], ['price_per_kg' => $a['kg'], 'currency' => 'USD', 'estimated_days_min' => 6, 'estimated_days_max' => 11, 'is_active' => true]);
            $ag->shippingRates()->updateOrCreate(['method' => 'sea', 'destination_country_id' => $ng?->id], ['price_per_cbm' => 300, 'currency' => 'USD', 'estimated_days_min' => 30, 'estimated_days_max' => 45, 'is_active' => true]);
        }

        $this->command?->newLine();
        $this->command?->warn("Demo accounts password: {$password}");
        $this->command?->line('  (superadmin@paybridge.test, admin@paybridge.test, kofi@example.com, agent@example.com)');
        $this->command?->line('  Set DEMO_PASSWORD in .env to pin this across reseeds.');
    }
}
