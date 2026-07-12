<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Faq;
use App\Models\Guide;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $guides = [
            ['title' => 'How to buy from 1688', 'category' => '1688', 'excerpt' => 'A complete beginner guide to sourcing wholesale from 1688.com.', 'is_featured' => true,
             'steps' => [['title' => 'Create an account', 'body' => 'Register on 1688.com with your phone or Alipay.'], ['title' => 'Search in Chinese', 'body' => 'Use image search or translated keywords to find products.'], ['title' => 'Chat with suppliers', 'body' => 'Use Wangwang to negotiate price and MOQ.'], ['title' => 'Pay with Alipay', 'body' => 'Fund your Alipay via LshopBridge, then checkout.']],
             'faqs' => [['q' => 'Do I need a Chinese bank card?', 'a' => 'No — fund your Alipay through LshopBridge and pay directly.'], ['q' => 'What is MOQ?', 'a' => 'Minimum order quantity required by the supplier.']]],
            ['title' => 'How to buy from Taobao', 'category' => 'taobao', 'excerpt' => 'Shop retail items from China the smart way.', 'is_featured' => true,
             'steps' => [['title' => 'Install Taobao', 'body' => 'Download the app and switch language if needed.'], ['title' => 'Find products', 'body' => 'Search or scan images to find what you want.'], ['title' => 'Checkout with Alipay', 'body' => 'Top up Alipay with LshopBridge and pay.']]],
            ['title' => 'How to use Alipay as a foreigner', 'category' => 'alipay', 'excerpt' => 'Set up and fund Alipay without a Chinese bank account.',
             'steps' => [['title' => 'Download Alipay', 'body' => 'Register with your phone number.'], ['title' => 'Verify identity', 'body' => 'Complete basic verification.'], ['title' => 'Fund via LshopBridge', 'body' => 'Send money to your Alipay instantly through LshopBridge.']]],
            ['title' => 'Shipping goods to a warehouse', 'category' => 'shipping', 'excerpt' => 'Consolidate orders and ship to Africa affordably.'],
            ['title' => 'Customs & delivery explained', 'category' => 'customs', 'excerpt' => 'Understand duties, clearance and last-mile delivery.'],
            ['title' => 'Common mistakes to avoid', 'category' => 'mistakes', 'excerpt' => 'Avoid scams, overpaying and shipping delays.'],
        ];
        foreach ($guides as $g) {
            Guide::updateOrCreate(['slug' => Str::slug($g['title'])], array_merge([
                'is_published' => true, 'read_minutes' => rand(3, 8), 'views' => rand(40, 900),
            ], $g));
        }

        $faqs = [
            ['question' => 'How fast is funding delivered?', 'answer' => 'In most cases instantly. Automated payments confirm within seconds and funding is delivered automatically.', 'category' => 'funding'],
            ['question' => 'Which payment methods can I use?', 'answer' => 'MTN MoMo, Orange Money, bank transfer, card and crypto.', 'category' => 'payments'],
            ['question' => 'Is my data safe?', 'answer' => 'Yes. Documents are encrypted, stored privately and never shared publicly.', 'category' => 'security'],
            ['question' => 'Do I need to upload proof of payment?', 'answer' => 'Not for automated methods — only manual bank transfers need proof.', 'category' => 'payments'],
            ['question' => 'What are the limits?', 'answer' => 'Limits depend on your verification level. Verify your ID to raise them.', 'category' => 'account'],
            ['question' => 'Can I get a refund?', 'answer' => 'If a funding cannot be completed, the amount is refunded to your wallet.', 'category' => 'funding'],
        ];
        foreach ($faqs as $i => $f) {
            Faq::updateOrCreate(['question' => $f['question']], $f + ['is_published' => true, 'sort' => $i]);
        }

        Banner::updateOrCreate(['title' => 'Fund Alipay, WeChat Pay and more'], [
            'subtitle' => 'Top up with MoMo, bank, card or crypto and we deliver to any China wallet automatically — plus shop gift cards, eSIMs, VPN & more, delivered in minutes.',
            'cta_label' => 'Start funding', 'cta_url' => '/register', 'type' => 'hero', 'position' => 'home', 'is_active' => true, 'sort' => 1,
        ]);

        $pages = [
            ['slug' => 'terms', 'title' => 'Terms of Service', 'type' => 'legal', 'excerpt' => 'The terms governing your use of LshopBridge.'],
            ['slug' => 'privacy', 'title' => 'Privacy Policy', 'type' => 'legal', 'excerpt' => 'How we collect, use and protect your data.'],
            ['slug' => 'refund-policy', 'title' => 'Refund Policy', 'type' => 'legal', 'excerpt' => 'When and how refunds are issued.'],
            ['slug' => 'about', 'title' => 'About LshopBridge', 'type' => 'info', 'excerpt' => 'Bridging Africa and China — instant wallet funding plus a digital shop for gift cards, eSIMs, top-ups and more.'],
        ];
        foreach ($pages as $p) {
            Page::updateOrCreate(['slug' => $p['slug']], $p + [
                'body' => "This is placeholder content for the {$p['title']} page. Administrators can edit this from the admin panel under Legal pages.\n\nReplace this with your real, legally-reviewed copy before going live.",
                'is_published' => true, 'last_reviewed_at' => now(),
            ]);
        }
    }
}
