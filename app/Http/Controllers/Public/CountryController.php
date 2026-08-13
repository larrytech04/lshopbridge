<?php

namespace App\Http\Controllers\Public;

use App\Enums\CountryLaunchStatus;
use App\Http\Controllers\Controller;
use App\Models\ChinaWalletType;
use App\Models\Country;
use App\Models\ExchangeRate;
use App\Models\PaymentMethod;
use App\Services\Seo\CanonicalUrlService;
use App\Services\Seo\StructuredDataBuilder;
use Illuminate\View\View;

/**
 * Country landing pages — every fact rendered here is pulled from real,
 * admin-configured data (Mobile Money numbers, payment-method country
 * restrictions, China-wallet-type country restrictions, live exchange
 * rates), never invented. See Country::hasRealPaymentInfrastructure() for
 * the signal that decides whether an individual country's page is
 * indexable at all — most of the 190+ countries in this table are just
 * "allowed to register from", not somewhere with real local payment rails
 * configured yet, and a page with nothing to differentiate it from any
 * other country stays noindex rather than being a thin doorway page.
 */
class CountryController extends Controller
{
    public function index(CanonicalUrlService $canonical, StructuredDataBuilder $schema): View
    {
        $countries = Country::active()->get();

        $breadcrumbs = [
            ['name' => __('Home'), 'url' => $canonical->normalize(route('home'))],
            ['name' => __('Countries'), 'url' => $canonical->normalize(route('countries.index'))],
        ];

        return view('public.countries.index', [
            'countries' => $countries,
            'breadcrumbs' => $breadcrumbs,
            'breadcrumbSchema' => $schema->breadcrumbList($breadcrumbs),
        ]);
    }

    public function show(Country $country, CanonicalUrlService $canonical, StructuredDataBuilder $schema): View
    {
        abort_unless($country->is_active && $country->launch_status !== CountryLaunchStatus::Disabled, 404);

        $momoProviders = $country->momoNumbers()->where('is_active', true)->get();

        $paymentMethods = PaymentMethod::active()->get()
            ->filter(fn (PaymentMethod $method) => $method->isAvailableInCountry($country->iso2))
            ->values();

        $walletTypes = ChinaWalletType::active()->get()
            ->filter(fn (ChinaWalletType $type) => $type->allowsCountry($country->iso2))
            ->values();

        $exchangeRate = $country->currency_code
            ? ExchangeRate::where('base_currency', $country->currency_code)
                ->where('quote_currency', 'CNY')
                ->where('is_active', true)
                ->first()
            : null;

        // The one real signal a page here has anything to say that a plain
        // "not available yet" notice wouldn't — see Country::hasRealPaymentInfrastructure().
        $hasRealContent = $momoProviders->isNotEmpty() || $paymentMethods->isNotEmpty();
        $isFullyLaunched = $country->launch_status === CountryLaunchStatus::Active;

        $breadcrumbs = [
            ['name' => __('Home'), 'url' => $canonical->normalize(route('home'))],
            ['name' => __('Countries'), 'url' => $canonical->normalize(route('countries.index'))],
            ['name' => $country->name, 'url' => $canonical->normalize(route('countries.show', $country))],
        ];

        return view('public.countries.show', [
            'country' => $country,
            'momoProviders' => $momoProviders,
            'paymentMethods' => $paymentMethods,
            'walletTypes' => $walletTypes,
            'exchangeRate' => $exchangeRate,
            'hasRealContent' => $hasRealContent,
            'isFullyLaunched' => $isFullyLaunched,
            // A country with nothing real to differentiate it, or one the
            // admin hasn't actually launched yet, stays noindex — see
            // brief section 6/14. Still fully viewable if visited directly.
            'robotsOverride' => ($hasRealContent && $isFullyLaunched) ? null : 'noindex,follow',
            'breadcrumbs' => $breadcrumbs,
            'breadcrumbSchema' => $schema->breadcrumbList($breadcrumbs),
        ]);
    }
}
