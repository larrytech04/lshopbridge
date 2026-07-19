<?php

/*
|--------------------------------------------------------------------------
| Editable site content (CMS)
|--------------------------------------------------------------------------
| Front-end text blocks that admins can edit from Admin → Page content.
| Each block: [settings key, label, input ('text'|'textarea'), default].
| The default is the English copy (still translated via __()). To make a new
| piece of text editable: add a row here, then in the Blade use
|   {{ cms('the_key', __('the default text')) }}
*/

return [
    'blocks' => [

        'Home: section headings' => [
            ['cms_home_payments_title', 'Payment methods: title', 'text', 'Accepted payment methods'],
            ['cms_home_giftcards_title', 'Popular gift cards: title', 'text', 'Popular gift cards'],
            ['cms_home_giftcards_subtitle', 'Popular gift cards: subtitle', 'text', 'Top brands, delivered instantly to your account.'],
            ['cms_home_esim_title', 'Travel eSIMs: title', 'text', 'Global travel eSIMs'],
            ['cms_home_esim_subtitle', 'Travel eSIMs: subtitle', 'textarea', 'Get a data eSIM for 190+ countries, installed in minutes, no physical SIM. Choose a plan below.'],
            ['cms_home_features_title', 'Features: title', 'text', 'Everything you need, in one place'],
            ['cms_home_features_subtitle', 'Features: subtitle', 'textarea', 'Hover a panel to explore what makes LshopBridge fast, safe and simple.'],
            ['cms_home_reviews_title', 'Reviews: title', 'text', 'What our customers say'],
            ['cms_home_cta_title', 'Bottom CTA: title', 'text', 'Ready to fund your China wallet?'],
            ['cms_home_cta_subtitle', 'Bottom CTA: subtitle', 'textarea', 'Create a free account and send your first payment in minutes.'],
        ],

        'Payment methods page' => [
            ['cms_pmpage_title', 'Title', 'text', 'Accepted payment methods'],
            ['cms_pmpage_subtitle', 'Subtitle', 'textarea', 'Top up your wallet using the channels you already trust, mobile money, cards, bank transfer, USSD & crypto, accepted across Africa.'],
        ],

        'Footer' => [
            ['cms_footer_tagline', 'Tagline', 'textarea', 'Make payments, fund China wallets, and buy instant digital products, gift cards, eSIMs & VPN, delivered in minutes.'],
        ],
    ],
];
