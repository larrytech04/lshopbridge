<?php

$world = require __DIR__.'/world.php';

return [

    /*
    |--------------------------------------------------------------------------
    | Branding fallbacks
    |--------------------------------------------------------------------------
    | These are *fallbacks only*. Anything an admin can edit lives in the
    | `settings` table (see App\Services\Settings\SettingsService). Read those
    | through `setting('key', config('platform.x'))` so the DB wins.
    */
    'name'    => env('APP_NAME', 'LshopBridge'),
    'tagline' => 'Fund Alipay, WeChat Pay & China wallets from anywhere in Africa.',
    'support_email' => 'support@lshopbridge.com',

    /*
    |--------------------------------------------------------------------------
    | Supported UI languages (the header language switcher)
    |--------------------------------------------------------------------------
    */
    // Languages, country→language/currency maps, currencies & the full country
    // list all come from config/world.php (all countries worldwide).
    'locales' => $world['locales'],
    // Locales we ship a *complete translation catalog* for. The language picker
    // only offers these so every choice fully translates the site; any other
    // country→locale mapping gracefully falls back to English. Add a code here
    // only once lang/<code>.json covers the full key set (see scripts/i18n_scan).
    'supported_locales' => ['en', 'fr', 'zh', 'es', 'pt'],
    'country_locale' => $world['country_locale'],
    'country_currency' => $world['country_currency'],
    'currencies' => array_map(fn ($v) => ['symbol' => $v[0], 'rate' => $v[1], 'decimals' => $v[2]], $world['currencies']),
    'countries' => $world['countries'],

    // RTL languages (header sets <html dir>).
    'rtl_locales' => ['ar', 'fa', 'ur', 'he'],

    /*
    |--------------------------------------------------------------------------
    | Automation master switches
    |--------------------------------------------------------------------------
    | When automation is OFF the platform safely degrades to manual review:
    | users upload proof of payment and admins confirm by hand.
    */
    'automation' => [
        'payments' => (bool) env('PAYMENTS_AUTOMATION_ENABLED', true),
        'funding'  => (bool) env('FUNDING_AUTOMATION_ENABLED', true),
    ],

    // Global default driver mode for providers that don't override it.
    'provider_mode' => env('PROVIDER_MODE', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | KYC levels and default limits (in platform base currency)
    |--------------------------------------------------------------------------
    | Seeded into the `kyc_levels` table; admin can override per level there.
    */
    'base_currency' => 'XAF',
    'target_currency' => 'CNY',

    /*
    |--------------------------------------------------------------------------
    | Referral rewards (LshopBridge Coins)
    |--------------------------------------------------------------------------
    | Paid out once, when a referred friend's KYC is approved for the first
    | time (App\Http\Controllers\Admin\KycController::approve).
    */
    'referrals' => [
        'referrer_points' => 100,
        'referred_points' => 20,
    ],

    'kyc_levels' => [
        0 => ['name' => 'Registered',        'daily' => 0,        'monthly' => 0,         'per_tx' => 0],
        1 => ['name' => 'Email/Phone',       'daily' => 100000,   'monthly' => 500000,    'per_tx' => 50000],
        2 => ['name' => 'ID Verified',       'daily' => 1000000,  'monthly' => 10000000,  'per_tx' => 500000],
        3 => ['name' => 'Business/Agent',    'daily' => 5000000,  'monthly' => 50000000,  'per_tx' => 2500000],
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk engine defaults
    |--------------------------------------------------------------------------
    */
    'risk' => [
        'max_failed_payments'      => 3,   // failed charges before flagging
        'velocity_count'           => 5,   // tx within window
        'velocity_window_minutes'  => 30,
        'large_tx_multiplier'      => 0.9, // % of per-tx limit considered "large"
    ],

    /*
    |--------------------------------------------------------------------------
    | Secure uploads
    |--------------------------------------------------------------------------
    | KYC docs, selfies and proof live on the PRIVATE disk and are only ever
    | streamed through authorised controllers, never linked publicly.
    */
    'private_disk' => 'private',
    'public_disk'  => 'public',
];
