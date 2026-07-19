<?php

use App\Services\Settings\SettingsService;

if (! function_exists('setting')) {
    /**
     * Read an admin-managed setting (DB-backed, cached) with a fallback.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}

if (! function_exists('site_logo')) {
    /** URL of the site logo, admin-uploaded if set, else the bundled default. */
    function site_logo(): string
    {
        $custom = setting('site_logo_path');

        return $custom ? asset($custom) : asset('assets/'.rawurlencode('shopbridge logo.png'));
    }
}

if (! function_exists('site_favicon')) {
    /** URL of the favicon, admin-uploaded if set, else the bundled default icon mark. */
    function site_favicon(): string
    {
        $custom = setting('site_favicon_path');

        return $custom ? asset($custom) : asset('assets/'.rawurlencode('favicon shopbridge.png'));
    }
}

if (! function_exists('cms')) {
    /**
     * Admin-editable site content block (Admin → Page content). Returns the
     * stored value if set, otherwise the provided default (usually __('...')).
     */
    function cms(string $key, string $default = ''): string
    {
        $value = setting($key);

        return ($value === null || $value === '') ? $default : (string) $value;
    }
}

if (! function_exists('money')) {
    /**
     * Format an amount with a currency code for display.
     */
    function money(int|float|string|null $amount, string $currency = 'XAF', int $decimals = 2): string
    {
        return number_format((float) $amount, $decimals).' '.$currency;
    }
}

if (! function_exists('reference')) {
    /**
     * Generate a unique, human-readable reference, e.g. PB-DEP-7F3K9Q2A.
     */
    function reference(string $prefix): string
    {
        return strtoupper($prefix.'-'.\Illuminate\Support\Str::random(10));
    }
}

if (! function_exists('display_currency')) {
    /**
     * The visitor's active display currency (set when a country is selected),
     * falling back to the platform base. Returns code, symbol, rate, decimals.
     *
     * @return array{code: string, symbol: string, rate: float, decimals: int}
     */
    function display_currency(): array
    {
        $base = config('platform.base_currency', 'XAF');

        if (session()->has('display_currency')) {
            return session('display_currency');
        }

        $cfg = config("platform.currencies.$base", ['symbol' => $base, 'rate' => 1, 'decimals' => 0]);

        return ['code' => $base, 'symbol' => $cfg['symbol'], 'rate' => $cfg['rate'], 'decimals' => $cfg['decimals']];
    }
}

if (! function_exists('disp')) {
    /**
     * Convert a BASE-currency (XAF) amount to the visitor's display currency and
     * format it. Display-only, the underlying ledger remains in the base currency.
     */
    function disp(int|float|string|null $baseAmount): string
    {
        $c = display_currency();
        $value = (float) $baseAmount * ($c['rate'] ?? 1);

        return $c['symbol'].' '.number_format($value, $c['decimals'] ?? 0);
    }
}

if (! function_exists('locale_dir')) {
    /** Text direction for the active locale ('rtl' for Arabic/Persian/Urdu/Hebrew). */
    function locale_dir(): string
    {
        return in_array(app()->getLocale(), config('platform.rtl_locales', []), true) ? 'rtl' : 'ltr';
    }
}

if (! function_exists('geo_country')) {
    /**
     * Best-effort country ISO2 from the request. Uses Cloudflare's CF-IPCountry
     * header when present (set automatically behind Cloudflare). Returns null if
     * unknown, the onboarding popup then falls back to a client-side IP lookup.
     */
    function geo_country(): ?string
    {
        $iso = request()->header('CF-IPCountry');

        if ($iso && strlen($iso) === 2 && ctype_alpha($iso)) {
            return strtoupper($iso);
        }

        return null;
    }
}

if (! function_exists('locales')) {
    /** Every known UI language (code => native label). */
    function locales(): array
    {
        return config('platform.locales', ['en' => 'English']);
    }
}

if (! function_exists('supported_locales')) {
    /**
     * Languages we ship a full catalog for, the only ones the picker offers.
     * Returns code => native label, ordered as in config.
     */
    function supported_locales(): array
    {
        $all = config('platform.locales', ['en' => 'English']);
        $supported = config('platform.supported_locales', ['en']);

        return array_intersect_key($all, array_flip($supported));
    }
}

if (! function_exists('is_supported_locale')) {
    function is_supported_locale(string $locale): bool
    {
        return in_array($locale, config('platform.supported_locales', ['en']), true);
    }
}

if (! function_exists('current_locale')) {
    function current_locale(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('guide_category_meta')) {
    /**
     * Display metadata for a Learning Center guide category: icon, Tailwind
     * color name, and label. Platform/app names (1688, Taobao, Alipay, …) are
     * proper nouns and intentionally left untranslated; only the descriptive
     * categories are wrapped in __().
     *
     * @return array{icon: string, color: string, label: string}
     */
    function guide_category_meta(string $category): array
    {
        return match ($category) {
            '1688' => ['icon' => 'building', 'color' => 'amber', 'label' => '1688'],
            'taobao' => ['icon' => 'bag', 'color' => 'orange', 'label' => 'Taobao'],
            'tmall' => ['icon' => 'star', 'color' => 'rose', 'label' => 'Tmall'],
            'pinduoduo' => ['icon' => 'users', 'color' => 'pink', 'label' => 'Pinduoduo'],
            'jd' => ['icon' => 'gauge', 'color' => 'red', 'label' => 'JD.com'],
            'xiaohongshu' => ['icon' => 'sparkles', 'color' => 'rose', 'label' => 'Xiaohongshu (RED)'],
            'weidian' => ['icon' => 'mail', 'color' => 'emerald', 'label' => 'Weidian'],
            'aliexpress' => ['icon' => 'globe', 'color' => 'sky', 'label' => 'AliExpress'],
            'dhgate' => ['icon' => 'cart', 'color' => 'amber', 'label' => 'DHgate'],
            'alipay' => ['icon' => 'wallet', 'color' => 'sky', 'label' => 'Alipay'],
            'wechatpay' => ['icon' => 'phone', 'color' => 'emerald', 'label' => 'WeChat Pay'],
            'shipping' => ['icon' => 'truck', 'color' => 'violet', 'label' => __('Shipping & warehouses')],
            'customs' => ['icon' => 'doc', 'color' => 'slate', 'label' => __('Customs & delivery')],
            'mistakes' => ['icon' => 'alert', 'color' => 'rose', 'label' => __('Mistakes to avoid')],
            'orientation' => ['icon' => 'filter', 'color' => 'brand', 'label' => __('Getting started')],
            'glossary' => ['icon' => 'languages', 'color' => 'sky', 'label' => __('Glossary')],
            default => ['icon' => 'book', 'color' => 'slate', 'label' => ucfirst($category)],
        };
    }
}

if (! function_exists('region')) {
    /**
     * The visitor's selected country/region (session, then logged-in user's
     * country, then platform default). Drives the header selector site-wide.
     *
     * @return array{iso: string, name: string, flag: string}
     */
    function region(): array
    {
        if (session()->has('region')) {
            return session('region');
        }

        if (auth()->check() && auth()->user()->country) {
            $c = auth()->user()->country;

            return ['iso' => $c->iso2, 'name' => $c->name, 'flag' => $c->flag_emoji ?? '🌍'];
        }

        return ['iso' => 'CM', 'name' => 'Cameroon', 'flag' => '🇨🇲'];
    }
}
