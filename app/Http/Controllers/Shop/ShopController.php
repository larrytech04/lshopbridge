<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Services\Shop\CategoryNavigationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        return $this->storefront($request, null);
    }

    public function category(ShopCategory $category, Request $request): View
    {
        abort_unless($category->is_active, 404);
        abort_unless($category->isAvailableForCountry(region()['iso'] ?? null), 404);
        abort_unless($category->isCurrentlyAvailable(), 404);

        return $this->storefront($request, $category);
    }

    /**
     * Shared storefront renderer for both the full shop and a single category
     * (each category gets its own hero + page via this method).
     */
    protected function storefront(Request $request, ?ShopCategory $active): View
    {
        $categoryNav = app(CategoryNavigationService::class);
        // Resolve which top-level category and (optional) subcategory are active.
        $activeTop = $activeSub = null;
        if ($active) {
            if ($active->parent_id) {
                $activeSub = $active;
                $activeTop = $active->parent;
            } else {
                $activeTop = $active;
            }
        }

        $query = ShopProduct::active()->with(['category', 'variants'])
            ->whereHas('variants', fn ($q) => $q->where('is_active', true));

        if ($activeSub) {
            $query->where('shop_category_id', $activeSub->id);
        } elseif ($activeTop) {
            // A top-level category shows its own products plus those of its children.
            $ids = $activeTop->children()->pluck('id')->push($activeTop->id);
            $query->whereIn('shop_category_id', $ids);
        }

        if ($q = $request->query('q')) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('brand', 'like', "%{$q}%")
                ->orWhere('summary', 'like', "%{$q}%"));
        }

        $filter = in_array($request->query('filter'), ['deals', 'featured'], true) ? $request->query('filter') : null;
        match ($filter) {
            'deals' => $query->where('is_best_deal', true),
            'featured' => $query->where('is_featured', true),
            default => null,
        };

        $sort = in_array($request->query('sort'), ['az', 'za'], true) ? $request->query('sort') : 'popular';
        match ($sort) {
            'az' => $query->orderBy('name'),
            'za' => $query->orderByDesc('name'),
            default => $query->orderByDesc('is_best_deal')->orderByDesc('is_featured')->orderByDesc('sales_count')->orderBy('sort'),
        };

        return view('shop.index', [
            'products' => $query->paginate(12)->withQueryString(),
            'topCategories' => $categoryNav->visibleTopLevel(region()['iso'] ?? null),
            'activeTop' => $activeTop,
            'activeSub' => $activeSub,
            'subcategories' => $activeTop ? $activeTop->activeChildren()->orderBy('sort')->get() : collect(),
            'sort' => $sort,
            'filter' => $filter,
            'filters' => $request->only('q'),
        ]);
    }

    public function show(ShopProduct $product): View
    {
        abort_unless($product->is_active, 404);

        $user = auth()->user();

        return view('shop.show', [
            'product' => $product->load('category.parent', 'activeVariants'),
            'related' => ShopProduct::active()->where('shop_category_id', $product->shop_category_id)
                ->where('id', '!=', $product->id)->with('variants')->take(4)->get(),
            'inWishlist' => $user ? $user->wishlists()->where('shop_product_id', $product->id)->exists() : false,
        ]);
    }
}
