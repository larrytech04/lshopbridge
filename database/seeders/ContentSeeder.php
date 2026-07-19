<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['question' => 'How fast is funding delivered?', 'answer' => 'In most cases instantly. Automated payments confirm within seconds and funding is delivered automatically.', 'category' => 'funding'],
            ['question' => 'Which payment methods can I use?', 'answer' => 'MTN MoMo, Orange Money, bank transfer, card and crypto.', 'category' => 'payments'],
            ['question' => 'Is my data safe?', 'answer' => 'Yes. Documents are encrypted, stored privately and never shared publicly.', 'category' => 'security'],
            ['question' => 'Do I need to upload proof of payment?', 'answer' => 'Not for automated methods, only manual bank transfers need proof.', 'category' => 'payments'],
            ['question' => 'What are the limits?', 'answer' => 'Limits depend on your verification level. Verify your ID to raise them.', 'category' => 'account'],
            ['question' => 'Can I get a refund?', 'answer' => 'If a funding cannot be completed, the amount is refunded to your wallet.', 'category' => 'funding'],
        ];
        foreach ($faqs as $i => $f) {
            Faq::updateOrCreate(['question' => $f['question']], $f + ['is_published' => true, 'sort' => $i]);
        }

        Banner::updateOrCreate(['title' => 'Fund Alipay, WeChat Pay and more'], [
            'subtitle' => 'Top up with MoMo, bank, card or crypto and we deliver to any China wallet automatically, plus shop gift cards, eSIMs, VPN & more, delivered in minutes.',
            'cta_label' => 'Start funding', 'cta_url' => '/register', 'type' => 'hero', 'position' => 'home', 'is_active' => true, 'sort' => 1,
        ]);

        $pages = [
            ['slug' => 'terms', 'title' => 'Terms of Service', 'type' => 'legal', 'excerpt' => 'The terms governing your use of LshopBridge.'],
            ['slug' => 'privacy', 'title' => 'Privacy Policy', 'type' => 'legal', 'excerpt' => 'How we collect, use and protect your data.'],
            ['slug' => 'refund-policy', 'title' => 'Refund Policy', 'type' => 'legal', 'excerpt' => 'When and how refunds are issued.'],
            ['slug' => 'about', 'title' => 'About LshopBridge', 'type' => 'info', 'excerpt' => 'Bridging Africa and China, instant wallet funding plus a digital shop for gift cards, eSIMs, top-ups and more.'],
        ];
        foreach ($pages as $p) {
            Page::updateOrCreate(['slug' => $p['slug']], $p + [
                'body' => "This is placeholder content for the {$p['title']} page. Administrators can edit this from the admin panel under Legal pages.\n\nReplace this with your real, legally-reviewed copy before going live.",
                'is_published' => true, 'last_reviewed_at' => now(),
            ]);
        }
    }
}
