<?php

namespace App\Services\Import\Connectors;

use App\Enums\ShopProductType;
use App\Models\ImportSource;
use App\Models\ProductImport;
use App\Models\ShopCategory;
use App\Models\ShopCategoryMapping;
use App\Models\ShopProduct;
use App\Models\ShopVariant;

/**
 * Shared row-to-product logic for every file-based connector (CSV, JSON, ...).
 * Subclasses only need to turn their file format into a stream of associative
 * arrays with keys: name, category, type, sku, price, cost_price, currency,
 * stock, description, image_url, brand. Only name/price are required.
 *
 * Every imported product is created as a draft — never auto-published — so an
 * admin reviews it before it can be sold.
 */
abstract class AbstractFileConnector extends AbstractConnector
{
    private const REQUIRED = ['name', 'price'];

    public function capabilities(): array
    {
        return ['test_connection', 'product_import'];
    }

    public function testConnection(ImportSource $source): array
    {
        return ['ok' => true, 'message' => 'File import needs no external connection — just a file.'];
    }

    /** @return iterable<array<string,mixed>> */
    abstract protected function rows(string $filePath): iterable;

    public function importProducts(ImportSource $source, ProductImport $run): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $log = [];
        $rowNumber = 1;

        if (! $run->file_path || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($run->file_path)) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 1, 'warnings' => [
                ['row' => 0, 'level' => 'error', 'message' => 'Import file not found.'],
            ]];
        }

        foreach ($this->rows($run->file_path) as $data) {
            $rowNumber++;

            $missing = array_filter(self::REQUIRED, fn ($f) => empty($data[$f] ?? null));
            if ($missing) {
                $failed++;
                $log[] = ['row' => $rowNumber, 'level' => 'error', 'message' => 'Missing required field(s): '.implode(', ', $missing)];

                continue;
            }

            $sku = trim((string) ($data['sku'] ?? ''));
            if ($sku !== '' && ShopVariant::where('sku', $sku)->exists()) {
                $skipped++;
                $log[] = ['row' => $rowNumber, 'level' => 'warning', 'message' => "Skipped — SKU '{$sku}' already exists."];

                continue;
            }

            try {
                $category = $this->resolveCategory($source, trim((string) ($data['category'] ?? 'Imported')));
                $type = in_array($data['type'] ?? null, array_column(ShopProductType::cases(), 'value'), true) ? $data['type'] : 'other';

                $product = ShopProduct::create([
                    'shop_category_id' => $category->id,
                    'name' => trim($data['name']),
                    'brand' => $data['brand'] ?? null,
                    'type' => $type,
                    'description' => $data['description'] ?? null,
                    'image_path' => $data['image_url'] ?? null,
                    'status' => 'draft',
                    'is_active' => false,
                    'source' => $source->code,
                    'import_source_id' => $source->id,
                    'product_import_id' => $run->id,
                ]);

                $product->variants()->create([
                    'name' => 'Standard',
                    'sku' => $sku ?: null,
                    'price' => (float) $data['price'],
                    'cost_price' => isset($data['cost_price']) && $data['cost_price'] !== '' ? (float) $data['cost_price'] : null,
                    'currency' => $data['currency'] ?? config('platform.base_currency', 'XAF'),
                    'stock' => isset($data['stock']) && $data['stock'] !== '' ? (int) $data['stock'] : null,
                    'is_active' => true,
                ]);

                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $log[] = ['row' => $rowNumber, 'level' => 'error', 'message' => 'Failed to create product: '.$e->getMessage()];
            }
        }

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'failed' => $failed, 'warnings' => $log];
    }

    private function resolveCategory(ImportSource $source, string $externalCategory): ShopCategory
    {
        $mapping = ShopCategoryMapping::firstOrCreate(
            ['import_source_id' => $source->id, 'external_category' => $externalCategory],
            ['status' => 'suggested'],
        );

        if ($mapping->shop_category_id) {
            return $mapping->category;
        }

        $category = ShopCategory::firstOrCreate(['name' => $externalCategory], ['is_active' => true]);
        $mapping->update(['shop_category_id' => $category->id, 'status' => 'confirmed', 'last_synced_at' => now()]);

        return $category;
    }
}
