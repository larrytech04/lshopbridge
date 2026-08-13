<?php

namespace Tests\Feature\Seo;

use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GA4/GTM/verification scripts only ever load when an admin has actually
 * configured a real ID — never a hardcoded production ID baked into the
 * template (see brief section 23). No existing coverage of this before —
 * also covers the pre-existing GA4/verification wiring, not just the new
 * GTM addition.
 */
class AnalyticsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_analytics_scripts_load_when_nothing_is_configured(): void
    {
        $content = $this->get(route('home'))->getContent();

        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $content);
        $this->assertStringNotContainsString('googletagmanager.com/gtm.js', $content);
        $this->assertStringNotContainsString('google-site-verification', $content);
        $this->assertStringNotContainsString('msvalidate.01', $content);
    }

    public function test_ga4_loads_only_when_configured(): void
    {
        app(SettingsService::class)->set('google_analytics_id', 'G-TESTID123');

        $content = $this->get(route('home'))->getContent();

        $this->assertStringContainsString('googletagmanager.com/gtag/js?id=G-TESTID123', $content);
        $this->assertSame(1, substr_count($content, 'googletagmanager.com/gtag/js'));
    }

    public function test_gtm_loads_only_when_configured(): void
    {
        app(SettingsService::class)->set('google_tag_manager_id', 'GTM-TEST123');

        $content = $this->get(route('home'))->getContent();

        // The ID is passed as the final argument to the standard GTM IIFE
        // snippet, not interpolated directly next to "id=" — assert the
        // configured ID is actually present (not a hardcoded placeholder)
        // and the loader script itself only appears once.
        $this->assertStringContainsString("'GTM-TEST123'", $content);
        $this->assertSame(1, substr_count($content, 'googletagmanager.com/gtm.js'));
    }

    public function test_search_console_verification_loads_only_when_configured(): void
    {
        app(SettingsService::class)->set('google_site_verification', 'test-verification-code');

        $response = $this->get(route('home'));

        $response->assertSee('<meta name="google-site-verification" content="test-verification-code">', false);
    }

    public function test_bing_verification_loads_only_when_configured(): void
    {
        app(SettingsService::class)->set('bing_site_verification', 'test-bing-code');

        $response = $this->get(route('home'));

        $response->assertSee('<meta name="msvalidate.01" content="test-bing-code">', false);
    }
}
