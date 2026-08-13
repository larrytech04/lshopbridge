<?php

namespace Tests\Feature\Admin;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ShopProduct is deliberately NOT covered here: it uses HasSeoMetadata but
 * is not wired into SeoMetadataController::TYPES yet, because the public
 * product page doesn't call SeoService::forModel() for it (products are
 * excluded from SEO work until real Zendit/Airalo data lands). See the
 * docblock on SeoMetadataController.
 */
class SeoMetadataAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_the_agent_show_page_shows_the_seo_panel(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.agents.show', $agent));

        $response->assertOk();
        $response->assertSeeText('SEO');
    }

    public function test_an_admin_can_save_seo_fields_for_an_agent(): void
    {
        $agent = Agent::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.seo-metadata.update', ['type' => 'agent', 'id' => $agent->id]), [
            'meta_title' => 'Custom Agent Title',
            'meta_description' => 'A custom description.',
        ])->assertRedirect();

        $this->assertDatabaseHas('seo_metadata', [
            'seoable_type' => Agent::class,
            'seoable_id' => $agent->id,
            'meta_title' => 'Custom Agent Title',
        ]);
    }

    public function test_saving_again_updates_the_same_row_instead_of_creating_a_duplicate(): void
    {
        $agent = Agent::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.seo-metadata.update', ['type' => 'agent', 'id' => $agent->id]), ['meta_title' => 'First']);
        $this->actingAs($admin)->put(route('admin.seo-metadata.update', ['type' => 'agent', 'id' => $agent->id]), ['meta_title' => 'Second']);

        $this->assertSame(1, $agent->seoMetadata()->count());
        $this->assertSame('Second', $agent->seoMetadata->meta_title);
    }

    public function test_saving_records_who_reviewed_it_and_when(): void
    {
        $agent = Agent::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.seo-metadata.update', ['type' => 'agent', 'id' => $agent->id]), ['meta_title' => 'X']);

        $this->assertSame($admin->id, $agent->seoMetadata->seo_reviewed_by);
        $this->assertNotNull($agent->seoMetadata->last_seo_review_at);
    }

    public function test_the_sitemap_include_checkbox_defaults_to_true_when_checked(): void
    {
        $agent = Agent::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.seo-metadata.update', ['type' => 'agent', 'id' => $agent->id]), [
            'meta_title' => 'X',
            'sitemap_include' => '1',
        ]);

        $this->assertTrue((bool) $agent->seoMetadata->sitemap_include);
    }

    public function test_the_sitemap_include_checkbox_is_false_when_omitted_as_an_unchecked_checkbox(): void
    {
        $agent = Agent::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.seo-metadata.update', ['type' => 'agent', 'id' => $agent->id]), [
            'meta_title' => 'X',
            // sitemap_include intentionally omitted, as a real unchecked checkbox would.
        ]);

        $this->assertFalse((bool) $agent->seoMetadata->sitemap_include);
    }

    public function test_shop_product_is_not_yet_a_recognised_seo_metadata_type(): void
    {
        // Deliberately rejected until products are wired into SeoService::forModel().
        $this->actingAs($this->admin())
            ->put(route('admin.seo-metadata.update', ['type' => 'shop-product', 'id' => 1]))
            ->assertNotFound();
    }

    public function test_an_unknown_type_key_404s_instead_of_accepting_an_arbitrary_class(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.seo-metadata.update', ['type' => 'user', 'id' => 1]))
            ->assertNotFound();
    }

    public function test_a_regular_user_cannot_save_seo_metadata(): void
    {
        $agent = Agent::factory()->create();
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->put(route('admin.seo-metadata.update', ['type' => 'agent', 'id' => $agent->id]), ['meta_title' => 'Hijacked'])
            ->assertForbidden();

        $this->assertDatabaseMissing('seo_metadata', ['meta_title' => 'Hijacked']);
    }

    public function test_a_guest_cannot_save_seo_metadata(): void
    {
        $agent = Agent::factory()->create();

        $this->put(route('admin.seo-metadata.update', ['type' => 'agent', 'id' => $agent->id]), ['meta_title' => 'Hijacked'])
            ->assertRedirect(route('admin.login'));
    }

    public function test_an_invalid_robots_value_is_rejected(): void
    {
        $agent = Agent::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.seo-metadata.update', ['type' => 'agent', 'id' => $agent->id]), ['robots' => 'not-a-real-value'])
            ->assertSessionHasErrors('robots');
    }

    public function test_saving_records_an_audit_log_entry(): void
    {
        $agent = Agent::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.seo-metadata.update', ['type' => 'agent', 'id' => $agent->id]), ['meta_title' => 'X']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.seo_metadata.updated']);
    }

    public function test_saved_seo_metadata_is_actually_used_on_the_public_agent_page(): void
    {
        $agent = Agent::factory()->approved()->create();
        $agent->seoMetadata()->create(['meta_description' => 'A real custom description for this agent.']);

        $response = $this->get(route('agents.show', $agent));

        $response->assertSee('<meta name="description" content="A real custom description for this agent.">', false);
    }

    public function test_a_saved_canonical_override_is_actually_used_on_the_public_agent_page(): void
    {
        $agent = Agent::factory()->approved()->create();
        $agent->seoMetadata()->create(['canonical_override' => '/shipping-agents/some-other-listing']);

        $response = $this->get(route('agents.show', $agent));

        $response->assertSee('<link rel="canonical" href="https://localhost/shipping-agents/some-other-listing">', false);
    }

    public function test_a_saved_robots_override_is_respected_within_the_environment_safeguard(): void
    {
        // "noindex,follow" already starts with noindex, so the safeguard
        // (which only ever tightens toward noindex, never loosens) leaves
        // it exactly as saved — proving the override actually reaches the
        // page rather than being silently ignored.
        $agent = Agent::factory()->approved()->create();
        $agent->seoMetadata()->create(['robots' => 'noindex,follow']);

        $response = $this->get(route('agents.show', $agent));

        $response->assertSee('<meta name="robots" content="noindex,follow">', false);
    }
}
