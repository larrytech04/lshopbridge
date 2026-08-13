<?php

namespace Tests\Feature\Seo;

use App\Models\Country;
use App\Models\MomoNumber;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Country pages render only from real, admin-configured data (Mobile Money
 * numbers, payment-method/China-wallet-type country restrictions) — see
 * CountryController's docblock. A country with none of that stays fully
 * viewable but noindex, never a thin doorway page.
 */
class CountryPagesTest extends TestCase
{
    use RefreshDatabase;

    private function productionIndexable(): void
    {
        $this->app->instance('env', 'production');
        app(SettingsService::class)->set('seo_indexing_enabled', true, 'bool');
    }

    /**
     * Country::booted() auto-generates a slug on any creation path, not
     * just CountryFactory — regression test for a real bug this caught:
     * several pre-existing tests raw-Country::create(['iso2' => 'CN', ...])
     * without a slug, which started failing NOT NULL once the slug column
     * (added for country landing pages) became required.
     */
    public function test_a_country_created_without_an_explicit_slug_still_gets_one(): void
    {
        $country = Country::create(['iso2' => 'CN', 'name' => 'China', 'flag_emoji' => '🇨🇳']);

        $this->assertNotNull($country->slug);
        $this->assertStringStartsWith('china-', $country->slug);
    }

    public function test_the_index_page_lists_every_active_country(): void
    {
        Country::factory()->create(['name' => 'Testland', 'is_active' => true]);

        $this->get(route('countries.index'))->assertSee('Testland');
    }

    public function test_a_disabled_country_404s(): void
    {
        $country = Country::factory()->create(['is_active' => true, 'launch_status' => 'disabled']);

        $this->get(route('countries.show', $country))->assertNotFound();
    }

    public function test_a_country_with_real_payment_infrastructure_is_indexable_in_production(): void
    {
        $this->productionIndexable();
        $country = Country::factory()->create(['is_active' => true, 'launch_status' => 'active']);
        MomoNumber::create(['provider' => 'mtn', 'number' => '677000000', 'account_name' => 'Test', 'country_id' => $country->id, 'is_active' => true]);

        $response = $this->get(route('countries.show', $country));

        $response->assertSee('<meta name="robots" content="index,follow">', false);
    }

    public function test_a_country_with_no_real_payment_infrastructure_stays_noindex_even_in_production(): void
    {
        $this->productionIndexable();
        $country = Country::factory()->create(['is_active' => true, 'launch_status' => 'active']);

        $response = $this->get(route('countries.show', $country));

        $response->assertSee('noindex', false);
        $response->assertOk();
    }

    public function test_a_country_not_yet_fully_launched_stays_noindex_even_with_real_infrastructure(): void
    {
        $this->productionIndexable();
        $country = Country::factory()->create(['is_active' => true, 'launch_status' => 'coming_soon']);
        MomoNumber::create(['provider' => 'mtn', 'number' => '677000000', 'account_name' => 'Test', 'country_id' => $country->id, 'is_active' => true]);

        $response = $this->get(route('countries.show', $country));

        $response->assertSee('noindex', false);
    }

    public function test_a_page_level_robots_override_can_never_force_indexing_outside_production(): void
    {
        // The general safeguard, exercised through a real page: even though
        // this country's own page tries to set index,follow (real
        // infrastructure, fully launched), the environment isn't
        // production, so it must still come out noindex.
        $country = Country::factory()->create(['is_active' => true, 'launch_status' => 'active']);
        MomoNumber::create(['provider' => 'mtn', 'number' => '677000000', 'account_name' => 'Test', 'country_id' => $country->id, 'is_active' => true]);

        $response = $this->get(route('countries.show', $country));

        $response->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    public function test_shows_the_real_mobile_money_provider_when_configured(): void
    {
        $country = Country::factory()->create(['is_active' => true, 'launch_status' => 'active']);
        MomoNumber::create(['provider' => 'orange', 'number' => '699000000', 'account_name' => 'Test', 'country_id' => $country->id, 'is_active' => true]);

        $this->get(route('countries.show', $country))->assertSee('Orange');
    }

    public function test_does_not_show_an_inactive_mobile_money_number(): void
    {
        $country = Country::factory()->create(['is_active' => true, 'launch_status' => 'active']);
        MomoNumber::create(['provider' => 'mtn', 'number' => '677000000', 'account_name' => 'Test', 'country_id' => $country->id, 'is_active' => false]);

        $this->get(route('countries.show', $country))->assertDontSee('Mtn');
    }

    public function test_the_country_page_is_reachable_via_a_normal_link_from_payment_methods(): void
    {
        $this->get(route('public.payment-methods'))
            ->assertSee('href="'.route('countries.index').'"', false);
    }

    public function test_sitemap_only_includes_countries_with_real_infrastructure_and_full_launch(): void
    {
        $real = Country::factory()->create(['is_active' => true, 'launch_status' => 'active', 'slug' => 'real-country']);
        MomoNumber::create(['provider' => 'mtn', 'number' => '677000000', 'account_name' => 'Test', 'country_id' => $real->id, 'is_active' => true]);

        $thin = Country::factory()->create(['is_active' => true, 'launch_status' => 'active', 'slug' => 'thin-country']);
        $comingSoon = Country::factory()->create(['is_active' => true, 'launch_status' => 'coming_soon', 'slug' => 'coming-soon-country']);
        MomoNumber::create(['provider' => 'mtn', 'number' => '677000001', 'account_name' => 'Test', 'country_id' => $comingSoon->id, 'is_active' => true]);

        $content = $this->get('/sitemap-countries.xml')->getContent();

        $this->assertStringContainsString('real-country', $content);
        $this->assertStringNotContainsString('thin-country', $content);
        $this->assertStringNotContainsString('coming-soon-country', $content);
    }

    public function test_the_countries_index_is_included_in_the_static_pages_sitemap(): void
    {
        $content = $this->get('/sitemap-pages.xml')->getContent();

        $this->assertStringContainsString('<loc>https://localhost/countries</loc>', $content);
    }
}
