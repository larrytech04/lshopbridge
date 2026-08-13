<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The command palette's static $pages list (in SearchController) has no
 * test coverage of its own elsewhere — a single unguarded entry pointing at
 * a route that no longer exists (route() is called on every entry, not
 * just matched ones) would 500 the ENTIRE search endpoint, not just hide
 * that one result. Caught exactly this when the withdrawal feature's
 * routes were removed but its command-palette entry was briefly left
 * behind — see withdrawal-feature-removed in project memory.
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_empty_query_returns_the_default_pages_without_erroring(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->getJson(route('search'))->assertOk();
    }

    public function test_a_query_searches_without_erroring(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->getJson(route('search', ['q' => 'wallet']))->assertOk();
    }

    public function test_every_static_page_entry_resolves_to_a_real_route(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        // Blank query returns every static page entry unfiltered — if any
        // of them named a dead route, building this response would throw.
        $response = $this->actingAs($user)->getJson(route('search'))->assertOk();

        $pages = collect($response->json('groups'))->firstWhere('key', 'pages')['items'] ?? [];
        $this->assertNotEmpty($pages);
        foreach ($pages as $result) {
            $this->assertArrayHasKey('url', $result);
        }
    }

    public function test_admins_see_admin_results_without_erroring(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);

        $this->actingAs($admin)->getJson(route('search'))->assertOk();
    }
}
