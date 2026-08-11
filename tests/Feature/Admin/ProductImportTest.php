<?php

namespace Tests\Feature\Admin;

use App\Models\ImportSource;
use App\Models\ProductImport;
use App\Models\User;
use App\Services\Import\ProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_import_center_seeds_the_full_connector_catalog(): void
    {
        $this->actingAs($this->admin())->get(route('admin.shop.imports.index'))->assertOk();

        $this->assertGreaterThan(50, ImportSource::count());
        $this->assertDatabaseHas('import_sources', ['code' => 'shopify', 'status' => 'not_connected']);
        $this->assertDatabaseHas('import_sources', ['code' => 'csv', 'connector_class' => \App\Services\Import\Connectors\CsvConnector::class]);
    }

    public function test_placeholder_connector_honestly_reports_not_connected(): void
    {
        ImportSource::ensureSeeded();
        $shopify = ImportSource::where('code', 'shopify')->first();

        $result = app(ProductImportService::class)->resolveConnector($shopify)->testConnection($shopify);

        $this->assertFalse($result['ok']);
    }

    public function test_csv_import_creates_draft_products_with_variants(): void
    {
        Storage::fake('local');
        ImportSource::ensureSeeded();
        $csvSource = ImportSource::where('code', 'csv')->first();

        $csv = "name,category,type,sku,price,cost_price,currency,stock\n"
            ."Imported Gift Card,Imported Gifts,giftcard,IMP-001,5000,3000,XAF,25\n";
        $path = 'imports/test.csv';
        Storage::disk('local')->put($path, $csv);

        $import = ProductImport::create([
            'import_source_id' => $csvSource->id,
            'started_by' => $this->admin()->id,
            'file_path' => $path,
            'status' => 'preparing',
            'started_at' => now(),
        ]);

        app(ProductImportService::class)->run($import);

        $import->refresh();
        $this->assertSame(1, $import->products_created);
        $this->assertDatabaseHas('shop_products', ['name' => 'Imported Gift Card', 'status' => 'draft', 'source' => 'csv']);
        $this->assertDatabaseHas('shop_variants', ['sku' => 'IMP-001', 'price' => 5000, 'cost_price' => 3000]);
    }

    public function test_csv_import_skips_duplicate_skus(): void
    {
        Storage::fake('local');
        ImportSource::ensureSeeded();
        $csvSource = ImportSource::where('code', 'csv')->first();

        $category = \App\Models\ShopCategory::factory()->create();
        $existing = \App\Models\ShopProduct::factory()->for($category, 'category')->create();
        $existing->variants()->create(['name' => 'V', 'sku' => 'DUPE-1', 'price' => 1000, 'is_active' => true]);

        $csv = "name,price,sku\nSecond Product,2000,DUPE-1\n";
        $path = 'imports/dupe.csv';
        Storage::disk('local')->put($path, $csv);

        $import = ProductImport::create(['import_source_id' => $csvSource->id, 'file_path' => $path, 'status' => 'preparing', 'started_at' => now()]);
        app(ProductImportService::class)->run($import);

        $import->refresh();
        $this->assertSame(0, $import->products_created);
        $this->assertSame(1, $import->products_skipped);
    }

    public function test_rollback_removes_draft_products_but_keeps_ordered_ones(): void
    {
        Storage::fake('local');
        ImportSource::ensureSeeded();
        $csvSource = ImportSource::where('code', 'csv')->first();

        $csv = "name,price,sku\nRollback Me,1000,ROLL-1\nKeep Me,2000,ROLL-2\n";
        $path = 'imports/rollback.csv';
        Storage::disk('local')->put($path, $csv);

        $import = ProductImport::create(['import_source_id' => $csvSource->id, 'file_path' => $path, 'status' => 'preparing', 'started_at' => now()]);
        app(ProductImportService::class)->run($import);

        $keepProduct = \App\Models\ShopProduct::where('name', 'Keep Me')->first();
        $order = \App\Models\ShopOrder::factory()->create();
        \App\Models\ShopOrderItem::create([
            'shop_order_id' => $order->id, 'shop_product_id' => $keepProduct->id,
            'name' => $keepProduct->name, 'type' => 'giftcard', 'unit_price' => 2000, 'quantity' => 1, 'line_total' => 2000, 'status' => 'fulfilled',
        ]);

        $result = app(ProductImportService::class)->rollback($import->fresh(), $this->admin());

        $this->assertSame(1, $result['removed']);
        $this->assertSame(1, $result['kept']);
        $this->assertSoftDeleted('shop_products', ['name' => 'Rollback Me']);
        $this->assertDatabaseHas('shop_products', ['name' => 'Keep Me', 'deleted_at' => null]);
    }
}
