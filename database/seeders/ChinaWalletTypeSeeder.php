<?php

namespace Database\Seeders;

use App\Models\ChinaWalletType;
use App\Models\PaymentProvider;
use Illuminate\Database\Seeder;

/**
 * Seeds the fixed set of China wallet types (alipay/wechat/other, matching
 * App\Enums\AppType) with real, honest defaults. Numeric limits are left
 * null on purpose where no wallet-specific rule exists yet — FundingService
 * already falls back to the customer's KYC-tier limits (LimitService) when
 * these are null, so an admin only fills them in once a real rule applies.
 */
class ChinaWalletTypeSeeder extends Seeder
{
    public function run(): void
    {
        $alipayLive = PaymentProvider::where('code', 'alipay')->where('is_active', true)->exists();

        $rows = [
            [
                'code' => 'alipay',
                'name' => 'Alipay',
                'description' => 'Fund a recipient\'s Alipay balance directly from their wallet ID, phone number or QR code.',
                'account_identifier_type' => 'custom',
                'qr_required' => false,
                'account_name_required' => true,
                'phone_required' => false,
                'automated_funding' => $alipayLive,
                'manual_funding' => ! $alipayLive,
                'provider_code' => $alipayLive ? 'alipay' : null,
                'processing_time_estimate' => $alipayLive ? 'Usually within minutes' : 'Manually processed, typically within a few hours',
                'customer_instructions' => 'Add the recipient\'s Alipay-linked phone number, wallet ID or a QR code so we can match the account before sending funds.',
                'sort' => 0,
            ],
            [
                'code' => 'wechat',
                'name' => 'WeChat Pay',
                'description' => 'Fund a recipient\'s WeChat Pay balance using their WeChat ID, phone number or QR code.',
                'account_identifier_type' => 'custom',
                'qr_required' => false,
                'account_name_required' => true,
                'phone_required' => false,
                'automated_funding' => false,
                'manual_funding' => true,
                'provider_code' => null,
                'processing_time_estimate' => 'Manually processed, typically within a few hours',
                'customer_instructions' => 'Add the recipient\'s WeChat ID, linked phone number or a QR code so we can match the account before sending funds.',
                'sort' => 1,
            ],
            [
                'code' => 'other',
                'name' => 'Other China wallet',
                'description' => 'Fund another supported China wallet not listed above. Reviewed manually before delivery.',
                'account_identifier_type' => 'custom',
                'qr_required' => false,
                'account_name_required' => true,
                'phone_required' => false,
                'automated_funding' => false,
                'manual_funding' => true,
                'provider_code' => null,
                'processing_time_estimate' => 'Manually processed after review',
                'customer_instructions' => 'Tell us which wallet you are funding and add the recipient\'s account identifier so our team can verify it.',
                'sort' => 2,
            ],
        ];

        foreach ($rows as $row) {
            ChinaWalletType::updateOrCreate(['code' => $row['code']], $row + ['is_active' => true]);
        }
    }
}
