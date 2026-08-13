<?php

namespace Tests\Feature\Accessibility;

use App\Models\Agent;
use App\Models\Country;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6 accessibility baseline: a skip link + landmark on every layout,
 * and exactly one <h1> naming the page on every major public listing page
 * (regression coverage for a real gap found in this phase: shop/agents
 * index pages had no heading at all).
 */
class PageStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_layout_has_a_skip_link_and_main_landmark(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Skip to content');
        $response->assertSee('id="main-content"', false);
        $response->assertSee('href="#main-content"', false);
    }

    public function test_the_homepage_has_exactly_one_h1(): void
    {
        $response = $this->get(route('home'));

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_the_shop_index_has_exactly_one_h1(): void
    {
        $category = ShopCategory::factory()->create(['is_active' => true]);
        ShopProduct::factory()->create(['shop_category_id' => $category->id, 'is_active' => true]);

        $response = $this->get(route('shop.index'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        $response->assertSee('Digital Shop');
    }

    public function test_the_agents_index_has_exactly_one_h1(): void
    {
        Agent::factory()->approved()->create();

        $response = $this->get(route('agents.index'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        $response->assertSeeText('Shipping agents');
    }

    public function test_the_countries_index_has_exactly_one_h1(): void
    {
        Country::factory()->create(['is_active' => true]);

        $response = $this->get(route('countries.index'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_the_dashboard_layout_has_a_skip_link_and_main_landmark(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSee('Skip to content');
        $response->assertSee('id="main-content"', false);
    }
}
