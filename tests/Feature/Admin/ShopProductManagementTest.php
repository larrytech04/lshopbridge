<?php

namespace Tests\Feature\Admin;

use App\Models\ShopCategory;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\Admin\ShopProductAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function category(): ShopCategory
    {
        return ShopCategory::factory()->create();
    }

    // --------------------------------------------------------------- access

    public function test_non_admin_cannot_view_products(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.shop.products.index'))->assertForbidden();
    }

    public function test_admin_can_view_products(): void
    {
        ShopProduct::factory()->for($this->category(), 'category')->create();

        $this->actingAs($this->admin())
            ->get(route('admin.shop.products.index'))
            ->assertOk()
            ->assertSee('Products');
    }

    // -------------------------------------------------------------- create/update

    public function test_admin_can_create_a_product_with_variants(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin())
            ->post(route('admin.shop.products.store'), [
                'shop_category_id' => $category->id,
                'name' => 'Test eSIM',
                'type' => 'esim',
                'is_active' => '1',
                'variants' => [
                    ['name' => 'Standard', 'price' => 5000, 'cost_price' => 3000, 'currency' => 'XAF', 'stock' => 10],
                ],
            ])
            ->assertRedirect(route('admin.shop.products.index'));

        $this->assertDatabaseHas('shop_products', ['name' => 'Test eSIM', 'status' => 'active']);
        $this->assertDatabaseHas('shop_variants', ['name' => 'Standard', 'price' => 5000, 'cost_price' => 3000]);
    }

    public function test_creating_inactive_product_saves_as_draft(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin())
            ->post(route('admin.shop.products.store'), [
                'shop_category_id' => $category->id,
                'name' => 'Draft Product',
                'type' => 'giftcard',
                'variants' => [['name' => 'Std', 'price' => 1000]],
            ]);

        $this->assertDatabaseHas('shop_products', ['name' => 'Draft Product', 'status' => 'draft', 'is_active' => false]);
    }

    public function test_updating_variants_removes_deleted_rows_and_normalizes_blank_numeric_fields(): void
    {
        $product = ShopProduct::factory()->for($this->category(), 'category')->create();
        $variant = $product->variants()->create(['name' => 'Old', 'price' => 1000, 'is_active' => true]);

        $this->actingAs($this->admin())
            ->put(route('admin.shop.products.update', $product), [
                'shop_category_id' => $product->shop_category_id,
                'name' => $product->name,
                'type' => 'giftcard',
                'is_active' => '1',
                'variants' => [
                    ['id' => $variant->id, 'name' => 'Updated', 'price' => 2000, 'cost_price' => '', 'stock' => ''],
                ],
            ]);

        $this->assertDatabaseHas('shop_variants', ['id' => $variant->id, 'name' => 'Updated', 'price' => 2000, 'cost_price' => null, 'stock' => null]);
    }

    public function test_unchecking_variant_active_checkbox_actually_deactivates_it(): void
    {
        $product = ShopProduct::factory()->for($this->category(), 'category')->create();
        $variant = $product->variants()->create(['name' => 'V', 'price' => 1000, 'is_active' => true]);

        $this->actingAs($this->admin())
            ->put(route('admin.shop.products.update', $product), [
                'shop_category_id' => $product->shop_category_id,
                'name' => $product->name,
                'type' => 'giftcard',
                'is_active' => '1',
                'variants' => [
                    ['id' => $variant->id, 'name' => 'V', 'price' => 1000], // is_active omitted = unchecked
                ],
            ]);

        $this->assertDatabaseHas('shop_variants', ['id' => $variant->id, 'is_active' => false]);
    }

    // ---------------------------------------------------------------- status

    public function test_toggle_active_switches_between_active_and_disabled(): void
    {
        $product = ShopProduct::factory()->for($this->category(), 'category')->create(['status' => 'active', 'is_active' => true]);

        $this->actingAs($this->admin())->post(route('admin.shop.products.toggle-active', $product));

        $this->assertDatabaseHas('shop_products', ['id' => $product->id, 'status' => 'disabled', 'is_active' => false]);
    }

    public function test_schedule_publish_creates_scheduled_display_status(): void
    {
        $product = ShopProduct::factory()->for($this->category(), 'category')->create(['status' => 'draft', 'is_active' => false]);
        $svc = app(ShopProductAdminService::class);

        $svc->schedulePublish($product, now()->addDay()->toDateTimeString(), $this->admin());

        $this->assertSame('scheduled', $svc->computeDisplayStatus($product->fresh()));
    }

    public function test_due_schedule_is_applied_on_read(): void
    {
        $product = ShopProduct::factory()->for($this->category(), 'category')->create(['status' => 'draft', 'is_active' => false, 'scheduled_publish_at' => now()->subMinute()]);

        app(ShopProductAdminService::class)->applyDueSchedules();

        $this->assertDatabaseHas('shop_products', ['id' => $product->id, 'status' => 'active', 'is_active' => true]);
    }

    // ---------------------------------------------------------------- archive

    public function test_archiving_a_product_soft_deletes_but_keeps_it_queryable_with_trashed(): void
    {
        $product = ShopProduct::factory()->for($this->category(), 'category')->create();

        $this->actingAs($this->admin())->delete(route('admin.shop.products.destroy', $product));

        $this->assertSoftDeleted('shop_products', ['id' => $product->id]);
        $this->assertDatabaseHas('shop_products', ['id' => $product->id, 'status' => 'archived']);
        $this->assertNotNull(ShopProduct::withTrashed()->find($product->id));
    }

    public function test_archiving_a_product_with_completed_orders_preserves_the_order_snapshot(): void
    {
        $product = ShopProduct::factory()->for($this->category(), 'category')->create();
        $variant = $product->variants()->create(['name' => 'V', 'price' => 1000, 'is_active' => true]);
        $order = ShopOrder::factory()->create();
        ShopOrderItem::create([
            'shop_order_id' => $order->id, 'shop_product_id' => $product->id, 'shop_variant_id' => $variant->id,
            'name' => $product->name, 'type' => 'giftcard', 'unit_price' => 1000, 'quantity' => 1, 'line_total' => 1000, 'status' => 'fulfilled',
        ]);

        $this->actingAs($this->admin())->delete(route('admin.shop.products.destroy', $product));

        $this->assertSoftDeleted('shop_products', ['id' => $product->id]);
        $this->assertDatabaseHas('shop_order_items', ['shop_product_id' => $product->id, 'name' => $product->name]);
    }

    // -------------------------------------------------------------- duplicate

    public function test_duplicate_creates_a_draft_copy_with_its_own_variants(): void
    {
        $product = ShopProduct::factory()->for($this->category(), 'category')->create(['is_active' => true, 'status' => 'active']);
        $product->variants()->create(['name' => 'Std', 'price' => 1500, 'is_active' => true]);

        $response = $this->actingAs($this->admin())->post(route('admin.shop.products.duplicate', $product));
        $response->assertRedirect();

        $this->assertDatabaseHas('shop_products', ['name' => $product->name.' (copy)', 'status' => 'draft', 'is_active' => false]);
        $copy = ShopProduct::where('name', $product->name.' (copy)')->first();
        $this->assertSame(1, $copy->variants()->count());
    }

    // ----------------------------------------------------------------- bulk

    public function test_bulk_archive_applies_to_all_selected_products(): void
    {
        $a = ShopProduct::factory()->for($this->category(), 'category')->create();
        $b = ShopProduct::factory()->for($this->category(), 'category')->create();

        $this->actingAs($this->admin())
            ->post(route('admin.shop.products.bulk-action'), ['action' => 'archive', 'ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertSoftDeleted('shop_products', ['id' => $a->id]);
        $this->assertSoftDeleted('shop_products', ['id' => $b->id]);
    }

    // -------------------------------------------------------------- tabs / summary

    public function test_tab_filters_out_of_stock_products(): void
    {
        $product = ShopProduct::factory()->for($this->category(), 'category')->create();
        $product->variants()->create(['name' => 'V', 'price' => 1000, 'stock' => 0, 'is_active' => true]);
        $other = ShopProduct::factory()->for($this->category(), 'category')->create(['name' => 'In stock product']);
        $other->variants()->create(['name' => 'V', 'price' => 1000, 'stock' => 50, 'is_active' => true]);

        $response = $this->actingAs($this->admin())->get(route('admin.shop.products.index', ['tab' => 'out_of_stock']));

        $response->assertSee($product->name)->assertDontSee('In stock product');
    }

    public function test_summary_counts_reflect_real_database_state(): void
    {
        ShopProduct::factory()->for($this->category(), 'category')->count(2)->create(['status' => 'active', 'is_active' => true]);
        ShopProduct::factory()->for($this->category(), 'category')->create(['status' => 'draft', 'is_active' => false]);

        $summary = app(ShopProductAdminService::class)->summary();

        $this->assertSame(3, $summary['total']);
        $this->assertSame(2, $summary['active']);
        $this->assertSame(1, $summary['draft']);
    }
}
