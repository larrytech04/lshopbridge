<?php

use App\Services\Payments\Providers\CardGatewayProvider;
use App\Services\Payments\Providers\CryptoGatewayProvider;
use App\Services\Payments\Providers\FlutterwaveProvider;
use App\Services\Payments\Providers\MtnMomoProvider;
use App\Services\Payments\Providers\OrangeMoneyProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Default provider mode
    |--------------------------------------------------------------------------
    | sandbox = mock charges + simulated webhooks (safe, no live money).
    | live    = real provider HTTP calls (wire the TODO sections first).
    */
    'mode' => env('PROVIDER_MODE', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Provider registry
    |--------------------------------------------------------------------------
    | Each key is the `code` stored on payment_methods / payment_providers.
    | The PaymentManager resolves the driver class and hands it this config.
    | Secrets ALWAYS come from env — never hard-code them here.
    */
    'providers' => [

        'mtn_momo' => [
            'label'  => 'MTN Mobile Money',
            'driver' => MtnMomoProvider::class,
            'mode'   => env('MTN_MOMO_MODE', env('PROVIDER_MODE', 'sandbox')),
            'base_url' => env('MTN_MOMO_BASE_URL'),
            'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
            'api_user' => env('MTN_MOMO_API_USER'),
            'api_key'  => env('MTN_MOMO_API_KEY'),
            'webhook_secret' => env('MTN_MOMO_WEBHOOK_SECRET'),
        ],

        'orange_money' => [
            'label'  => 'Orange Money',
            'driver' => OrangeMoneyProvider::class,
            'mode'   => env('ORANGE_MONEY_MODE', env('PROVIDER_MODE', 'sandbox')),
            'base_url' => env('ORANGE_MONEY_BASE_URL'),
            'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
            'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
            'webhook_secret' => env('ORANGE_MONEY_WEBHOOK_SECRET'),
        ],

        'flutterwave' => [
            'label'  => 'Flutterwave (Card & Mobile Money)',
            'driver' => FlutterwaveProvider::class,
            'mode'   => env('FLUTTERWAVE_MODE', env('PROVIDER_MODE', 'sandbox')),
            'base_url' => env('FLUTTERWAVE_BASE_URL'),
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
            'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET'),
        ],

        'crypto' => [
            'label'  => 'Crypto (USDT/BTC)',
            'driver' => CryptoGatewayProvider::class,
            'mode'   => env('CRYPTO_GATEWAY_MODE', env('PROVIDER_MODE', 'sandbox')),
            'base_url' => env('CRYPTO_GATEWAY_BASE_URL'),
            'api_key'  => env('CRYPTO_GATEWAY_API_KEY'),
            'webhook_secret' => env('CRYPTO_GATEWAY_WEBHOOK_SECRET'),
        ],

        'card' => [
            'label'  => 'Card / Prepaid',
            'driver' => CardGatewayProvider::class,
            'mode'   => env('CARD_GATEWAY_MODE', env('PROVIDER_MODE', 'sandbox')),
            'base_url' => env('CARD_GATEWAY_BASE_URL'),
            'api_key'  => env('CARD_GATEWAY_API_KEY'),
            'webhook_secret' => env('CARD_GATEWAY_WEBHOOK_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Accepted payment methods (display / logo wall)
    |--------------------------------------------------------------------------
    | The full set of channels we accept across Africa — most powered by
    | Flutterwave — shown on the home page, the payment-methods page and the
    | footer. Each entry is [pay-icon key, display name]; keys map to the
    | <x-pay-icon> component. Display-only (operational channels live in the
    | payment_methods table).
    */
    'accepted' => [
        'Cards' => [
            ['visa', 'Visa'],
            ['mastercard', 'Mastercard'],
            ['verve', 'Verve'],
            ['amex', 'American Express'],
        ],
        'Mobile Money' => [
            ['mtn', 'MTN MoMo'],
            ['orange', 'Orange Money'],
            ['airtel', 'Airtel Money'],
            ['vodafone', 'Vodafone Cash'],
            ['mpesa', 'M-Pesa'],
            ['tigo', 'Tigo Cash'],
            ['moov', 'Moov Money'],
            ['wave', 'Wave'],
        ],
        'Bank & USSD' => [
            ['bank', 'Bank transfer'],
            ['ussd', 'USSD'],
            ['account', 'Bank account'],
            ['enaira', 'eNaira'],
        ],
        'Wallets' => [
            ['barter', 'Barter'],
            ['applepay', 'Apple Pay'],
            ['googlepay', 'Google Pay'],
            ['paypal', 'PayPal'],
        ],
        'Crypto' => [
            ['btc', 'Bitcoin'],
            ['usdt', 'USDT'],
            ['usdc', 'USDC'],
            ['ltc', 'Litecoin'],
            ['ton', 'TON'],
            ['trx', 'TRON'],
            ['doge', 'Dogecoin'],
            ['bnb', 'BNB'],
            ['sol', 'Solana'],
            ['dai', 'DAI'],
            ['euroc', 'EUROC'],
            ['usds', 'USDS'],
            ['pyusd', 'PYUSD'],
            ['usde', 'USDE'],
            ['fdusd', 'FDUSD'],
            ['gram', 'GRAM'],
        ],
    ],
];
