<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShopProductType;
use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\Supplier;
use App\Services\Admin\ShopProductAdminService;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShopProductController extends Controller
{
    public function index(Request $request, ShopProductAdminService $svc): View
    {
        $svc->applyDueSchedules();

        // withTrashed(): archived products are soft-deleted but must remain visible in their own tab.
        $query = ShopProduct::withTrashed()->with(['category', 'supplier', 'variants']);

        if ($cat = $request->query('category')) {
            $query->where('shop_category_id', $cat);
        }
        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('brand', 'like', "%{$q}%")
                ->orWhere('external_product_id', 'like', "%{$q}%")
                ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%{$q}%")->orWhere('barcode', 'like', "%{$q}%")));
        }
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }
        if ($supplier = $request->query('supplier')) {
            $query->where('supplier_id', $supplier);
        }
        if ($request->query('has_errors') === '1') {
            $query->where('provider_status', 'error');
        }

        $tab = $request->query('tab', 'all');
        $products = $query->latest()->get();

        $products = $products->filter(function (ShopProduct $p) use ($tab, $svc) {
            return match ($tab) {
                'active' => $p->status->value === 'active',
                'draft' => $p->status->value === 'draft',
                'scheduled' => $svc->computeDisplayStatus($p) === 'scheduled',
                'out_of_stock' => $svc->isOutOfStock($p),
                'low_stock' => $svc->isLowStock($p),
                'on_sale' => $svc->isOnSale($p),
                'disabled' => $p->status->value === 'disabled',
                'archived' => $p->status->value === 'archived',
                'sync_errors' => $svc->hasSyncErrors($p),
                default => true,
            };
        })->values();

        return view('admin.shop.products.index', [
            'products' => $products,
            'categories' => ShopCategory::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'productTypes' => ShopProductType::cases(),
            'summary' => $svc->summary(),
            'tabCounts' => $svc->tabCounts(),
            'activeTab' => $tab,
            'q' => $request->query('q', ''),
            'statusOf' => fn (ShopProduct $p) => $svc->computeDisplayStatus($p),
        ]);
    }

    public function rowDetail(ShopProduct $product, ShopProductAdminService $svc, AuditLogger $audit)
    {
        $product->load(['variants', 'category', 'supplier', 'importSource']);
        $audit->log('shop.product.viewed', "Viewed product {$product->name}", $product);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => $product->brand,
                'type' => $product->type->value,
                'type_label' => $product->type->label(),
                'shop_category_id' => $product->shop_category_id,
                'category' => $product->category?->name,
                'supplier' => $product->supplier?->name,
                'source' => $product->source,
                'summary' => $product->summary,
                'description' => $product->description,
                'redeem_instructions' => $product->redeem_instructions,
                'image_path' => $product->image_path,
                'status' => $product->status->value,
                'display_status' => $svc->computeDisplayStatus($product),
                'is_active' => $product->is_active,
                'is_featured' => $product->is_featured,
                'is_best_deal' => $product->is_best_deal,
                'sales_count' => $product->sales_count,
                'sort' => $product->sort,
                'admin_notes' => $product->admin_notes,
                'provider_status' => $product->provider_status,
                'last_synced_at' => $product->last_synced_at?->format('M j, Y g:ia'),
                'scheduled_publish_at' => $product->scheduled_publish_at?->toDateTimeString(),
                'updated_at' => $product->updated_at->format('M j, Y g:ia'),
            ],
            'variants' => $product->variants->map(fn ($v) => [
                'id' => $v->id, 'name' => $v->name, 'sku' => $v->sku, 'barcode' => $v->barcode,
                'price' => (float) $v->price, 'cost_price' => $v->cost_price !== null ? (float) $v->cost_price : null,
                'compare_at_price' => $v->compare_at_price !== null ? (float) $v->compare_at_price : null,
                'sale_price' => $v->sale_price !== null ? (float) $v->sale_price : null,
                'currency' => $v->currency, 'data_amount' => $v->data_amount, 'validity_days' => $v->validity_days,
                'denomination' => $v->denomination !== null ? (float) $v->denomination : null,
                'stock' => $v->stock, 'low_stock_threshold' => $v->low_stock_threshold,
                'is_active' => $v->is_active, 'profit_amount' => $v->profitAmount(), 'profit_margin' => $v->profitMarginPercent(),
            ]),
            'recent_orders' => \App\Models\ShopOrderItem::where('shop_product_id', $product->id)
                ->with('order')->latest()->take(5)->get()
                ->map(fn ($i) => ['reference' => $i->order->reference, 'quantity' => $i->quantity, 'total' => (float) $i->line_total, 'status' => $i->order->status->label(), 'at' => $i->created_at->format('M j, Y')]),
        ]);
    }

    public function create(Request $request): View
    {
        $requestedType = $request->query('type');
        $type = ShopProductType::tryFrom((string) $requestedType)?->value ?? 'giftcard';
        $product = new ShopProduct(['type' => $type]);

        return view('admin.shop.products.form', ['product' => $product, 'categories' => ShopCategory::orderBy('name')->get()]);
    }

    public function store(Request $request, ShopProductAdminService $svc)
    {
        $data = $this->validated($request);
        $check = $svc->validateProduct($data);
        if (! $check['ok']) {
            return back()->withErrors($check['errors'])->withInput();
        }

        $product = $svc->createProduct($data, $request->input('variants', []), $request->user());

        return redirect()->route('admin.shop.products.index')->with('success', "Product '{$product->name}' created.");
    }

    public function edit(ShopProduct $product): View
    {
        return view('admin.shop.products.form', ['product' => $product->load('variants'), 'categories' => ShopCategory::orderBy('name')->get()]);
    }

    public function update(Request $request, ShopProduct $product, ShopProductAdminService $svc)
    {
        $data = $this->validated($request);
        $check = $svc->validateProduct($data);
        if (! $check['ok']) {
            return back()->withErrors($check['errors'])->withInput();
        }

        $svc->updateProduct($product, $data, $request->input('variants', []), $request->user());

        return redirect()->route('admin.shop.products.index')->with('success', 'Product updated.');
    }

    public function toggleActive(Request $request, ShopProduct $product, ShopProductAdminService $svc)
    {
        $svc->setStatus($product, $product->status->value === 'active' ? 'disabled' : 'active', $request->user());

        return back()->with('success', 'Product status updated.');
    }

    public function schedule(Request $request, ShopProduct $product, ShopProductAdminService $svc)
    {
        $data = $request->validate(['publish_at' => ['required', 'date', 'after:now']]);
        $svc->schedulePublish($product, $data['publish_at'], $request->user());

        return back()->with('success', 'Publish scheduled.');
    }

    public function duplicate(ShopProduct $product, ShopProductAdminService $svc, Request $request)
    {
        $copy = $svc->duplicate($product, $request->user());

        return redirect()->route('admin.shop.products.edit', $copy)->with('success', 'Product duplicated as a draft.');
    }

    public function destroy(ShopProduct $product, ShopProductAdminService $svc, Request $request)
    {
        $svc->archive($product, $request->user());

        return back()->with('success', 'Product archived.');
    }

    public function bulkAction(Request $request, ShopProductAdminService $svc)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['activate', 'disable', 'archive', 'export'])],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $products = ShopProduct::withTrashed()->whereIn('id', $data['ids'])->get();
        foreach ($products as $product) {
            match ($data['action']) {
                'activate' => $svc->setStatus($product, 'active', $request->user()),
                'disable' => $svc->setStatus($product, 'disabled', $request->user()),
                'archive' => $svc->archive($product, $request->user()),
                default => null,
            };
        }

        return back()->with('success', ucfirst($data['action']).' applied to '.$products->count().' product(s).');
    }

    public function exportCsv(AuditLogger $audit): StreamedResponse
    {
        $products = ShopProduct::withTrashed()->with('variants', 'category')->get();
        $audit->log('shop.product.exported', 'Exported '.$products->count().' product(s) to CSV');

        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Category', 'Type', 'Source', 'Variants', 'Status', 'Units sold', 'Updated']);
            foreach ($products as $p) {
                fputcsv($out, [$p->name, $p->category?->name, $p->type->label(), $p->source, $p->variants->count(), $p->status->label(), $p->sales_count, $p->updated_at->toDateTimeString()]);
            }
            fclose($out);
        }, 'products-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'shop_category_id' => ['required', 'exists:shop_categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:80'],
            'type' => ['required', Rule::enum(ShopProductType::class)],
            'region' => ['nullable', 'string', 'max:80'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'redeem_instructions' => ['nullable', 'string'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'sort' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_best_deal' => ['nullable', 'boolean'],
        ]);

        foreach (['is_active', 'is_featured', 'is_best_deal'] as $b) {
            $data[$b] = $request->boolean($b);
        }
        $data['status'] = $data['is_active'] ? 'active' : 'draft';

        return $data;
    }
}
