<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

/**
 * Header language switcher, country/region selector, and the first-visit
 * onboarding popup. Choices persist in the session (and on the user when
 * authenticated) so they apply across the whole site on every request.
 */
class LocalizationController extends Controller
{
    public function setLocale(Request $request, string $locale)
    {
        $this->applyLocale($request, $locale);

        return back();
    }

    public function setCountry(Request $request, string $iso)
    {
        $this->applyCountry($request, $iso);

        return back();
    }

    /** First-visit onboarding: set country, language and (client-side) theme at once. */
    public function onboard(Request $request)
    {
        if ($iso = $request->query('country')) {
            $this->applyCountry($request, $iso);
        }
        if ($loc = $request->query('locale')) {
            $this->applyLocale($request, $loc);
        }

        return back();
    }

    private function applyLocale(Request $request, string $locale): void
    {
        // Only switch to languages we ship a complete catalog for; any other
        // mapping (e.g. a country whose language we don't translate yet) is left
        // on the current locale so the site never shows a half-translated page.
        if (! in_array($locale, config('platform.supported_locales', ['en']), true)) {
            return;
        }
        session(['locale' => $locale]);
        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }
    }

    private function applyCountry(Request $request, string $iso): void
    {
        $country = Country::where('iso2', strtoupper($iso))->where('is_active', true)->first();
        if (! $country) {
            return;
        }

        session(['region' => [
            'iso' => $country->iso2,
            'name' => $country->name,
            'flag' => $country->flag_emoji ?? '🌍',
        ]]);

        $code = config("platform.country_currency.{$country->iso2}")
            ?? ($country->currency_code ?: config('platform.base_currency', 'XAF'));
        $cfg = config("platform.currencies.$code", ['symbol' => $code, 'rate' => 1, 'decimals' => 0]);
        session(['display_currency' => [
            'code' => $code, 'symbol' => $cfg['symbol'], 'rate' => $cfg['rate'], 'decimals' => $cfg['decimals'],
        ]]);

        // Language follows the country unless explicitly chosen elsewhere.
        if ($loc = config("platform.country_locale.{$country->iso2}")) {
            $this->applyLocale($request, $loc);
        }

        if ($request->user()) {
            $request->user()->update(['country_id' => $country->id]);
        }
    }
}
