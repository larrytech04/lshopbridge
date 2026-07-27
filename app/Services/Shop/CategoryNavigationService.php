<?php

namespace App\Services\Shop;

use App\Models\ShopCategory;
use Illuminate\Support\Collection;

/**
 * Single source of truth for "which categories should this visitor see in
 * navigation" — the sidebar rail, the Marketplace mega-menu, and the shop
 * page's own category list all call this instead of each re-implementing
 * the active/menu_visible/country/date-window rules independently.
 */
class CategoryNavigationService
{
    /** Top-level categories with their visible children, filtered for the given country. */
    public function visibleTopLevel(?string $countryIso = null): Collection
    {
        return ShopCategory::visibleInNavigation()
            ->topLevel()
            ->withCount('products')
            ->with(['children' => fn ($q) => $q->visibleInNavigation()->withCount('products')])
            ->get()
            ->filter(fn (ShopCategory $c) => $c->isAvailableForCountry($countryIso))
            ->map(function (ShopCategory $c) use ($countryIso) {
                $c->setRelation('children', $c->children->filter(
                    fn (ShopCategory $child) => $child->isAvailableForCountry($countryIso)
                )->values());

                return $c;
            })
            ->values();
    }

    public function featured(?string $countryIso = null, int $limit = 6): Collection
    {
        return $this->visibleTopLevel($countryIso)
            ->filter(fn (ShopCategory $c) => $c->featured)
            ->take($limit)
            ->values();
    }
}
