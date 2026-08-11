<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use App\Models\User;
use App\Services\Content\LegalContentRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_hub_lists_a_published_legal_document_under_its_category(): void
    {
        Page::factory()->create([
            'slug' => 'sample-policy', 'title' => 'Sample Policy', 'type' => 'legal',
            'category' => 'money', 'is_published' => true,
        ]);

        $this->get(route('legal.index'))
            ->assertOk()
            ->assertSee('Money & Payments')
            ->assertSee('Sample Policy');
    }

    public function test_legal_hub_does_not_list_an_unpublished_document(): void
    {
        Page::factory()->create(['slug' => 'draft-policy', 'title' => 'Draft Policy', 'type' => 'legal', 'is_published' => false]);

        $this->get(route('legal.index'))->assertOk()->assertDontSee('Draft Policy');
    }

    public function test_legal_hub_does_not_list_an_info_type_page(): void
    {
        Page::factory()->create(['slug' => 'about', 'title' => 'About Us Only Page', 'type' => 'info', 'is_published' => true]);

        $this->get(route('legal.index'))->assertOk()->assertDontSee('About Us Only Page');
    }

    public function test_legal_hub_hides_a_document_scoped_to_a_service_that_does_not_exist(): void
    {
        Page::factory()->create([
            'slug' => 'ghost-service-policy', 'title' => 'Ghost Service Policy', 'type' => 'legal',
            'category' => 'money', 'is_published' => true,
            'applicable_services' => ['a_service_route_that_will_never_exist'],
        ]);

        $this->get(route('legal.index'))->assertOk()->assertDontSee('Ghost Service Policy');
    }

    public function test_a_published_legal_document_renders_at_legal_show(): void
    {
        Page::factory()->create([
            'slug' => 'sample-policy', 'title' => 'Sample Policy', 'type' => 'legal',
            'excerpt' => 'A short excerpt.', 'body' => "## First Section\n\nSome text.\n\n## Second Section\n\nMore text.",
            'is_published' => true,
        ]);

        $response = $this->get(route('legal.show', 'sample-policy'));

        $response->assertOk()
            ->assertSee('Sample Policy')
            ->assertSee('A short excerpt.')
            ->assertSee('First Section')
            ->assertSee('Second Section')
            ->assertSee('id="first-section"', false);
    }

    public function test_unpublished_legal_document_404s_on_the_public_route(): void
    {
        $page = Page::factory()->create(['slug' => 'draft-policy', 'type' => 'legal', 'is_published' => false]);

        $this->get(route('legal.show', $page))->assertNotFound();
    }

    public function test_old_p_slug_url_redirects_permanently_to_the_legal_route(): void
    {
        $page = Page::factory()->create(['slug' => 'sample-policy', 'type' => 'legal', 'is_published' => true]);

        $this->get(route('pages.show', $page))
            ->assertRedirect(route('legal.show', $page))
            ->assertStatus(301);
    }

    public function test_info_type_page_still_serves_directly_at_p_slug_and_does_not_redirect(): void
    {
        $page = Page::factory()->create(['slug' => 'company-story', 'type' => 'info', 'is_published' => true]);

        $this->get(route('pages.show', $page))->assertOk();
    }

    public function test_plain_summary_renders_as_the_in_simple_terms_callout(): void
    {
        Page::factory()->create([
            'slug' => 'sample-policy', 'type' => 'legal', 'is_published' => true,
            'plain_summary' => '- We only do good things.',
        ]);

        $this->get(route('legal.show', 'sample-policy'))
            ->assertOk()
            ->assertSee('In simple terms')
            ->assertSee('We only do good things');
    }

    public function test_related_policies_in_the_same_category_are_shown(): void
    {
        Page::factory()->create(['slug' => 'policy-a', 'title' => 'Policy A', 'type' => 'legal', 'category' => 'money', 'is_published' => true]);
        Page::factory()->create(['slug' => 'policy-b', 'title' => 'Policy B', 'type' => 'legal', 'category' => 'money', 'is_published' => true]);

        $this->get(route('legal.show', 'policy-a'))
            ->assertOk()
            ->assertSee('Related policies')
            ->assertSee('Policy B');
    }

    public function test_a_script_tag_typed_into_the_body_is_escaped_never_executed(): void
    {
        Page::factory()->create([
            'slug' => 'sample-policy', 'type' => 'legal', 'is_published' => true,
            'body' => "## Section\n\nText with an attempt: <script>alert(1)</script>",
        ]);

        $response = $this->get(route('legal.show', 'sample-policy'));

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_an_unset_company_placeholder_token_shows_a_bracketed_pending_review_notice_not_blank_or_fake_text(): void
    {
        Page::factory()->create([
            'slug' => 'sample-policy', 'type' => 'legal', 'is_published' => true,
            'body' => 'Issued by {{company_legal_name}}.',
        ]);

        $response = $this->get(route('legal.show', 'sample-policy'));

        $response->assertOk();
        $response->assertDontSee('{{company_legal_name}}', false);
        $response->assertSee('pending legal/company review');
    }

    public function test_a_configured_company_setting_resolves_the_token_instead_of_the_placeholder(): void
    {
        app(\App\Services\Settings\SettingsService::class)->set('company_legal_name', 'Verified Trading Co Ltd', 'string');

        Page::factory()->create([
            'slug' => 'sample-policy', 'type' => 'legal', 'is_published' => true,
            'body' => 'Issued by {{company_legal_name}}.',
        ]);

        $this->get(route('legal.show', 'sample-policy'))
            ->assertOk()
            ->assertSee('Verified Trading Co Ltd')
            ->assertDontSee('pending legal/company review');
    }

    public function test_admin_can_update_a_legal_page_category_and_service_scoping(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
        $page = Page::factory()->create(['slug' => 'sample-policy', 'type' => 'legal', 'category' => 'general']);

        $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'type' => 'legal',
            'category' => 'money',
            'plain_summary' => '- A summary point.',
            'body' => '## A Section',
            'applicable_services' => 'withdrawals, shipping_agents',
            'applicable_countries' => 'CM, NG',
            'effective_date' => '2026-01-01',
            'internal_review_notes' => 'Needs lawyer sign-off.',
        ])->assertRedirect();

        $page->refresh();
        $this->assertSame('money', $page->category);
        $this->assertSame(['withdrawals', 'shipping_agents'], $page->applicable_services);
        $this->assertSame(['CM', 'NG'], $page->applicable_countries);
        $this->assertSame('2026-01-01', $page->effective_date->format('Y-m-d'));
        $this->assertSame('Needs lawyer sign-off.', $page->internal_review_notes);
    }

    public function test_internal_review_notes_are_never_rendered_on_the_public_page(): void
    {
        Page::factory()->create([
            'slug' => 'sample-policy', 'type' => 'legal', 'is_published' => true,
            'internal_review_notes' => 'SECRET-ADMIN-ONLY-MARKER-xyz123',
        ]);

        $this->get(route('legal.show', 'sample-policy'))
            ->assertOk()
            ->assertDontSee('SECRET-ADMIN-ONLY-MARKER-xyz123');
    }

    public function test_all_eight_seeded_phase_one_policies_render_without_error_and_leak_no_unresolved_tokens(): void
    {
        $this->seed(\Database\Seeders\ContentSeeder::class);

        foreach ([
            'terms', 'privacy', 'cookie-policy', 'acceptable-use-policy',
            'deposit-policy', 'china-wallet-funding-terms', 'refund-policy', 'marketplace-terms',
        ] as $slug) {
            $response = $this->get(route('legal.show', $slug));
            $response->assertOk();
            $this->assertDoesNotMatchRegularExpression('/\{\{\s*[a-z_]+\s*\}\}/', $response->getContent());
        }
    }

    public function test_renderer_extracts_headings_in_document_order_with_stable_ids(): void
    {
        $renderer = app(LegalContentRenderer::class);

        $headings = $renderer->extractHeadings("## First\n\nText.\n\n### Nested\n\nText.\n\n## Second");

        $this->assertSame(['first', 'nested', 'second'], array_column($headings, 'id'));
        $this->assertSame([2, 3, 2], array_column($headings, 'level'));
    }
}
