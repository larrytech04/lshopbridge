<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShopProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = ShopProduct::with('category')->withCount('variants');
        if ($cat = $request->query('category')) {
            $query->where('shop_category_id', $cat);
        }
        if ($q = $request->query('q')) {
            $query->where('name', 'like', "%{$q}%");
        }

        return view('admin.shop.products.index', [
            'products' => $query->latest()->paginate(20)->withQueryString(),
            'categories' => ShopCategory::orderBy('name')->get(),
            'filters' => $request->only('category', 'q'),
        ]);
    }

    public function create(): View
    {
        return view('admin.shop.products.form', ['product' => new ShopProduct, 'categories' => ShopCategory::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $product = ShopProduct::create($this->validated($request));
        $this->syncVariants($product, $request);

        return redirect()->route('admin.shop.products.index')->with('success', 'Product created.');
    }

    public function edit(ShopProduct $product): View
    {
        return view('admin.shop.products.form', ['product' => $product->load('variants'), 'categories' => ShopCategory::orderBy('name')->get()]);
    }

    public function update(Request $request, ShopProduct $product)
    {
        $product->update($this->validated($request));
        $this->syncVariants($product, $request);

        return redirect()->route('admin.shop.products.index')->with('success', 'Product updated.');
    }

    public function destroy(ShopProduct $product)
    {
        $product->delete();

        return back()->with('success', 'Product removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'shop_category_id' => ['required', 'exists:shop_categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:80'],
            'type' => ['required', 'in:giftcard,esim,vpn,data,gaming,streaming,software,other'],
            'region' => ['nullable', 'string', 'max:80'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'redeem_instructions' => ['nullable', 'string'],
            'sort' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_best_deal' => ['nullable', 'boolean'],
        ]);

        foreach (['is_active', 'is_featured', 'is_best_deal'] as $b) {
            $data[$b] = $request->boolean($b);
        }

        return $data;
    }

    /** Upsert the variants[] array and delete any removed rows. */
    private function syncVariants(ShopProduct $product, Request $request): void
    {
        $variants = $request->input('variants', []);
        $keepIds = [];

        foreach ($variants as $v) {
            if (empty($v['name']) || ! isset($v['price'])) {
                continue;
            }
            $row = $product->variants()->updateOrCreate(
                ['id' => $v['id'] ?? null],
                [
                    'name' => $v['name'],
                    'price' => (float) $v['price'],
                    'compare_at_price' => $v['compare_at_price'] ?? null,
                    'currency' => $v['currency'] ?? config('platform.base_currency'),
                    'data_amount' => $v['data_amount'] ?? null,
                    'validity_days' => $v['validity_days'] ?? null,
                    'denomination' => $v['denomination'] ?? null,
                    'stock' => ($v['stock'] ?? '') === '' ? null : (int) $v['stock'],
                    'is_active' => (bool) ($v['is_active'] ?? true),
                ],
            );
            $keepIds[] = $row->id;
        }

        $product->variants()->whereNotIn('id', $keepIds ?: [0])->delete();
    }
}
