<?php

namespace Tests\Feature\Navigation;

use App\Models\ShopCategory;
use App\Services\Shop\CategoryNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryNavigationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CategoryNavigationService
    {
        return app(CategoryNavigationService::class);
    }

    public function test_inactive_categories_are_excluded(): void
    {
        ShopCategory::factory()->create(['is_active' => false, 'name' => 'Hidden']);
        ShopCategory::factory()->create(['is_active' => true, 'name' => 'Visible']);

        $names = $this->service()->visibleTopLevel()->pluck('name');

        $this->assertNotContains('Hidden', $names);
        $this->assertContains('Visible', $names);
    }

    public function test_categories_hidden_from_menu_are_excluded(): void
    {
        ShopCategory::factory()->create(['menu_visible' => false, 'name' => 'Not in menu']);

        $names = $this->service()->visibleTopLevel()->pluck('name');

        $this->assertNotContains('Not in menu', $names);
    }

    public function test_category_restricted_to_other_countries_is_excluded_for_visitor(): void
    {
        ShopCategory::factory()->create(['name' => 'US only', 'restricted_countries' => ['CM']]);

        $names = $this->service()->visibleTopLevel('CM')->pluck('name');

        $this->assertNotContains('US only', $names);
    }

    public function test_category_not_restricted_for_visitor_country_is_included(): void
    {
        ShopCategory::factory()->create(['name' => 'US only', 'restricted_countries' => ['FR']]);

        $names = $this->service()->visibleTopLevel('CM')->pluck('name');

        $this->assertContains('US only', $names);
    }

    public function test_category_with_future_availability_window_is_excluded(): void
    {
        ShopCategory::factory()->create(['name' => 'Coming soon', 'available_from' => now()->addDay()]);

        $names = $this->service()->visibleTopLevel()->pluck('name');

        $this->assertNotContains('Coming soon', $names);
    }

    public function test_category_with_expired_availability_window_is_excluded(): void
    {
        ShopCategory::factory()->create(['name' => 'Expired', 'available_until' => now()->subDay()]);

        $names = $this->service()->visibleTopLevel()->pluck('name');

        $this->assertNotContains('Expired', $names);
    }

    public function test_featured_returns_only_featured_categories_up_to_limit(): void
    {
        ShopCategory::factory()->count(3)->create(['featured' => true]);
        ShopCategory::factory()->create(['featured' => false, 'name' => 'Not featured']);

        $featured = $this->service()->featured(null, 2);

        $this->assertCount(2, $featured);
        $this->assertTrue($featured->every(fn (ShopCategory $c) => $c->featured));
    }

    public function test_child_categories_inherit_country_restriction_filtering(): void
    {
        $parent = ShopCategory::factory()->create(['name' => 'Parent']);
        ShopCategory::factory()->create(['name' => 'Child hidden', 'parent_id' => $parent->id, 'restricted_countries' => ['CM']]);
        ShopCategory::factory()->create(['name' => 'Child visible', 'parent_id' => $parent->id]);

        $result = $this->service()->visibleTopLevel('CM')->firstWhere('id', $parent->id);

        $this->assertNotNull($result);
        $childNames = $result->children->pluck('name');
        $this->assertNotContains('Child hidden', $childNames);
        $this->assertContains('Child visible', $childNames);
    }
}
