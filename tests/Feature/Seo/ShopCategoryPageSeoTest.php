<?php

namespace Tests\Feature\Seo;

use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ShopCategory has its own native seo_title/meta_description/canonical_url
 * columns with a real admin form (admin/shop/categories.blade.php), but
 * shop/index.blade.php never read them, only ever falling back to a
 * generated title/description. Every subcategory of the same parent also
 * shared its parent's exact title, a real duplicate-title problem. Both
 * fixed in the same pass that built the Content Quality dashboard.
 */
class ShopCategoryPageSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_categorys_own_seo_title_and_description_are_used_when_set(): void
    {
        $category = ShopCategory::factory()->create([
            'is_active' => true,
            'seo_title' => 'Custom SEO Title For Category',
            'meta_description' => 'A custom category description.',
        ]);
        ShopProduct::factory()->create(['shop_category_id' => $category->id, 'is_active' => true]);

        $response = $this->get(route('shop.category', $category));

        $response->assertOk();
        $response->assertSee('Custom SEO Title For Category');
        $response->assertSee('<meta name="description" content="A custom category description.">', false);
    }

    public function test_a_category_without_a_custom_seo_title_falls_back_to_the_generated_default(): void
    {
        $category = ShopCategory::factory()->create(['is_active' => true, 'name' => 'Gift Cards', 'seo_title' => null]);
        ShopProduct::factory()->create(['shop_category_id' => $category->id, 'is_active' => true]);

        $response = $this->get(route('shop.category', $category));

        $response->assertOk();
        $response->assertSee('Gift Cards, Digital Shop');
    }

    public function test_a_subcategory_uses_its_own_seo_title_not_its_parents(): void
    {
        $parent = ShopCategory::factory()->create(['is_active' => true, 'name' => 'Gift Cards', 'seo_title' => 'Parent Title']);
        $child = ShopCategory::factory()->create(['is_active' => true, 'parent_id' => $parent->id, 'name' => 'Streaming Cards', 'seo_title' => 'Streaming Gift Cards Title']);
        ShopProduct::factory()->create(['shop_category_id' => $child->id, 'is_active' => true]);

        $response = $this->get(route('shop.category', $child));

        $response->assertOk();
        $response->assertSee('Streaming Gift Cards Title');
        $response->assertDontSee('Parent Title');
    }

    public function test_a_categorys_canonical_override_is_used_when_set(): void
    {
        $category = ShopCategory::factory()->create(['is_active' => true, 'canonical_url' => '/shop/c/some-other-slug']);
        ShopProduct::factory()->create(['shop_category_id' => $category->id, 'is_active' => true]);

        $response = $this->get(route('shop.category', $category));

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="https://localhost/shop/c/some-other-slug">', false);
    }
}
