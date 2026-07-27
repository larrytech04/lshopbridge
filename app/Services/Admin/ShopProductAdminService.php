<?php

namespace App\Services\Admin;

use App\Models\ShopProduct;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class ShopProductAdminService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @return array{ok:bool, errors:array<string,string>}
     */
    public function validateProduct(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Product name is required.';
        }
        if (empty($data['shop_category_id'])) {
            $errors['shop_category_id'] = 'Category is required.';
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    public function createProduct(array $data, array $variants, User $admin): ShopProduct
    {
        return DB::transaction(function () use ($data, $variants, $admin) {
            $product = ShopProduct::create($data + ['updated_by' => $admin->id]);
            $this->syncVariants($product, $variants);
            $this->audit->log('shop.product.created', "Created product {$product->name}", $product, $data);

            return $product->fresh(['variants']);
        });
    }

    public function updateProduct(ShopProduct $product, array $data, array $variants, User $admin): ShopProduct
    {
        return DB::transaction(function () use ($product, $data, $variants, $admin) {
            $before = $product->only(['name', 'status', 'is_active']);
            $product->update($data + ['updated_by' => $admin->id]);
            $this->syncVariants($product, $variants);
            $this->audit->log('shop.product.updated', "Updated product {$product->name}", $product, ['before' => $before, 'after' => $data]);

            return $product->fresh(['variants']);
        });
    }

    private const NULLABLE_NUMERIC_VARIANT_FIELDS = ['cost_price', 'compare_at_price', 'stock', 'low_stock_threshold', 'validity_days', 'denomination'];

    private function syncVariants(ShopProduct $product, array $variants): void
    {
        $keepIds = [];

        foreach ($variants as $row) {
            if (empty($row['name']) || ! isset($row['price']) || $row['price'] === '') {
                continue;
            }

            $data = collect($row)->except('id')->all();
            foreach (self::NULLABLE_NUMERIC_VARIANT_FIELDS as $field) {
                if (array_key_exists($field, $data) && ($data[$field] === '' || $data[$field] === null)) {
                    $data[$field] = null;
                }
            }
            // Checkboxes are omitted from the request entirely when unchecked.
            $data['is_active'] = ! empty($row['is_active']);

            $variant = $product->variants()->updateOrCreate(['id' => $row['id'] ?? null], $data);
            $keepIds[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keepIds ?: [0])->delete();
    }

    public function setStatus(ShopProduct $product, string $status, User $admin): ShopProduct
    {
        $product->update([
            'status' => $status,
            'is_active' => $status === 'active',
            'updated_by' => $admin->id,
        ]);
        $this->audit->log('shop.product.status_changed', "Set {$product->name} to {$status}", $product, [], $admin->id);

        return $product->fresh();
    }

    public function schedulePublish(ShopProduct $product, string $publishAt, User $admin): ShopProduct
    {
        $product->update(['status' => 'draft', 'is_active' => false, 'scheduled_publish_at' => $publishAt, 'updated_by' => $admin->id]);
        $this->audit->log('shop.product.scheduled', "Scheduled {$product->name} to publish at {$publishAt}", $product, [], $admin->id);

        return $product->fresh();
    }

    /** Promotes due-to-publish drafts. Runs opportunistically on page load — no queue/cron for this exists. */
    public function applyDueSchedules(): int
    {
        $due = ShopProduct::where('status', 'draft')
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now())
            ->get();

        foreach ($due as $product) {
            $product->update(['status' => 'active', 'is_active' => true, 'scheduled_publish_at' => null]);
            $this->audit->log('shop.product.published', "Auto-published scheduled product {$product->name}", $product);
        }

        return $due->count();
    }

    public function duplicate(ShopProduct $product, User $admin): ShopProduct
    {
        return DB::transaction(function () use ($product, $admin) {
            $copy = ShopProduct::create(array_merge($product->only([
                'shop_category_id', 'brand', 'type', 'region', 'summary', 'description',
                'redeem_instructions', 'image_path', 'logo_path', 'meta',
            ]), [
                'name' => $product->name.' (copy)',
                'status' => 'draft',
                'is_active' => false,
                'is_featured' => false,
                'is_best_deal' => false,
                'source' => 'native',
                'updated_by' => $admin->id,
            ]));

            foreach ($product->variants as $variant) {
                $copy->variants()->create($variant->only([
                    'name', 'sku', 'price', 'cost_price', 'compare_at_price', 'currency', 'data_amount',
                    'validity_days', 'denomination', 'stock', 'low_stock_threshold', 'barcode', 'sort',
                ]));
            }

            $this->audit->log('shop.product.duplicated', "Duplicated {$product->name} as {$copy->name}", $copy, [], $admin->id);

            return $copy->fresh(['variants']);
        });
    }

    /** Archive-not-delete: soft-deletes so products used in completed orders are never actually removed. */
    public function archive(ShopProduct $product, User $admin): void
    {
        $product->update(['status' => 'archived', 'is_active' => false, 'updated_by' => $admin->id]);
        $this->audit->log('shop.product.archived', "Archived product {$product->name}", $product, [], $admin->id);
        $product->delete();
    }

    public function computeDisplayStatus(ShopProduct $product): string
    {
        if ($product->status->value === 'draft' && $product->scheduled_publish_at && $product->scheduled_publish_at->isFuture()) {
            return 'scheduled';
        }

        return $product->status->value;
    }

    public function isOutOfStock(ShopProduct $product): bool
    {
        $variants = $product->variants;
        if ($variants->isEmpty()) {
            return false;
        }

        return $variants->every(fn ($v) => $v->stock !== null && $v->stock <= 0);
    }

    public function isLowStock(ShopProduct $product): bool
    {
        return $product->variants->contains(fn ($v) => $v->isLowStock()) && ! $this->isOutOfStock($product);
    }

    public function isOnSale(ShopProduct $product): bool
    {
        return $product->variants->contains(fn ($v) => $v->saleIsActive());
    }

    public function hasSyncErrors(ShopProduct $product): bool
    {
        return $product->provider_status === 'error';
    }

    public function summary(): array
    {
        // withTrashed(): archived products are soft-deleted but must still count/show in their own tab.
        $products = ShopProduct::withTrashed()->with('variants')->get();
        $active = $products->filter(fn ($p) => $p->status->value === 'active');

        return [
            'total' => $products->count(),
            'active' => $active->count(),
            'draft' => $products->filter(fn ($p) => $p->status->value === 'draft')->count(),
            'out_of_stock' => $products->filter(fn ($p) => $this->isOutOfStock($p))->count(),
            'low_stock' => $products->filter(fn ($p) => $this->isLowStock($p))->count(),
            'on_sale' => $products->filter(fn ($p) => $this->isOnSale($p))->count(),
            'imported' => $products->filter(fn ($p) => $p->isImported())->count(),
            'provider_synced' => $products->filter(fn ($p) => $p->last_synced_at !== null)->count(),
            'with_errors' => $products->filter(fn ($p) => $this->hasSyncErrors($p))->count(),
            'units_sold' => (int) $products->sum('sales_count'),
        ];
    }

    /** @return array<string,int> tab key => count */
    public function tabCounts(): array
    {
        $products = ShopProduct::withTrashed()->with('variants')->get();

        return [
            'all' => $products->count(),
            'active' => $products->filter(fn ($p) => $p->status->value === 'active')->count(),
            'draft' => $products->filter(fn ($p) => $p->status->value === 'draft')->count(),
            'scheduled' => $products->filter(fn ($p) => $this->computeDisplayStatus($p) === 'scheduled')->count(),
            'out_of_stock' => $products->filter(fn ($p) => $this->isOutOfStock($p))->count(),
            'low_stock' => $products->filter(fn ($p) => $this->isLowStock($p))->count(),
            'on_sale' => $products->filter(fn ($p) => $this->isOnSale($p))->count(),
            'disabled' => $products->filter(fn ($p) => $p->status->value === 'disabled')->count(),
            'archived' => $products->filter(fn ($p) => $p->status->value === 'archived')->count(),
            'sync_errors' => $products->filter(fn ($p) => $this->hasSyncErrors($p))->count(),
        ];
    }
}
