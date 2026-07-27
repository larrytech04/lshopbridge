<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Fee;
use App\Models\ShopCategory;
use App\Services\Admin\ShopCategoryAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ShopCategory::withCount(['products', 'children'])
            ->with('parent')
            ->orderBy('sort')
            ->get();

        return view('admin.shop.categories', [
            'categories' => $categories,
            'tree' => $this->buildTree($categories),
            'fees' => Fee::orderBy('name')->get(['id', 'name']),
            'countries' => Country::orderBy('name')->get(['id', 'iso2', 'name']),
        ]);
    }

    public function rowDetail(ShopCategory $category)
    {
        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'description' => $category->description,
            'tagline' => $category->tagline,
            'icon' => $category->icon,
            'image_path' => $category->image_path,
            'banner_path' => $category->banner_path,
            'accent' => $category->accent,
            'product_type' => $category->product_type,
            'sort' => $category->sort,
            'is_active' => $category->is_active,
            'featured' => $category->featured,
            'menu_visible' => $category->menu_visible,
            'default_fee_id' => $category->default_fee_id,
            'restricted_countries' => $category->restricted_countries ?? [],
            'navigation_badge' => $category->navigation_badge,
            'navigation_badge_style' => $category->navigation_badge_style,
            'available_from' => $category->available_from?->format('Y-m-d\TH:i'),
            'available_until' => $category->available_until?->format('Y-m-d\TH:i'),
            'seo_title' => $category->seo_title,
            'meta_description' => $category->meta_description,
            'canonical_url' => $category->canonical_url,
            'notes' => $category->notes,
            'products_count' => $category->products()->count(),
            'active_products_count' => $category->products()->where('is_active', true)->count(),
            'children_count' => $category->children()->count(),
        ]);
    }

    public function store(Request $request, ShopCategoryAdminService $svc)
    {
        $data = $this->validated($request);
        $check = $svc->validateCategory($data);
        if (! $check['ok']) {
            return back()->withErrors($check['errors'])->withInput();
        }

        $svc->createCategory($data, $request->user());

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, ShopCategory $category, ShopCategoryAdminService $svc)
    {
        $data = $this->validated($request, $category);
        $check = $svc->validateCategory($data, $category);
        if (! $check['ok']) {
            return back()->withErrors($check['errors'])->withInput();
        }

        $svc->updateCategory($category, $data, $request->user());

        return back()->with('success', 'Category updated.');
    }

    public function toggleActive(Request $request, ShopCategory $category, ShopCategoryAdminService $svc)
    {
        $svc->setActive($category, ! $category->is_active, $request->user());

        return back()->with('success', $category->is_active ? 'Category deactivated.' : 'Category activated.');
    }

    public function reorder(Request $request, ShopCategoryAdminService $svc)
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);
        $svc->reorder($data['ids'], $request->user());

        return response()->json(['ok' => true]);
    }

    public function destroy(ShopCategory $category, ShopCategoryAdminService $svc, Request $request)
    {
        $result = $svc->archive($category, $request->user());

        return back()->with($result['ok'] ? 'success' : 'error', $result['message'] ?? 'Category archived.');
    }

    private function validated(Request $request, ?ShopCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'exists:shop_categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'icon' => ['nullable', 'string', 'max:40'],
            'accent' => ['nullable', 'string', 'max:20'],
            'product_type' => ['nullable', 'string', 'max:30'],
            'sort' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'menu_visible' => ['nullable', 'boolean'],
            'default_fee_id' => ['nullable', 'exists:fees,id'],
            'restricted_countries' => ['nullable', 'array'],
            'restricted_countries.*' => ['string', 'size:2'],
            'navigation_badge' => ['nullable', 'in:New,Popular,Sale,Limited,Coming Soon'],
            'navigation_badge_style' => ['nullable', 'in:brand,emerald,amber,rose,slate'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['featured'] = $request->boolean('featured');
        $data['menu_visible'] = $request->boolean('menu_visible', true);
        $data['icon'] = ($data['icon'] ?? null) ?: 'sparkles';

        return $data;
    }

    private function buildTree($categories, ?int $parentId = null): array
    {
        return $categories->where('parent_id', $parentId)->map(fn ($c) => [
            'category' => $c,
            'children' => $this->buildTree($categories, $c->id),
        ])->values()->all();
    }
}
