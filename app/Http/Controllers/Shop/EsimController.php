<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\ShopProduct;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The eSIM destination-selector landing page. Deliberately reuses the
 * generic ShopProduct/ShopVariant catalog rather than a parallel eSIM-only
 * product table — plan details/comparison live on the existing shop.show
 * page (see ShopController), which renders a dedicated eSIM layout for
 * type=esim products.
 */
class EsimController extends Controller
{
    public function index(Request $request): View
    {
        $products = ShopProduct::active()->where('type', 'esim')
            ->with('activeVariants')
            ->whereHas('activeVariants')
            ->get();

        $scope = in_array($request->query('scope'), ['local', 'regional', 'global'], true) ? $request->query('scope') : null;

        if ($q = trim((string) $request->query('q', ''))) {
            $products = $products->filter(fn ($p) => str_contains(mb_strtolower($p->name), mb_strtolower($q))
                || str_contains(mb_strtolower((string) $p->region), mb_strtolower($q)));
        }

        $scopeCounts = [
            'local' => $products->where('esim_scope', 'local')->count(),
            'regional' => $products->where('esim_scope', 'regional')->count(),
            'global' => $products->where('esim_scope', 'global')->count(),
        ];

        if ($scope) {
            $products = $products->where('esim_scope', $scope);
        }

        // Real destinations only — flattened from each product's own confirmed
        // coverage list, never a fabricated "N countries" figure.
        $countryCodes = $products->flatMap(fn ($p) => $p->esim_coverage_countries ?? [])->unique()->values();
        $destinations = Country::whereIn('iso2', $countryCodes)->orderBy('name')->get(['iso2', 'name', 'flag_emoji']);

        return view('shop.esim.landing', [
            'products' => $products->values(),
            'destinations' => $destinations,
            'scope' => $scope,
            'scopeCounts' => $scopeCounts,
            'q' => $request->query('q', ''),
        ]);
    }
}
