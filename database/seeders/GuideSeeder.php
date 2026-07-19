<?php

namespace Database\Seeders;

use App\Models\Guide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The China Buying Academy, a full, plain-language course on how to shop
 * every major China platform and get goods home to Africa. Written to be
 * understood by a first-time buyer with zero background: short sentences,
 * everyday words, one idea per step.
 */
class GuideSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->guides() as $sort => $g) {
            Guide::updateOrCreate(['slug' => Str::slug($g['title'])], array_merge([
                'is_published' => true,
                'sort' => $sort,
                'views' => rand(80, 1400),
                'read_minutes' => max(3, (int) ceil(str_word_count(json_encode($g)) / 220)),
            ], $g));
        }
    }

    private function guides(): array
    {
        return [

            // ───────────────────────────────────────── Getting started ──
            [
                'title' => 'Which China shopping site should I use?',
                'category' => 'orientation',
                'is_featured' => true,
                'excerpt' => "China has more than one 'Amazon.' Answer three quick questions and we'll point you to the right site.",
                'body' => "If you are brand new to shopping from China, the hardest part isn't the buying, it's picking the right website. There isn't one single site that does everything, the way there might be in your country. Instead, different sites are good at different things. This guide is your map. Read it once, and you'll always know where to start.",
                'steps' => [
                    [
                        'title' => 'First, ask: how many do I want?',
                        'body' => "If you want just one item, one phone case, one dress, one toy, you want a retail site. The best ones for that are Taobao, Tmall, JD.com and Pinduoduo. If you want many of the same item, to sell or share with others, you want a wholesale site, where sellers expect to sell in bulk. The best one for that is 1688.com.",
                        'tip' => "Not sure yet? Start with Taobao. It sells almost everything, one item at a time, and is the easiest place to practise.",
                    ],
                    [
                        'title' => 'Second, ask: do I care about the brand name?',
                        'body' => "If you want a real, guaranteed-authentic product from a big brand, Nike, Apple, a well-known skincare brand, go to Tmall. Every shop on Tmall is an official brand store, checked by the platform. If you don't mind unbranded or small-business products, and you mainly care about a good price, Taobao and Pinduoduo will usually be cheaper.",
                    ],
                    [
                        'title' => 'Third, ask: what kind of product is it?',
                        'body' => "Buying a phone, laptop, TV or other electronics? JD.com is the safest choice, it owns its own warehouses and is known for careful packing and honest specifications. Looking for the newest fashion trends, make-up or lifestyle products, and want to see real people's opinions first? Try Xiaohongshu (also called 'RED') to research, then buy on Taobao or Tmall.",
                    ],
                    [
                        'title' => 'Fourth, ask: am I buying from a specific small shop?',
                        'body' => "Sometimes a friend, an influencer, or your shipping agent sends you a link to a small individual shop instead of a big platform. Many of these run on Weidian, a shopping tool built inside WeChat. It works well, but because sellers are individuals rather than big companies, you should take a little more care, we explain exactly how in the Weidian guide.",
                    ],
                    [
                        'title' => "Prefer everything already in English, with no agent needed?",
                        'body' => "AliExpress and DHgate are both built for buyers outside China. Product pages, customer service and checkout are already in English, and packages can often be shipped straight to your home address, without needing a warehouse or an agent in between. Prices are usually a little higher than on the Chinese-language sites, because of this convenience.",
                    ],
                    [
                        'title' => 'Now remember: every site needs a way to pay',
                        'body' => "Taobao, Tmall, 1688, JD.com, Pinduoduo and Xiaohongshu are all paid for with Alipay. Weidian is usually paid for with WeChat Pay. AliExpress and DHgate accept ordinary bank cards, so you don't need either wallet for those two. LshopBridge can fund your Alipay or WeChat Pay balance in minutes, from your Mobile Money, bank card or crypto, see the Alipay and WeChat Pay guides next.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Can I just use one site for everything?', 'a' => "You can, but you'll pay more or find less choice. Taobao alone covers most everyday needs well, start there, and branch out once you're comfortable."],
                    ['q' => 'Which site is safest for a complete beginner?', 'a' => 'Tmall and JD.com have the strongest buyer protections because every seller is an approved, registered business. Start there if safety matters more to you than price.'],
                    ['q' => 'Do I need to speak or read Chinese?', 'a' => "No. This academy shows you exactly which buttons to tap, and most apps offer a translate option. You will pick up a handful of useful Chinese words along the way, we've collected them in the Glossary guide."],
                ],
            ],

            // ─────────────────────────────────────────────────── 1688 ──
            [
                'title' => 'How to buy from 1688 (wholesale sourcing)',
                'category' => '1688',
                'is_featured' => true,
                'excerpt' => 'The wholesale site real shops and resellers use to buy in bulk, straight from the factory.',
                'body' => "1688.com is owned by the same company as Taobao, but it has a different job: it connects you directly to factories and wholesale suppliers, at wholesale prices. It's the site to use when you want to buy many of the same item, to stock a shop, start a small business, or share an order with friends. It looks a little more serious than Taobao, but it is not difficult once you know the steps below.",
                'steps' => [
                    [
                        'title' => 'Understand who 1688 is for',
                        'body' => "1688 sellers expect to sell in bulk. Many will have a 'minimum order quantity,' or MOQ, the smallest amount they are willing to sell at once, for example 20 pieces or 50 pieces. If you only want one item, use Taobao instead. If you want many, 1688 will usually beat every other site on price.",
                    ],
                    [
                        'title' => 'Create your account',
                        'body' => "Open the 1688 app or website and register with your phone number. You'll receive a verification code by SMS, type it in to confirm it's really you. You can browse and search without an account, but you need one to message sellers and check out.",
                    ],
                    [
                        'title' => 'Search like a pro, even without Chinese',
                        'body' => "You don't need to type Chinese words. Use the camera/image-search button inside the app: take or upload a photo of the product you want, and 1688 will show you matching listings automatically. This one trick alone solves most of the language problem.",
                        'tip' => 'A good screenshot from Google, Pinterest or another shopping site works just as well as your own photo.',
                    ],
                    [
                        'title' => 'Learn to read a listing',
                        'body' => "Each listing shows a price, but look closely, it's often a price *per piece*, and it may change depending on how many you order (buy more, pay less per piece). Scroll down to see the MOQ, the available colours/sizes, and, importantly, the seller's rating and how many years they've been trading. A seller with a high rating and years of history is far safer than a brand-new shop with no reviews.",
                    ],
                    [
                        'title' => 'Talk to the supplier before you pay',
                        'body' => "Tap the chat button (often shown as a small speech-bubble icon called Wangwang) to message the seller directly. Ask your real questions: 'Can you ship 15 pieces, even though the MOQ says 20?', 'Do you have this in red?', 'Can I get a sample first?'. Most sellers reply within a day, and many will bend their own rules a little if you simply ask politely.",
                    ],
                    [
                        'title' => 'Compare two or three suppliers before committing',
                        'body' => "Never buy from the very first listing you find. Open two or three similar shops, compare their prices, their MOQ, and, most of all, their reviews and ratings. Five extra minutes of comparing can save you real money and a lot of disappointment.",
                    ],
                    [
                        'title' => 'Pay safely with Alipay',
                        'body' => "1688 checkout is done through Alipay, the same wallet used across nearly every Chinese shopping site. Fund your Alipay through LshopBridge, choose your currency, pick Mobile Money, bank, card or crypto, and the money lands in Alipay automatically, usually within minutes. Then simply pay for your order as normal.",
                    ],
                    [
                        'title' => 'Know what happens after you pay',
                        'body' => "Your order ships to a warehouse in China, not directly to your country. That warehouse is provided by your shipping agent (see the Shipping guide). The agent holds your goods, can combine them with other orders you make, and then ships everything to you together, which is far cheaper than shipping many small parcels separately.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'What exactly is an MOQ?', 'a' => "Minimum Order Quantity, the fewest pieces a seller will sell in one order. It might be 1, 10, 50 or more, and it's always shown on the product page."],
                    ['q' => 'Can I order just one sample first?', 'a' => "Often yes, at a higher per-piece price. Many buyers order one sample to check quality before placing a full bulk order, it's a smart habit, not a shortcut."],
                    ['q' => 'Do I need a Chinese bank account?', 'a' => 'No. Fund your Alipay balance through LshopBridge from your own country, then pay on 1688 exactly as a local buyer would.'],
                    ['q' => 'Is 1688 safe for a first-time buyer?', 'a' => "Yes, as long as you check seller ratings and history first, and start with a smaller trial order before a big one."],
                ],
            ],

            // ────────────────────────────────────────────────── Taobao ──
            [
                'title' => 'How to buy from Taobao (everyday shopping)',
                'category' => 'taobao',
                'is_featured' => true,
                'excerpt' => "China's biggest 'everything store', clothes, gadgets, home goods and more, one item at a time.",
                'body' => "Taobao is the site most people picture when they think of shopping in China. It's enormous, almost anything you can imagine is for sale somewhere on it, from a single shop owner selling handmade jewellery to huge stores with thousands of five-star reviews. This guide walks you through your very first purchase, step by step.",
                'steps' => [
                    [
                        'title' => 'Install the app and set your language',
                        'body' => "Download the Taobao app (or use the website) and create an account with your phone number. Inside Settings, you can often switch the interface to English, it won't translate every single product title, but the menus and buttons become much easier to use.",
                    ],
                    [
                        'title' => 'Search for what you want',
                        'body' => "Type an English word into the search bar, many searches still return good results, since sellers often add English keywords too. For anything that doesn't work, use the camera icon to search by photo instead: it finds visually similar products even with zero typing.",
                    ],
                    [
                        'title' => 'Read the product page properly',
                        'body' => "Scroll past the main photos to see: the size or variant chart (tap it, Taobao sizes often differ from what you're used to), the shop's rating, and, most valuable of all, the customer photo reviews near the bottom. Real buyer photos tell you far more than the seller's own pictures.",
                        'tip' => "A shop rating shown as a little crown, diamond or number of hearts tells you how established the seller is, more icons generally means more trading history.",
                    ],
                    [
                        'title' => 'Pick your options and add to cart',
                        'body' => "Choose your colour, size or style from the small buttons above the 'Add to Cart' button. Double-check your choice before continuing, this is the single most common mistake new buyers make, ordering the wrong size or colour by accident.",
                    ],
                    [
                        'title' => 'Enter your shipping address',
                        'body' => "This will be the warehouse address given to you by your shipping agent (see the Shipping guide), not your home address in Africa, since most Taobao sellers only ship inside China. Save this address once, and it will be ready for every future order.",
                    ],
                    [
                        'title' => 'Checkout and pay with Alipay',
                        'body' => "Confirm your order and pay through Alipay. If your Alipay balance is low, top it up through LshopBridge first, it usually only takes a couple of minutes for the funds to arrive.",
                    ],
                    [
                        'title' => 'Track your order',
                        'body' => "Open 'My Orders' inside the app to see live tracking, from the seller's shipping through to arrival at your agent's warehouse. Message your agent once it arrives so they know to expect it.",
                    ],
                    [
                        'title' => 'If something goes wrong',
                        'body' => "Wrong item, damaged item, or it simply never arrives? Open the order and tap 'Refund' or 'Return.' Taobao's after-sales system is built for exactly this, and most honest sellers resolve issues within a few days without any argument.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Is Taobao only in Chinese?', 'a' => 'The app can switch to English menus, though many individual product titles stay in Chinese, photo search and reviews-with-photos help you shop confidently anyway.'],
                    ['q' => 'How long does a Taobao seller take to ship?', 'a' => "Usually 1–3 days to prepare and hand over to a courier, then a few more days to reach your agent's warehouse inside China."],
                    ['q' => 'What if I ordered the wrong size?', 'a' => "Contact the seller through chat as soon as possible, before the order ships, most will happily fix it if you're quick."],
                ],
            ],

            // ─────────────────────────────────────────────────── Tmall ──
            [
                'title' => 'How to buy from Tmall (official brand stores)',
                'category' => 'tmall',
                'excerpt' => 'The same easy checkout as Taobao, but every shop is an officially verified brand.',
                'body' => "Tmall looks and feels a lot like Taobao, and in fact you can move between the two apps freely. The one big difference is trust: every single shop on Tmall is an official, verified brand store, checked and approved by the platform before it's allowed to open. If authenticity matters more to you than getting the rock-bottom price, Tmall is where you shop.",
                'steps' => [
                    [
                        'title' => 'Know what makes Tmall different',
                        'body' => "Taobao lets anyone open a small shop; Tmall only accepts registered companies and official brands. That's why you'll see real, recognisable names there, Nike, Apple, L'Oréal, and thousands of respected Chinese brands too, each running its own official flagship store.",
                    ],
                    [
                        'title' => "Look for the little red 'Tmall' mark",
                        'body' => "When you search on Taobao, results from Tmall stores are marked with a small red Tmall logo. This is your at-a-glance signal that a listing comes from a verified brand store rather than an independent seller.",
                    ],
                    [
                        'title' => "Find a brand's official flagship store",
                        'body' => "Search the brand name followed by '旗舰店' (flagship store), or simply search the brand name in English and look for the store with the Tmall mark and the largest follower count. That's almost always the real, official one.",
                    ],
                    [
                        'title' => 'Compare before buying, even here',
                        'body' => "Official doesn't always mean cheapest. It's still worth comparing the same product across two or three flagship stores, and checking whether it's currently on promotion, Tmall runs frequent sales, especially around big shopping dates like 11.11 (November 11th).",
                    ],
                    [
                        'title' => 'Checkout exactly like Taobao',
                        'body' => "Add to cart, choose your variant, enter your agent's warehouse address, and pay with Alipay, the whole process is identical to Taobao, because they share the same underlying app and payment system.",
                    ],
                    [
                        'title' => 'Enjoy stronger buyer protection',
                        'body' => "Tmall guarantees include genuine products, and often faster refunds and better warranty support than an average small Taobao shop, because the seller is a real, accountable company rather than an individual.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Is Tmall a separate app from Taobao?', 'a' => 'There is a dedicated Tmall app, but Tmall listings also appear directly inside the Taobao app and share the same account, cart and payment.'],
                    ['q' => 'Are Tmall prices always higher?', 'a' => "Slightly, on average, you're paying a little extra for the guarantee of an official, accountable seller. Sales events can bring prices below even Taobao's."],
                ],
            ],

            // ───────────────────────────────────────────── Pinduoduo ──
            [
                'title' => 'How to buy from Pinduoduo (group deals)',
                'category' => 'pinduoduo',
                'excerpt' => 'Unbeatable prices, powered by many buyers ordering the same item together.',
                'body' => "Pinduoduo built its name on one clever idea: prices drop when more people buy together. It has grown into one of China's largest shopping apps, known for extremely low prices, but, as with any bargain, it pays to shop a little more carefully here than elsewhere. This guide shows you how to get the good deals without the disappointments.",
                'steps' => [
                    [
                        'title' => "Understand 'group buying'",
                        'body' => "On many listings you'll see two prices: a normal price, and a lower 'group price.' To unlock the group price, you either join a group that a friend has started, or start your own group and share the link, Pinduoduo will often match you with strangers automatically within minutes, so you rarely need to wait for real friends.",
                    ],
                    [
                        'title' => 'Create your account',
                        'body' => "Register with your phone number, the same as any other app. Pinduoduo can be used entirely solo, the automatic group-matching means you don't need to invite anyone yourself if you don't want to.",
                    ],
                    [
                        'title' => 'Search and compare carefully',
                        'body' => "Because prices are so low, it's extra important to read reviews and look at the buyer photos before ordering. Sort results by 'sales' or 'reviews' rather than only by price, so you see the listings that real buyers have actually been happy with.",
                        'tip' => "If a price looks too good to be true even for Pinduoduo, treat it as a signal to check reviews twice as carefully.",
                    ],
                    [
                        'title' => 'Order a small test quantity first',
                        'body' => "For anything you haven't bought before, order one or two rather than a large batch. This protects you if the item turns out smaller, thinner or different from what the photos suggested, a common surprise at this price range.",
                    ],
                    [
                        'title' => 'Pay with Alipay and set your warehouse address',
                        'body' => "Checkout works the same way as Taobao: pick your variant, enter your agent's China warehouse address, and pay via Alipay, funded through LshopBridge if needed.",
                    ],
                    [
                        'title' => 'Use buyer protection if needed',
                        'body' => "Pinduoduo has a genuinely strong refund system, if an item never arrives or is clearly not as described, open a dispute inside the order page. Keep your chat messages and photos as evidence; they make any claim much faster to resolve.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Do I really need to invite friends to get the low price?', 'a' => "Not usually, the app will automatically group you with other shoppers wanting the same item, often within seconds."],
                    ['q' => 'Is the quality always lower on Pinduoduo?', 'a' => "Not always, but it varies more than on Tmall or JD. Reading reviews carefully before you buy is the best protection."],
                ],
            ],

            // ────────────────────────────────────────────────── JD.com ──
            [
                'title' => 'How to buy from JD.com (electronics & fast delivery)',
                'category' => 'jd',
                'excerpt' => "China's most trusted site for phones, laptops and appliances, with its own logistics network.",
                'body' => "JD.com (sometimes written 京东, pronounced 'Jingdong') built its reputation the hard way: by owning its own warehouses and delivery network instead of relying only on outside couriers. That means tighter quality control, more careful packing, and dependable delivery times, which is exactly why so many buyers choose JD specifically for anything expensive or electronic.",
                'steps' => [
                    [
                        'title' => 'Know what JD does best',
                        'body' => "JD is the safest general choice for phones, laptops, cameras, TVs and home appliances, because many of these are sold and shipped by JD itself, not by an outside third party. Look for the label 'JD self-operated' (自营) on a listing, that's JD's own stock, backed by JD's own guarantee.",
                    ],
                    [
                        'title' => 'Create your account',
                        'body' => "Sign up with your phone number through the JD app or website. You can browse without an account, but you'll need one to add items to your cart and check out.",
                    ],
                    [
                        'title' => 'Search and filter smartly',
                        'body' => "Use the filter options to narrow results by brand, price range, and, importantly, by 'self-operated' stores if that filter is available. This alone removes most of the risk of buying from an unreliable third-party seller.",
                    ],
                    [
                        'title' => 'Read the full specification list',
                        'body' => "For electronics, scroll all the way down to the detailed spec table before buying, storage size, RAM, screen size, battery capacity, and region/plug type. Two listings that look identical in the photo can be genuinely different products underneath.",
                        'tip' => "Check whether a phone is a 'global version' or 'China version', the China version may not support every 4G/5G band used in your country.",
                    ],
                    [
                        'title' => 'Add to cart and check out',
                        'body' => "Choose your exact variant, enter your agent's China warehouse address, and pay through Alipay. JD also supports its own JD Wallet in China, but Alipay remains the simplest route for international buyers using LshopBridge.",
                    ],
                    [
                        'title' => 'Enjoy JD-speed delivery',
                        'body' => "Because JD controls its own warehouses, self-operated orders often reach your agent's China warehouse faster than orders from small independent shops on other platforms, sometimes within a day or two.",
                    ],
                    [
                        'title' => 'Warranty and after-sales',
                        'body' => "Self-operated JD electronics usually come with a proper manufacturer or JD warranty. Keep your order number and any warranty card your agent forwards to you, you may need them if you ever have to make a claim.",
                    ],
                ],
                'faqs' => [
                    ['q' => "What does 'self-operated' actually mean?", 'a' => "It means JD itself bought the stock and stores it in its own warehouse, rather than a third-party shop simply listing on the JD platform. It's the closest thing to a guarantee that you're getting the genuine, correct product."],
                    ['q' => 'Is JD good for anything besides electronics?', 'a' => 'Yes, groceries, home goods and more, but electronics and appliances are where it truly stands out.'],
                ],
            ],

            // ───────────────────────────────────────────── Xiaohongshu ──
            [
                'title' => 'How to use Xiaohongshu / RED (social shopping)',
                'category' => 'xiaohongshu',
                'excerpt' => 'Part social network, part shopping guide, see real reviews before you spend a single yuan.',
                'body' => "Xiaohongshu, its name means 'Little Red Book,' and it's often just called RED, isn't a shopping site in the traditional sense. It's closer to a mix of Instagram and a giant, honest review site. Millions of everyday users post 'notes': short posts with real photos and honest opinions about products they've actually bought. Smart shoppers use it to research before buying elsewhere.",
                'steps' => [
                    [
                        'title' => "Understand what a 'note' is",
                        'body' => "A note is simply a short post, usually a few photos or a short video, plus a written opinion, shared by an ordinary user, not a company. Because these are real people sharing real experiences, notes tend to be far more honest than a shop's own product photos.",
                    ],
                    [
                        'title' => 'Set up the app',
                        'body' => "Download Xiaohongshu and register with your phone number, exactly like any other app. You can browse a large amount of content immediately after signing up.",
                    ],
                    [
                        'title' => 'Search before you shop, not after',
                        'body' => "Before buying a product on Taobao or Tmall, search its name on Xiaohongshu first. You'll usually find several honest notes from people who already own it, what they loved, what disappointed them, and how it compares to similar options.",
                        'tip' => "Search the general product type too (e.g. 'budget wireless earbuds'), not just one exact model, you'll discover options you didn't know existed.",
                    ],
                    [
                        'title' => 'Learn to spot a paid promotion',
                        'body' => "Not every glowing note is 100% independent, some creators are paid to feature a product. Posts marked with a small 'sponsored' or 'cooperation' (合作) label are paid content. Genuine notes usually mention at least one downside; treat an entirely perfect review with a little healthy doubt.",
                    ],
                    [
                        'title' => 'Follow the link to actually buy',
                        'body' => "Some notes let you tap through and buy directly inside Xiaohongshu's own small shop feature. More often, you'll take what you learned, the exact product name, or a screenshot, and search for it on Taobao or Tmall to complete your purchase using Alipay, as usual.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Can I buy directly on Xiaohongshu?', 'a' => 'Some sellers do offer in-app checkout, but most buyers use it purely for research, then purchase on Taobao or Tmall.'],
                    ['q' => 'Is Xiaohongshu only for beauty and fashion?', 'a' => "That's where it started, but today you'll find honest reviews for electronics, home goods, food, and almost anything else people buy."],
                ],
            ],

            // ─────────────────────────────────────────────────  Weidian ──
            [
                'title' => 'How to buy from Weidian (small shops on WeChat)',
                'category' => 'weidian',
                'excerpt' => 'Small, independent shops shared by link, flexible, but you need to shop with extra care.',
                'body' => "Weidian is a tool that lets anyone open a small shop inside WeChat, China's biggest messaging app. There's no huge central marketplace to browse, instead, you usually arrive at a specific shop because someone sent you a link: a friend, an influencer, or your own shipping agent. Weidian shops can be wonderful for unique, small-batch or hard-to-find items, but because sellers are individuals rather than big companies, this is the one platform in this academy where extra caution really matters.",
                'steps' => [
                    [
                        'title' => 'Get a shop link',
                        'body' => "You don't 'search' Weidian the way you search Taobao. You need a direct link or QR code, usually shared by the seller, a friend, or an agent who already knows and trusts that shop.",
                    ],
                    [
                        'title' => 'Open it inside WeChat',
                        'body' => "Weidian shops open inside WeChat itself, or in the separate Weidian app. If you don't already have WeChat installed, download it and create an account first, you'll need it either way, since WeChat Pay is how most Weidian sellers get paid.",
                    ],
                    [
                        'title' => 'Check the seller before you chat',
                        'body' => "Look for a shop rating, how long the shop has existed, and any reviews or shared customer photos. A shop with a long history and visible reviews is far safer than a brand-new one with none at all.",
                    ],
                    [
                        'title' => 'Chat to confirm details',
                        'body' => "Message the seller directly to confirm price, size/colour, and, importantly, that the item is actually in stock right now. Weidian listings are sometimes less carefully maintained than big-platform ones, so a quick message avoids disappointment.",
                    ],
                    [
                        'title' => 'Pay only inside the platform',
                        'body' => "Pay through WeChat Pay inside the official checkout flow, never by simply transferring money to a personal account outside of any order or receipt. Keeping the payment inside the platform means there's a record of your order if anything goes wrong.",
                        'tip' => "If a seller asks you to pay 'directly' outside of any order system to 'save on fees,' treat that as a warning sign and be extra cautious.",
                    ],
                    [
                        'title' => 'Let your agent help with the address',
                        'body' => "Because Weidian sellers are small and varied, it's especially useful to buy through, or at least ship via, a shipping agent who already knows the platform, they can help confirm a shop is trustworthy and handle the China-side delivery to your warehouse.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Is Weidian safe?', 'a' => 'It can be, but it has fewer built-in buyer protections than Taobao or Tmall because sellers are individuals. Stick to shops recommended by people you trust, and always pay through the official checkout.'],
                    ['q' => 'Why would I use Weidian instead of Taobao?', 'a' => 'Some unique, small-batch, or trend-specific items are only ever sold this way, shared shop-to-shop by word of mouth rather than listed on a big open marketplace.'],
                ],
            ],

            // ───────────────────────────────────────────── AliExpress ──
            [
                'title' => 'How to buy from AliExpress (easiest for beginners)',
                'category' => 'aliexpress',
                'excerpt' => 'Already in English, ships straight to your door, no warehouse or agent required.',
                'body' => "AliExpress is the international, English-language cousin of Taobao, built specifically for buyers outside China. If everything about warehouses, agents and Alipay in the other guides feels like a lot to take in on day one, AliExpress is the gentlest place to start, even though prices are usually a little higher in exchange for that convenience.",
                'steps' => [
                    [
                        'title' => 'Why AliExpress feels different',
                        'body' => "Unlike Taobao or 1688, AliExpress sellers already expect international buyers. Product pages are in English, customer service can usually help in English, and, the biggest difference, most sellers will ship parcels straight to your home address, with no warehouse or agent step needed at all.",
                    ],
                    [
                        'title' => 'Create your account',
                        'body' => "Sign up with your email or phone number directly on the AliExpress app or website, the whole process is in English from the very first screen.",
                    ],
                    [
                        'title' => 'Search and shop as you would anywhere',
                        'body' => "Search in English, filter by price, star rating, and 'orders' (how many times it's been bought, a strong trust signal). Read the reviews, especially any with photos from real buyers.",
                    ],
                    [
                        'title' => 'Choose your shipping method carefully',
                        'body' => "At checkout you'll usually see several shipping options at different prices and speeds. Cheaper options can take several weeks; faster tracked options cost more but arrive sooner and are easier to follow. Always pick a tracked option if it's offered.",
                    ],
                    [
                        'title' => 'Pay by card',
                        'body' => "AliExpress checkout accepts ordinary debit and credit cards directly, you do not need Alipay or WeChat Pay for this platform, which is part of what makes it beginner-friendly.",
                    ],
                    [
                        'title' => 'Track it home',
                        'body' => "Follow your tracking number inside the app all the way to your door. If it's late, damaged, or not as described, AliExpress has built-in buyer protection and dispute tools, open the order and choose 'Open Dispute' to start a claim.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Is AliExpress more expensive than Taobao?', 'a' => "Usually a little, yes, you're paying for English support, buyer protection, and shipping direct to your door without needing an agent."],
                    ['q' => 'How long does shipping usually take?', 'a' => 'From a couple of weeks with standard shipping to just a few days with faster tracked options, depending on what you choose at checkout.'],
                ],
            ],

            // ─────────────────────────────────────────────────  DHgate ──
            [
                'title' => 'How to buy from DHgate (wholesale in English)',
                'category' => 'dhgate',
                'excerpt' => 'Like AliExpress, but built for buying in bulk, great for small business owners.',
                'body' => "DHgate sits in an interesting middle ground: like AliExpress, it's in English and built for international buyers with cards, not Alipay. But like 1688, most sellers expect wholesale-style orders. It's a strong choice if you're starting a small business and want bulk pricing, without wrestling with a Chinese-language site.",
                'steps' => [
                    [
                        'title' => 'Know what DHgate is good for',
                        'body' => "DHgate works best when you want to buy a moderate-to-large quantity of one product and have it shipped internationally, in English, without needing an agent or warehouse in China.",
                    ],
                    [
                        'title' => 'Create your account',
                        'body' => "Register with your email directly on the DHgate app or website, the whole experience, from search to support, is in English.",
                    ],
                    [
                        'title' => 'Compare sellers, not just prices',
                        'body' => "DHgate has thousands of independent sellers of very mixed quality. Always check a seller's rating, years active, and response rate before ordering, these matter here more than almost anywhere else in this guide.",
                        'tip' => "A seller with thousands of completed orders and a rating above 95% is a far safer bet than a new shop with a slightly lower price.",
                    ],
                    [
                        'title' => 'Order a sample before a bulk order',
                        'body' => "Just like on 1688, it's smart to order one or two units first to check real quality, before committing to a large wholesale order from a seller you haven't bought from before.",
                    ],
                    [
                        'title' => 'Pay by card and choose your shipping',
                        'body' => "Checkout with an ordinary debit or credit card. Choose a tracked shipping option where possible, it costs a little more but lets you follow your order all the way to your door.",
                    ],
                    [
                        'title' => 'Use buyer protection if there is a problem',
                        'body' => "DHgate holds your payment until you confirm the order arrived correctly. If it doesn't match the listing, open a dispute from your order page before confirming receipt, once confirmed, it's harder to claim a refund.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Is DHgate the same company as AliExpress?', 'a' => 'No, they are separate, competing platforms, though they serve a similar international, English-speaking audience.'],
                    ['q' => 'Can I order just one item?', 'a' => 'Often yes, though prices are usually much better once you order in the quantities sellers expect.'],
                ],
            ],

            // ──────────────────────────────────────────────── Alipay ──
            [
                'title' => 'How to set up and fund Alipay as a foreigner',
                'category' => 'alipay',
                'is_featured' => true,
                'excerpt' => 'The one wallet almost every China shopping site needs, set up and funded in minutes.',
                'body' => "Alipay is the payment app behind Taobao, Tmall, 1688, JD.com, Pinduoduo and more, think of it as the key that unlocks checkout on almost every site in this academy. You do not need a Chinese bank account to use it. This guide gets you from zero to a funded, ready-to-spend Alipay account.",
                'steps' => [
                    [
                        'title' => 'What Alipay actually is',
                        'body' => "Alipay is a digital wallet, similar in spirit to Mobile Money, but built specifically for China's shopping and services ecosystem. Your balance sits inside the app, ready to pay instantly at checkout on any site that accepts it, which is almost every site in this guide except AliExpress and DHgate.",
                    ],
                    [
                        'title' => 'Download and register',
                        'body' => "Install the Alipay app and register with your phone number. You'll receive a verification code by SMS to confirm the number is really yours.",
                    ],
                    [
                        'title' => 'Complete basic identity verification',
                        'body' => "Alipay will ask for some basic identity information to open your wallet safely, this is a standard, one-time step, similar to verifying your identity for any banking or payment app, and it usually only takes a few minutes.",
                    ],
                    [
                        'title' => 'Fund your Alipay through LshopBridge',
                        'body' => "This is where LshopBridge comes in: open your LshopBridge dashboard, choose 'Fund China wallet,' select Alipay, and enter the amount. Pay with Mobile Money, bank transfer, card or crypto, LshopBridge shows you the exact rate and fee upfront, then delivers the funds straight into your Alipay balance automatically, usually within minutes.",
                        'tip' => 'Make sure the Alipay account name/ID you enter is exact, funds are matched to your account automatically, so a small typo can delay delivery.',
                    ],
                    [
                        'title' => 'Pay at checkout on any supported site',
                        'body' => "Once funded, checking out on Taobao, Tmall, 1688, JD.com or Pinduoduo simply means selecting Alipay as your payment method and confirming, your balance covers it instantly, with no separate login or transfer step needed.",
                    ],
                    [
                        'title' => 'Keep your account safe',
                        'body' => "Never share your Alipay password or verification codes with anyone, including a seller or 'agent' who asks for them directly, a real agent never needs your login, only your order details and warehouse address.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Do I need a Chinese ID or bank account?', 'a' => 'No. Foreign users can open and use Alipay with just a phone number and basic identity verification, and fund it entirely through services like LshopBridge.'],
                    ['q' => 'How fast does LshopBridge funding arrive?', 'a' => 'Most automated payment methods confirm and deliver within minutes. Manual bank transfers may take a little longer while they are verified.'],
                    ['q' => 'What if I fund the wrong amount?', 'a' => "Contact support from your dashboard immediately, unused balance simply stays in your Alipay wallet ready for your next order, it isn't lost."],
                ],
            ],

            // ────────────────────────────────────────────  WeChat Pay ──
            [
                'title' => 'How to set up and fund WeChat Pay',
                'category' => 'wechatpay',
                'excerpt' => "China's other essential wallet, needed for WeChat-based shops like Weidian, and handy everywhere.",
                'body' => "If Alipay is the key to most big shopping sites, WeChat Pay is the key to everything that happens inside WeChat itself, including Weidian shops, and paying individual sellers directly by scanning a QR code. Many buyers eventually set up both wallets, side by side.",
                'steps' => [
                    [
                        'title' => 'Download WeChat and register',
                        'body' => "Install WeChat and create an account using your phone number. WeChat is first and foremost a messaging app, Wallet is a feature built inside it, found in the 'Me' menu.",
                    ],
                    [
                        'title' => "Open the Wallet section",
                        'body' => "From the 'Me' tab, tap 'Services' (or 'Wallet,' depending on your version) to find your WeChat balance and payment tools. If you don't see it immediately, it usually appears the first time you attempt a payment or add money.",
                    ],
                    [
                        'title' => 'Verify your identity',
                        'body' => "Like Alipay, WeChat Pay needs basic identity verification before you can hold and spend a balance, a short, standard step required to keep the wallet secure.",
                    ],
                    [
                        'title' => 'Fund your balance through LshopBridge',
                        'body' => "From your LshopBridge dashboard, choose 'Fund China wallet,' select WeChat Pay, and enter your amount. Pay with Mobile Money, bank, card or crypto, and the balance is delivered directly into your WeChat wallet, the same simple flow as funding Alipay.",
                    ],
                    [
                        'title' => 'Pay a seller directly',
                        'body' => "Many small sellers, especially on Weidian, ask you to simply scan their QR code inside WeChat and enter the amount, this sends payment straight to them, similar to a Mobile Money transfer to a shop.",
                        'tip' => "Only scan and pay codes shown inside an official order or checkout flow, treat any request to pay a 'personal' code outside of a real order with caution.",
                    ],
                    [
                        'title' => 'Know when you need WeChat Pay vs Alipay',
                        'body' => "Use Alipay for Taobao, Tmall, 1688, JD.com and Pinduoduo. Use WeChat Pay for Weidian and any seller who asks you to pay by scanning a WeChat QR code. Keeping both funded gives you the flexibility to buy from anywhere.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Can I use WeChat Pay on Taobao?', 'a' => 'Generally no, Taobao and Tmall checkout is built around Alipay. WeChat Pay is used mainly inside WeChat itself, including Weidian shops.'],
                    ['q' => 'Which wallet should I set up first?', 'a' => 'Start with Alipay, it unlocks more of the big platforms. Add WeChat Pay once you need to buy from a Weidian shop or pay a seller directly by QR code.'],
                ],
            ],

            // ───────────────────────────────────────────────  Shipping ──
            [
                'title' => 'How shipping to your warehouse works',
                'category' => 'shipping',
                'excerpt' => 'Almost every China seller ships inside China only, this is the bridge that gets your goods to Africa.',
                'body' => "Here's something every new buyer needs to understand early: almost no seller on Taobao, 1688, Tmall, JD or Pinduoduo will ship a parcel directly to your country. They ship inside China only. The bridge between 'bought in China' and 'delivered to Africa' is a shipping agent and their warehouse, and once you understand how it works, it becomes the easiest part of the whole process.",
                'steps' => [
                    [
                        'title' => 'Understand the role of an agent',
                        'body' => "A shipping (or sourcing) agent is a company, based in China, that receives your parcels on your behalf, stores them safely, and then ships them onward to you in one combined shipment. LshopBridge's Marketplace connects you with verified, rated agents you can chat with directly.",
                    ],
                    [
                        'title' => 'Get your warehouse address',
                        'body' => "Once you've chosen an agent from the Marketplace, they'll give you a China warehouse address, plus a unique code or note to add to every order, this code is how they match incoming parcels to your account.",
                    ],
                    [
                        'title' => 'Use that address every time you check out',
                        'body' => "Whenever you buy on Taobao, Tmall, 1688, JD or Pinduoduo, enter your agent's warehouse address as the delivery address, and include your personal code in the order notes if asked. Save it once as your default address so every future order is one tap faster.",
                    ],
                    [
                        'title' => 'Let orders arrive and gather',
                        'body' => "You don't need to ship the moment one item arrives. Most buyers place several orders over days or weeks and let them all gather at the warehouse first, this is called consolidation, and it's the key to saving money.",
                    ],
                    [
                        'title' => 'Why consolidating saves you money',
                        'body' => "Shipping ten small parcels separately is far more expensive than shipping one combined parcel of the same total weight. Your agent repacks everything you've collected into a single, tighter shipment, cutting out wasted packaging and repeated shipping fees.",
                    ],
                    [
                        'title' => 'Choose your shipping method',
                        'body' => "Air shipping is faster (roughly a week or two) and costs more per kilogram. Sea shipping is much cheaper for heavy or bulky goods but slower (often several weeks). Ask your agent for both quotes and pick based on how urgently you need the goods.",
                    ],
                    [
                        'title' => 'Confirm your final quote and pay',
                        'body' => "Once you're ready to ship, your agent weighs and measures the combined parcel, and gives you a final price. Confirm the details, pay through LshopBridge, and track your shipment through to delivery.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'How long can my goods wait at the warehouse?', 'a' => 'Most agents allow a reasonable free storage period, check the specific terms shown on their Marketplace profile.'],
                    ['q' => 'What if I only ever buy one item at a time?', 'a' => "That's fine too, you can ship a single parcel any time. Consolidating simply becomes more valuable the more you order at once."],
                    ['q' => 'Is air or sea shipping better?', 'a' => 'Air suits urgent or lightweight orders; sea suits heavy, bulky, or non-urgent orders where saving money matters more than speed.'],
                ],
            ],

            // ────────────────────────────────────────────────  Customs ──
            [
                'title' => 'Customs and delivery, explained simply',
                'category' => 'customs',
                'excerpt' => "What happens between 'shipped from China' and 'knock on your door', no surprises.",
                'body' => "Customs sounds intimidating the first time you hear the word, but the idea behind it is simple: it's your own country checking what's coming in, and sometimes charging a small fee before releasing it to you. Once you understand the basic steps, it stops being a mystery and just becomes one more normal part of your order's journey.",
                'steps' => [
                    [
                        'title' => 'What customs actually is',
                        'body' => "Customs is a government department at your border, airport or port. Its job is to check incoming packages, confirm what's inside, and, for many goods, collect an import duty, a small tax on goods entering the country, before letting the parcel continue to you.",
                    ],
                    [
                        'title' => 'How import duty is generally worked out',
                        'body' => "Duty is usually a percentage of the declared value of the goods, and the percentage can depend on what the item is (electronics, clothing, and so on). Your shipping agent, or your country's customs authority, can tell you the typical rate for your kind of goods.",
                    ],
                    [
                        'title' => 'What your agent needs from you',
                        'body' => "To clear customs smoothly, your agent usually needs an accurate description of what's inside your shipment and its value. Always answer these questions honestly and promptly, an accurate declaration is what keeps your parcel moving quickly.",
                    ],
                    [
                        'title' => 'How long clearance usually takes',
                        'body' => "For most ordinary personal shipments, customs clearance takes anywhere from a day to about a week, depending on your country and how busy the port or airport is at the time. Sea shipments are naturally part of a longer overall journey, but the customs step itself is similar.",
                    ],
                    [
                        'title' => 'Last-mile delivery, the final stretch',
                        'body' => "Once a shipment clears customs, it moves to 'last-mile delivery', the local courier trip from the port or airport to your actual address. This final stretch is tracked the same way as the rest of your shipment.",
                    ],
                    [
                        'title' => 'If a package is delayed or held',
                        'body' => "Don't panic, delays are common and usually resolve themselves. Contact your agent first; they can often see exactly where the hold-up is and what, if anything, is needed from you to release it.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'Will I always have to pay a customs fee?', 'a' => 'It depends on your country, the type of goods, and their value, some low-value personal shipments are exempt. Your agent can give you a realistic expectation.'],
                    ['q' => 'What if customs asks about my package directly?', 'a' => "This is normal procedure, not a sign of a problem. Answer honestly, and involve your agent, they handle this regularly and can guide you."],
                ],
            ],

            // ─────────────────────────────────────────────── Mistakes ──
            [
                'title' => 'Common mistakes to avoid when buying from China',
                'category' => 'mistakes',
                'excerpt' => 'Learn from other buyers\' slip-ups, so your first orders go smoothly.',
                'body' => "Every experienced buyer has a story about an order that went wrong early on, the wrong size, an unexpected fee, a seller who never replied. The good news is that almost every common mistake is easy to avoid once you know it's coming. Here are the ones to watch for.",
                'steps' => [
                    [
                        'title' => 'Mistake #1, Not checking seller ratings',
                        'body' => "Every big platform shows a seller rating and history somewhere on the page. Skipping this check is the single most common reason buyers end up disappointed. Make it a habit: always glance at the rating and a few reviews before adding to cart.",
                    ],
                    [
                        'title' => 'Mistake #2, Ignoring real size and weight',
                        'body' => "A photo can't tell you how heavy or bulky something really is. Check the listed dimensions and weight, especially for furniture, appliances or anything large, this affects your shipping cost far more than most buyers expect.",
                    ],
                    [
                        'title' => 'Mistake #3, Paying outside the platform',
                        'body' => "If anyone, a seller, an 'agent,' or even someone claiming to be from LshopBridge, asks you to pay them directly outside of the normal checkout or your LshopBridge dashboard, stop. Legitimate payments always flow through the platform's proper checkout or a verified funding flow, never a private personal transfer.",
                        'tip' => "When in doubt, open a support ticket from your dashboard and ask before you pay anything unusual.",
                    ],
                    [
                        'title' => "Mistake #4, Being afraid to ask questions",
                        'body' => "New buyers sometimes stay quiet because of the language barrier, and end up guessing instead of asking. Sellers are used to simple, translated messages, a short question like 'Do you have this in blue? Can you ship 10 pieces?' is completely normal and usually gets a quick, helpful answer.",
                    ],
                    [
                        'title' => 'Mistake #5, Not double-checking variants before paying',
                        'body' => "Colour, size, and style are chosen with small buttons that are easy to tap past without noticing. Take five extra seconds on the checkout screen to confirm you selected exactly what you meant to.",
                    ],
                    [
                        'title' => 'Mistake #6, Not keeping records',
                        'body' => "Screenshot your order confirmation, your chat with the seller, and any promises made about price or condition. If a dispute ever comes up, having your own copy of the conversation makes resolving it much faster.",
                    ],
                    [
                        'title' => 'Mistake #7, Going big before going small',
                        'body' => "It's tempting to place one large order the moment you find a great price, especially on 1688 or DHgate. Resist that urge with any new supplier, a small first order costs little to test, and protects you from a costly surprise.",
                    ],
                ],
                'faqs' => [
                    ['q' => 'I already made one of these mistakes, what do I do?', 'a' => 'Contact the seller or your agent calmly and explain the issue, most are resolved through normal chat and after-sales tools. If you need a hand, open a support ticket from your LshopBridge dashboard.'],
                    ['q' => 'Is buying from China generally risky?', 'a' => "Not at all, once you know these basics, millions of people shop this way safely every single day. A little care at the start goes a long way."],
                ],
            ],

            // ───────────────────────────────────────────────  Glossary ──
            [
                'title' => 'Glossary, key words every China buyer should know',
                'category' => 'glossary',
                'excerpt' => 'A short, plain-language dictionary of every term used across this academy.',
                'body' => "You'll meet the same handful of words again and again across every guide in this academy. Bookmark this page, it's a quick dictionary you can come back to any time a term doesn't ring a bell.",
                'steps' => [
                    ['title' => 'Alipay', 'body' => "China's most widely used digital wallet. Needed to check out on Taobao, Tmall, 1688, JD.com and Pinduoduo. Fund it through LshopBridge."],
                    ['title' => 'WeChat Pay', 'body' => "China's other major digital wallet, built inside the WeChat messaging app. Needed for Weidian shops and paying sellers directly by QR code."],
                    ['title' => 'MOQ (Minimum Order Quantity)', 'body' => 'The smallest number of units a wholesale seller, usually on 1688, will agree to sell in one order.'],
                    ['title' => 'Wangwang', 'body' => "The built-in chat tool used to message sellers directly on Taobao and 1688, look for a small speech-bubble icon on the product page."],
                    ['title' => 'Flagship store', 'body' => "An official, brand-verified shop, most commonly seen on Tmall, as opposed to an independent small seller."],
                    ['title' => 'Self-operated (自营)', 'body' => "On JD.com, this label means JD itself owns and ships the stock, rather than an outside third-party seller, generally the safest option for electronics."],
                    ['title' => 'Consolidation', 'body' => "Combining several separate orders into one shipment at your agent's warehouse, to save on total shipping cost."],
                    ['title' => 'Air freight vs. sea freight', 'body' => 'Two shipping methods offered by agents: air is faster and pricier; sea is slower and much cheaper for heavy or bulky goods.'],
                    ['title' => 'Customs duty', 'body' => "A tax your own country may charge on goods entering from abroad, usually a percentage of the item's declared value."],
                    ['title' => 'Sourcing / shipping agent', 'body' => "A China-based company that receives your online orders at their warehouse and ships them onward to you, find verified ones in the LshopBridge Marketplace."],
                    ['title' => 'Buyer protection', 'body' => "Built-in refund and dispute tools on most platforms, which hold a seller accountable if an item never arrives or doesn't match its listing."],
                    ['title' => 'Note (小红书)', 'body' => "A short post, photos plus an honest opinion, shared by an ordinary shopper on Xiaohongshu, used to research a product before buying it elsewhere."],
                ],
                'faqs' => [],
            ],
        ];
    }
}
