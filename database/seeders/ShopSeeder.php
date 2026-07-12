<?php

namespace Database\Seeders;

use App\Models\ShopCategory;
use App\Models\ShopCode;
use App\Models\ShopProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Top-level categories (each gets its own hero + page) -----------
        // [slug, name, icon, accent, tagline]
        $top = [
            ['gift-cards', 'Gift Cards', 'giftcard', 'brand', 'Amazon, Apple, Steam & more'],
            ['mobile-topup', 'Mobile top up & data', 'signal', 'accent', 'Airtime & data for any network'],
            ['esims', 'eSIMs', 'sim', 'emerald', 'Instant data in 190+ countries'],
            ['bill-payments', 'Bill payments', 'receipt', 'violet', 'Pay utilities, TV & internet'],
            ['flights', 'Flights', 'plane', 'rose', 'Book flights worldwide'],
            ['stays', 'Stays', 'bed', 'amber', 'Hotels & stays, paid instantly'],
        ];
        $cat = [];
        foreach ($top as $i => [$slug, $name, $icon, $accent, $tag]) {
            $cat[$slug] = ShopCategory::updateOrCreate(['slug' => $slug], [
                'parent_id' => null, 'name' => $name, 'icon' => $icon, 'accent' => $accent,
                'tagline' => $tag, 'sort' => $i, 'is_active' => true,
            ]);
        }

        // ---- Gift-card subcategories ---------------------------------------
        $subs = [
            'Auto & Moto', 'Clothing & Accessories', 'Dating', 'Digital Apps', 'EGIFT',
            'Electronics', 'Entertainment', 'Food & Drink', 'Games', 'Groceries',
            'Health & Beauty', 'Home & Garden', 'Marketplace', 'Restaurants', 'Retail',
            'Streaming', 'Travel',
        ];
        foreach ($subs as $i => $name) {
            $slug = 'gc-'.Str::slug($name);
            $cat[$slug] = ShopCategory::updateOrCreate(['slug' => $slug], [
                'parent_id' => $cat['gift-cards']->id, 'name' => $name, 'icon' => 'giftcard',
                'accent' => 'brand', 'sort' => $i, 'is_active' => true,
            ]);
        }

        // Hide any legacy categories that are no longer part of the tree.
        ShopCategory::whereNotIn('slug', array_keys($cat))->update(['is_active' => false]);

        // ---- Products: [categorySlug, name, brand, type, region, summary, featured, best, variants[]]
        $products = [
            ['gc-marketplace', 'Amazon Gift Card', 'Amazon', 'giftcard', 'US', 'Shop millions of items on Amazon.', true, true, [
                ['$10', 6500, null], ['$25', 16000, 17000], ['$50', 31500, null], ['$100', 62000, null],
            ]],
            ['gc-digital-apps', 'Apple / iTunes Gift Card', 'Apple', 'giftcard', 'US', 'Apps, games, music, iCloud & more.', true, false, [
                ['$10', 6500, null], ['$25', 16000, null], ['$50', 31500, null],
            ]],
            ['gc-games', 'Steam Wallet Card', 'Steam', 'giftcard', 'Global', 'Add funds to your Steam wallet.', false, false, [
                ['$20', 13000, null], ['$50', 31500, null], ['$100', 62000, null],
            ]],
            ['gc-streaming', 'Netflix Gift Card', 'Netflix', 'giftcard', 'Global', 'Stream movies & series worldwide.', false, true, [
                ['$15', 9800, 10500], ['$30', 19000, null],
            ]],
            ['gc-digital-apps', 'Google Play Card', 'Google', 'giftcard', 'US', 'Apps, games and in-app purchases.', false, false, [
                ['$10', 6500, null], ['$25', 16000, null],
            ]],
            ['gc-retail', 'eBay Gift Card', 'eBay', 'giftcard', 'US', 'Spend on millions of eBay listings.', false, false, [
                ['$25', 16000, null], ['$50', 31500, null],
            ]],
            ['gc-streaming', 'Spotify Premium', 'Spotify', 'streaming', 'Global', 'Ad-free music, offline listening.', true, false, [
                ['1 Month', 3500, null, null, 30], ['3 Months', 9500, 10500, null, 90],
            ]],
            ['gc-streaming', 'YouTube Premium', 'YouTube', 'streaming', 'Global', 'Ad-free videos & background play.', false, false, [
                ['1 Month', 3800, null, null, 30],
            ]],
            ['gc-games', 'PUBG Mobile UC', 'PUBG', 'gaming', 'Global', 'Recharge Unknown Cash instantly.', true, false, [
                ['325 UC', 4500, null], ['660 UC', 9000, null], ['1800 UC', 23000, null],
            ]],
            ['gc-games', 'Free Fire Diamonds', 'Garena', 'gaming', 'Global', 'Top up diamonds for Free Fire.', false, true, [
                ['100 💎', 1200, null], ['520 💎', 6000, null], ['1080 💎', 12000, null],
            ]],
            ['gc-digital-apps', 'LshopBridge Secure VPN', 'LshopBridge VPN', 'vpn', 'Global', 'Fast, private VPN for all devices.', false, true, [
                ['1 Month', 5500, 7000, null, 30], ['12 Months', 39000, 66000, null, 365],
            ]],

            // eSIMs
            ['esims', 'China Travel eSIM', 'LshopBridge eSIM', 'esim', 'China', 'Stay connected across mainland China.', true, true, [
                ['1GB · 7 days', 4500, null, '1GB', 7], ['5GB · 30 days', 14500, 16000, '5GB', 30], ['10GB · 30 days', 24500, null, '10GB', 30],
            ]],
            ['esims', 'Global eSIM (130+ countries)', 'LshopBridge eSIM', 'esim', 'Global', 'One eSIM for worldwide travel.', true, false, [
                ['3GB · 30 days', 16500, null, '3GB', 30], ['10GB · 30 days', 39000, null, '10GB', 30],
            ]],
            ['esims', 'USA eSIM', 'LshopBridge eSIM', 'esim', 'United States', 'High-speed 5G data in the USA.', false, false, [
                ['5GB · 30 days', 17500, null, '5GB', 30], ['Unlimited · 15 days', 33000, null, 'Unlimited', 15],
            ]],

            // Mobile top up & data
            ['mobile-topup', 'MTN Airtime Top-up', 'MTN', 'data', 'Cameroon', 'Instant airtime for any MTN number.', true, false, [
                ['1,000 XAF', 1000, null], ['2,000 XAF', 2000, null], ['5,000 XAF', 5000, null],
            ]],
            ['mobile-topup', 'Orange Data Bundle', 'Orange', 'data', 'Cameroon', 'Data bundles delivered instantly.', false, false, [
                ['1.5GB · 30 days', 1500, null, '1.5GB', 30], ['6GB · 30 days', 5000, null, '6GB', 30],
            ]],

            // Bill payments
            ['bill-payments', 'ENEO Electricity', 'ENEO', 'other', 'Cameroon', 'Buy prepaid electricity tokens.', true, false, [
                ['5,000 XAF', 5000, null], ['10,000 XAF', 10000, null], ['25,000 XAF', 25000, null],
            ]],
            ['bill-payments', 'Canal+ Subscription', 'Canal+', 'other', 'Africa', 'Renew your Canal+ TV plan.', false, false, [
                ['Access · 1 month', 6000, null, null, 30], ['Évasion · 1 month', 16000, null, null, 30],
            ]],

            // Flights
            ['flights', 'Flight Booking Credit', 'LshopBridge Travel', 'other', 'Global', 'Pay for flights with funded credit.', true, false, [
                ['50,000 XAF', 50000, null], ['100,000 XAF', 100000, null], ['250,000 XAF', 250000, null],
            ]],

            // Stays
            ['stays', 'Hotel Stay Voucher', 'LshopBridge Stays', 'other', 'Global', 'Book hotels & stays worldwide.', true, false, [
                ['25,000 XAF', 25000, null], ['50,000 XAF', 50000, null], ['100,000 XAF', 100000, null],
            ]],
        ];

        foreach ($products as [$catSlug, $name, $brand, $type, $region, $summary, $featured, $best, $variants]) {
            $product = ShopProduct::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'shop_category_id' => $cat[$catSlug]->id,
                    'name' => $name, 'brand' => $brand, 'type' => $type, 'region' => $region,
                    'summary' => $summary, 'is_active' => true, 'is_featured' => $featured, 'is_best_deal' => $best,
                    'description' => $summary.' Delivered instantly to your account and email after payment.',
                    'redeem_instructions' => 'Your code/credential is shown on the order page and emailed to you. Redeem it in the official '.$brand.' app or website.',
                    'sales_count' => random_int(20, 1200),
                ],
            );

            foreach ($variants as $sort => $v) {
                $variant = $product->variants()->updateOrCreate(
                    ['name' => $v[0]],
                    [
                        'price' => $v[1],
                        'compare_at_price' => $v[2] ?? null,
                        'currency' => config('platform.base_currency', 'XAF'),
                        'data_amount' => $v[3] ?? null,
                        'validity_days' => $v[4] ?? null,
                        'stock' => null,
                        'is_active' => true,
                        'sort' => $sort,
                    ],
                );

                if ($name === 'Amazon Gift Card' && $v[0] === '$25' && $variant->codes()->count() === 0) {
                    foreach (range(1, 5) as $n) {
                        ShopCode::create(['shop_variant_id' => $variant->id, 'secret' => 'AMZN-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4))]);
                    }
                }
            }
        }
    }
}
