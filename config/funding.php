<?php

use App\Services\Funding\Providers\AlipayFundingProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | China wallet funding provider
    |--------------------------------------------------------------------------
    | The engine that pushes money into a recipient's Alipay / WeChat Pay /
    | other China wallet after the user's payment has cleared. Build & test in
    | sandbox; flip to live once a payment partner agreement is in place.
    */
    'default' => 'alipay',

    'mode' => env('ALIPAY_FUNDING_MODE', env('PROVIDER_MODE', 'sandbox')),

    'providers' => [
        'alipay' => [
            'label'  => 'Alipay / China Wallet Funding',
            'driver' => AlipayFundingProvider::class,
            'mode'   => env('ALIPAY_FUNDING_MODE', env('PROVIDER_MODE', 'sandbox')),
            'base_url' => env('ALIPAY_FUNDING_BASE_URL'),
            'partner_id' => env('ALIPAY_FUNDING_PARTNER_ID'),
            'api_key'  => env('ALIPAY_FUNDING_API_KEY'),
            'api_secret' => env('ALIPAY_FUNDING_API_SECRET'),
            'webhook_secret' => env('ALIPAY_FUNDING_WEBHOOK_SECRET'),
        ],
    ],

    // Supported destination apps shown to users.
    'apps' => [
        'alipay' => 'Alipay',
        'wechat' => 'WeChat Pay',
        'other'  => 'Other China wallet',
    ],
];
