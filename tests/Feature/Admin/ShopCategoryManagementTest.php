<?php

namespace Tests\Feature\Admin;

use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\Admin\ShopCategoryAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_non_admin_cannot_view_categories(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.shop.categories.index'))->assertForbidden();
    }

    public function test_admin_can_view_categories_with_nested_tree(): void
    {
        $parent = ShopCategory::factory()->create(['name' => 'Root Category']);
        ShopCategory::factory()->create(['name' => 'Nested Sub', 'parent_id' => $parent->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.shop.categories.index'))
            ->assertOk()
            ->assertSee('Root Category')
            ->assertSee('Nested Sub');
    }

    public function test_admin_can_create_a_subcategory(): void
    {
        $parent = ShopCategory::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.shop.categories.store'), ['name' => 'Sub One', 'parent_id' => $parent->id])
            ->assertRedirect();

        $this->assertDatabaseHas('shop_categories', ['name' => 'Sub One', 'parent_id' => $parent->id]);
    }

    public function test_category_cannot_become_its_own_parent(): void
    {
        $category = ShopCategory::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.shop.categories.update', $category), ['name' => $category->name, 'parent_id' => $category->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_category_cannot_be_moved_under_its_own_descendant(): void
    {
        $root = ShopCategory::factory()->create();
        $child = ShopCategory::factory()->create(['parent_id' => $root->id]);

        $this->actingAs($this->admin())
            ->put(route('admin.shop.categories.update', $root), ['name' => $root->name, 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_cannot_archive_a_category_with_products(): void
    {
        $category = ShopCategory::factory()->create();
        ShopProduct::factory()->for($category, 'category')->create();

        $response = $this->actingAs($this->admin())->delete(route('admin.shop.categories.destroy', $category));

        $response->assertRedirect();
        $this->assertDatabaseHas('shop_categories', ['id' => $category->id, 'is_active' => true]);
    }

    public function test_can_archive_an_empty_category(): void
    {
        $category = ShopCategory::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin())->delete(route('admin.shop.categories.destroy', $category));

        $this->assertDatabaseHas('shop_categories', ['id' => $category->id, 'is_active' => false]);
    }

    public function test_reorder_persists_new_sort_values(): void
    {
        $a = ShopCategory::factory()->create(['sort' => 0]);
        $b = ShopCategory::factory()->create(['sort' => 1]);

        app(ShopCategoryAdminService::class)->reorder([$b->id, $a->id], $this->admin());

        $this->assertSame(0, $b->fresh()->sort);
        $this->assertSame(1, $a->fresh()->sort);
    }

    public function test_toggle_active_flips_visibility(): void
    {
        $category = ShopCategory::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin())->post(route('admin.shop.categories.toggle-active', $category));

        $this->assertDatabaseHas('shop_categories', ['id' => $category->id, 'is_active' => false]);
    }
}
