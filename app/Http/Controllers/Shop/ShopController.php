<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
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

        return $this->storefront($request, $category);
    }

    /**
     * Shared storefront renderer for both the full shop and a single category
     * (each category gets its own hero + page via this method).
     */
    protected function storefront(Request $request, ?ShopCategory $active): View
    {
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

        $sort = in_array($request->query('sort'), ['az', 'za'], true) ? $request->query('sort') : 'popular';
        match ($sort) {
            'az' => $query->orderBy('name'),
            'za' => $query->orderByDesc('name'),
            default => $query->orderByDesc('is_best_deal')->orderByDesc('is_featured')->orderByDesc('sales_count')->orderBy('sort'),
        };

        return view('shop.index', [
            'products' => $query->paginate(12)->withQueryString(),
            'topCategories' => ShopCategory::active()->topLevel()->with('activeChildren')->get(),
            'activeTop' => $activeTop,
            'activeSub' => $activeSub,
            'subcategories' => $activeTop ? $activeTop->activeChildren()->orderBy('sort')->get() : collect(),
            'sort' => $sort,
            'filters' => $request->only('q'),
        ]);
    }

    public function show(ShopProduct $product): View
    {
        abort_unless($product->is_active, 404);

        return view('shop.show', [
            'product' => $product->load('category.parent', 'activeVariants'),
            'related' => ShopProduct::active()->where('shop_category_id', $product->shop_category_id)
                ->where('id', '!=', $product->id)->with('variants')->take(4)->get(),
        ]);
    }
}
