<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Country;
use App\Models\CryptoWallet;
use App\Models\MomoNumber;
use App\Models\PaymentMethod;
use App\Models\PaymentProvider;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            ['code' => 'mtn_momo', 'name' => 'MTN Mobile Money', 'kind' => 'collection', 'supports' => ['momo']],
            ['code' => 'orange_money', 'name' => 'Orange Money', 'kind' => 'collection', 'supports' => ['momo']],
            ['code' => 'flutterwave', 'name' => 'Flutterwave', 'kind' => 'collection', 'supports' => ['card', 'momo']],
            ['code' => 'crypto', 'name' => 'Crypto Gateway', 'kind' => 'collection', 'supports' => ['crypto']],
            ['code' => 'card', 'name' => 'Card / Prepaid', 'kind' => 'collection', 'supports' => ['card']],
            ['code' => 'alipay', 'name' => 'Alipay Funding', 'kind' => 'funding', 'supports' => ['funding']],
        ];
        foreach ($providers as $p) {
            PaymentProvider::updateOrCreate(['code' => $p['code']], $p + ['mode' => 'sandbox', 'is_active' => true]);
        }

        // "countries" is each method's real, publicly documented operator
        // footprint (MTN/Orange Mobile Money coverage per the operators' own
        // published country lists), curated by hand — not a live feed. Card,
        // crypto and bank transfer are genuinely global rails, so they're
        // left null (PaymentMethod::isAvailableInCountry() treats null as
        // "available everywhere") rather than guessing a restriction that
        // doesn't exist.
        $methods = [
            ['code' => 'mtn_momo', 'name' => 'MTN Mobile Money', 'type' => 'momo', 'provider_code' => 'mtn_momo', 'is_automated' => true, 'requires_proof' => false, 'min_amount' => 500, 'sort' => 1, 'description' => 'Instant top-up via MTN MoMo.', 'countries' => ['CM', 'GH', 'UG', 'RW', 'CI', 'BJ', 'CG', 'GW', 'GN', 'LR', 'SS', 'SZ', 'ZM', 'NG']],
            ['code' => 'orange_money', 'name' => 'Orange Money', 'type' => 'momo', 'provider_code' => 'orange_money', 'is_automated' => true, 'requires_proof' => false, 'min_amount' => 500, 'sort' => 2, 'description' => 'Instant top-up via Orange Money.', 'countries' => ['CM', 'SN', 'ML', 'CI', 'GN', 'MG', 'BW', 'BF', 'CD', 'EG', 'JO', 'MA', 'NE', 'SL', 'TN', 'GW', 'CF', 'LR']],
            ['code' => 'flutterwave', 'name' => 'Card', 'type' => 'card', 'provider_code' => 'flutterwave', 'is_automated' => true, 'requires_proof' => false, 'min_amount' => 1000, 'sort' => 3, 'description' => 'Pay with debit/credit card.'],
            ['code' => 'crypto', 'name' => 'Crypto (USDT)', 'type' => 'crypto', 'provider_code' => 'crypto', 'is_automated' => true, 'requires_proof' => false, 'min_amount' => 1000, 'sort' => 4, 'description' => 'Pay with USDT and other crypto.'],
            ['code' => 'bank_transfer', 'name' => 'Bank transfer', 'type' => 'bank', 'provider_code' => null, 'is_automated' => false, 'requires_proof' => true, 'min_amount' => 1000, 'sort' => 5, 'description' => 'Transfer to our bank account and upload proof.', 'instructions' => "Transfer to the bank account shown, using your name as reference, then upload the receipt."],
        ];
        foreach ($methods as $m) {
            PaymentMethod::updateOrCreate(['code' => $m['code']], $m + ['currency' => 'XAF', 'is_active' => true]);
        }

        $cm = Country::where('iso2', 'CM')->first();

        MomoNumber::updateOrCreate(['number' => '+237650000001'], ['provider' => 'mtn', 'account_name' => 'LshopBridge Collections', 'country_id' => $cm?->id, 'instructions' => 'Send to this MTN number then upload proof.', 'is_active' => true]);
        MomoNumber::updateOrCreate(['number' => '+237690000002'], ['provider' => 'orange', 'account_name' => 'LshopBridge Collections', 'country_id' => $cm?->id, 'is_active' => true]);

        CryptoWallet::updateOrCreate(['address' => 'TPbR1xampleUSDTaddr0000000000000000'], ['asset' => 'USDT', 'network' => 'TRC20', 'is_active' => true]);

        BankAccount::updateOrCreate(['account_number' => '0123456789'], ['bank_name' => 'Afriland First Bank', 'account_name' => 'LshopBridge Ltd', 'swift' => 'CCEICMCX', 'country_id' => $cm?->id, 'is_active' => true]);
    }
}
