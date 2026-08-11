<?php

namespace Tests\Feature\Admin;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_admin_can_view_and_create_suppliers(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.shop.suppliers.index'))
            ->assertOk()
            ->assertSee('Suppliers');

        $this->actingAs($this->admin())
            ->post(route('admin.shop.suppliers.store'), ['name' => 'CJ Dropshipping', 'code' => 'cj-dropship'])
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['name' => 'CJ Dropshipping', 'code' => 'cj-dropship']);
    }

    public function test_deleting_a_supplier_with_products_deactivates_instead_of_deleting(): void
    {
        $supplier = Supplier::factory()->create();
        \App\Models\ShopProduct::factory()->for(\App\Models\ShopCategory::factory()->create(), 'category')->create(['supplier_id' => $supplier->id]);

        $this->actingAs($this->admin())->delete(route('admin.shop.suppliers.destroy', $supplier));

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'is_active' => false]);
    }

    public function test_deleting_an_unused_supplier_removes_it(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.shop.suppliers.destroy', $supplier));

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
