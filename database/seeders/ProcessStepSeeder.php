<?php

namespace Database\Seeders;

use App\Models\ProcessStep;
use Illuminate\Database\Seeder;

/**
 * "How It Works" content (public.how-it-works) is admin-editable (Admin ->
 * Page content -> How It Works) rather than hardcoded in the view, which
 * means a fresh database has none of it until someone fills it in by hand.
 * This seeds the same fund/shop steps and promises already written in this
 * environment's admin panel, so a new environment (or production, if it was
 * never populated there) isn't stuck with an empty journey section.
 */
class ProcessStepSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Journey 1: Funding a China wallet
            ['group' => 'fund_step', 'sort' => 0, 'icon' => 'Recruiting-Employee-Target-Validated-Check-2--Streamline-Ultimate.png', 'title' => 'Create & verify your account', 'body' => 'Sign up free, confirm your email and phone (OTP), then complete KYC by uploading your ID and a selfie. Higher verification tiers unlock higher funding limits.'],
            ['group' => 'fund_step', 'sort' => 1, 'icon' => 'Money-Wallet-1--Streamline-Ultimate.png', 'title' => 'Save a China wallet', 'body' => 'Add your Alipay, WeChat Pay, UnionPay or QQ details once as a saved beneficiary, we store them securely so future funding is one tap.'],
            ['group' => 'fund_step', 'sort' => 2, 'icon' => 'Cash-Exchange-Rate--Streamline-Flex.png', 'title' => 'Choose an amount & see the live rate', 'body' => 'Enter how much CNY you want to send. We instantly show the exact rate and fee (XAF/NGN -> CNY) upfront, what you see is what you pay, no hidden charges.'],
            ['group' => 'fund_step', 'sort' => 3, 'icon' => 'Credit-Card-Payment--Streamline-Ultimate.png', 'title' => 'Pay your way', 'body' => 'Top up your wallet first, or pay directly with MTN MoMo, Orange Money, bank transfer, card or crypto, whatever is easiest for you.'],
            ['group' => 'fund_step', 'sort' => 4, 'icon' => 'Gateway-Security--Streamline-Ultimate.png', 'title' => 'We confirm automatically', 'body' => 'Automated methods are verified in seconds through secure provider webhooks. Only flagged or manual bank transfers go to a quick human review for safety.'],
            ['group' => 'fund_step', 'sort' => 5, 'icon' => 'Shipment-Smartphone-Arrive--Streamline-Ultimate.png', 'title' => 'Delivered to the wallet', 'body' => 'Our funding engine pays the China wallet automatically, usually within minutes. You get notified and can track every step live in your dashboard.'],

            // Journey 2: Shopping gift cards & eSIMs
            ['group' => 'shop_step', 'sort' => 0, 'icon' => 'Shop-Sign-Bag--Streamline-Ultimate.png', 'title' => 'Open the shop', 'body' => 'Browse categories right inside your dashboard: gift cards, eSIMs, mobile top-ups, bill payments, flights & stays.'],
            ['group' => 'shop_step', 'sort' => 1, 'icon' => 'Gift-Rectangle-With-Bow--Streamline-Ultimate.png', 'title' => 'Pick a product & plan', 'body' => 'Choose the brand or region and the exact option, e.g. an Amazon $25 gift card, or a China eSIM with 5GB for 30 days.'],
            ['group' => 'shop_step', 'sort' => 2, 'icon' => 'Products-Shopping-Bags--Streamline-Ultimate.png', 'title' => 'Add to cart or buy now', 'body' => 'Bundle several items into one order, or check out a single product instantly.'],
            ['group' => 'shop_step', 'sort' => 3, 'icon' => 'Credit-Card--Streamline-Ultimate.png', 'title' => 'Pay from wallet or directly', 'body' => 'Use your wallet balance for one-tap checkout, or pay with MoMo, bank, card or crypto. The exact price and any fee are shown before you confirm.'],
            ['group' => 'shop_step', 'sort' => 4, 'icon' => 'Email-Delivered-4--Streamline-Ux.png', 'title' => 'Instant delivery', 'body' => 'Gift card codes & PINs, and eSIM QR codes / activation details, are delivered to your dashboard and email, usually within seconds.'],
            ['group' => 'shop_step', 'sort' => 5, 'icon' => 'Love-Gift-Box-Heart--Streamline-Ultimate.png', 'title' => 'Redeem & enjoy', 'body' => 'Follow the redeem steps on your order. eSIMs install by scanning the QR code, no physical SIM, connected in minutes.'],

            // "Why it just works" promises
            ['group' => 'promise', 'sort' => 0, 'icon' => 'shield', 'title' => 'Bank-grade security', 'body' => 'Encrypted data, KYC tiers and automatic fraud screening on every order.'],
            ['group' => 'promise', 'sort' => 1, 'icon' => 'bolt', 'title' => 'Instant & automated', 'body' => 'Webhook-confirmed payments trigger instant payouts and delivery.'],
            ['group' => 'promise', 'sort' => 2, 'icon' => 'chart', 'title' => 'Transparent pricing', 'body' => 'The exact rate and fee are shown before you confirm, always.'],
            ['group' => 'promise', 'sort' => 3, 'icon' => 'heart', 'title' => 'Human support', 'body' => 'Real people on chat, WhatsApp and email whenever you need help.'],
        ];

        foreach ($rows as $row) {
            ProcessStep::updateOrCreate(
                ['group' => $row['group'], 'sort' => $row['sort']],
                ['icon' => $row['icon'], 'title' => $row['title'], 'body' => $row['body'], 'is_active' => true],
            );
        }
    }
}
