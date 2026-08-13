<?php

namespace Tests\Feature\Admin;

use App\Models\Agent;
use App\Models\Guide;
use App\Models\Page;
use App\Models\ShopCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoContentQualityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_a_guest_cannot_view_it(): void
    {
        $this->get(route('admin.seo.content-quality'))->assertRedirect(route('admin.login'));
    }

    public function test_a_non_admin_cannot_view_it(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.seo.content-quality'))->assertForbidden();
    }

    public function test_it_lists_a_published_page_guide_category_and_agent(): void
    {
        Page::factory()->create(['title' => 'Test Legal Page', 'is_published' => true]);
        Guide::factory()->create(['title' => 'Test Guide Title', 'is_published' => true]);
        ShopCategory::factory()->create(['name' => 'Test Category Name', 'is_active' => true]);
        Agent::factory()->approved()->create(['business_name' => 'Test Agent Business']);

        $response = $this->actingAs($this->admin())->get(route('admin.seo.content-quality'));

        $response->assertOk();
        $response->assertSee('Test Legal Page');
        $response->assertSee('Test Guide Title');
        $response->assertSee('Test Category Name');
        $response->assertSee('Test Agent Business');
    }

    public function test_it_excludes_unpublished_and_unapproved_records(): void
    {
        Page::factory()->create(['title' => 'Hidden Draft Page', 'is_published' => false]);
        Guide::factory()->create(['title' => 'Hidden Draft Guide', 'is_published' => false]);
        ShopCategory::factory()->create(['name' => 'Hidden Category', 'is_active' => false]);
        Agent::factory()->create(['business_name' => 'Pending Agent', 'status' => 'pending']);

        $response = $this->actingAs($this->admin())->get(route('admin.seo.content-quality'));

        $response->assertOk();
        $response->assertDontSee('Hidden Draft Page');
        $response->assertDontSee('Hidden Draft Guide');
        $response->assertDontSee('Hidden Category');
        $response->assertDontSee('Pending Agent');
    }

    public function test_it_flags_a_missing_meta_description(): void
    {
        Page::factory()->create(['title' => 'No Description Page', 'is_published' => true, 'meta_description' => null]);

        $response = $this->actingAs($this->admin())->get(route('admin.seo.content-quality'));

        $response->assertOk();
        $response->assertSee('Missing meta description');
    }

    public function test_it_flags_two_records_sharing_the_same_effective_title(): void
    {
        Guide::factory()->create(['title' => 'Shared Title Here', 'meta_title' => null, 'is_published' => true]);
        Page::factory()->create(['title' => 'Shared Title Here', 'meta_title' => null, 'is_published' => true]);

        $response = $this->actingAs($this->admin())->get(route('admin.seo.content-quality'));

        $response->assertOk();
        $response->assertSee('Duplicate title (shared with another page)');
    }

    public function test_it_does_not_flag_a_unique_title_as_a_duplicate(): void
    {
        Page::factory()->create(['title' => 'Totally Unique Title', 'meta_title' => null, 'is_published' => true]);

        $response = $this->actingAs($this->admin())->get(route('admin.seo.content-quality'));

        $response->assertOk();
        // The summary card is always labeled "Duplicate titles" regardless of
        // count, so assert against the specific per-row warning text instead.
        $response->assertDontSee('Duplicate title (shared with another page)');
    }

    public function test_an_agent_marked_noindex_is_flagged_and_counted(): void
    {
        $agent = Agent::factory()->approved()->create(['business_name' => 'Noindexed Agent']);
        $agent->seoMetadata()->create(['robots' => 'noindex,follow']);

        $response = $this->actingAs($this->admin())->get(route('admin.seo.content-quality'));

        $response->assertOk();
        $response->assertSee('Marked noindex');
    }

    public function test_summary_counts_reflect_real_data(): void
    {
        Page::factory()->create(['title' => 'A Page', 'is_published' => true, 'meta_description' => null]);

        $response = $this->actingAs($this->admin())->get(route('admin.seo.content-quality'));

        $response->assertOk();
        $response->assertViewHas('summary', function ($summary) {
            return $summary['total'] >= 1 && $summary['missing_description'] >= 1;
        });
    }
}
