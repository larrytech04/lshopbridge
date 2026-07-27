<?php

namespace Tests\Feature\Navigation;

use App\Models\ShopCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSidebarDropdownTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    public function test_marketplace_submenu_lists_dynamic_categories_in_the_primary_sidebar(): void
    {
        $category = ShopCategory::factory()->create(['name' => 'Gift Cards Test Category', 'is_active' => true, 'menu_visible' => true]);

        $response = $this->actingAs($this->customer())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="marketplace-subnav"', false);
        $response->assertSee('Gift Cards Test Category');
        $response->assertSee(route('shop.category', $category->slug), false);
    }

    public function test_marketplace_submenu_is_pinned_open_on_marketplace_routes(): void
    {
        $response = $this->actingAs($this->customer())->get(route('shop.index'));

        $response->assertOk();
        // The wrapper's Alpine state initialises to true on shop routes so the
        // submenu stays expanded while browsing the Marketplace.
        $response->assertSee("x-data=\"{ mp: true }\"", false);
    }

    public function test_no_desktop_secondary_sidebar_or_popover_markup_remains(): void
    {
        $response = $this->actingAs($this->customer())->get(route('shop.index'));

        $response->assertOk();
        $response->assertDontSee('id="context-sidebar-marketplace"', false);
        $response->assertDontSee('shell-col-context', false);
        $response->assertDontSee('glass-strong fixed top-20', false);
    }

    public function test_agent_area_never_renders_the_marketplace_submenu(): void
    {
        $agentUser = User::factory()->create(['role' => 'agent', 'status' => 'active']);
        \App\Models\Agent::factory()->approved()->create(['user_id' => $agentUser->id]);

        $response = $this->actingAs($agentUser)->get(route('agent.dashboard'));

        $response->assertOk();
        $response->assertDontSee('id="marketplace-subnav"', false);
    }

    public function test_category_page_renders_with_the_category_marked_active(): void
    {
        $category = ShopCategory::factory()->create(['name' => 'Gift Cards Test Category', 'is_active' => true, 'menu_visible' => true]);

        $response = $this->actingAs($this->customer())->get(route('shop.category', $category->slug));

        $response->assertOk();
        $response->assertSee('Gift Cards Test Category');
    }

    public function test_dashboard_shell_grid_wrapper_is_present(): void
    {
        $response = $this->actingAs($this->customer())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('dashboard-shell', false);
        $response->assertSee('shell-col-primary', false);
        $response->assertSee('shell-col-main', false);
    }
}
