<?php

namespace Tests\Feature\Seo;

use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_served_as_plain_text(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
    }

    public function test_it_disallows_everything_outside_production(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertSee('Disallow: /', false);
        $response->assertDontSee('Disallow: /dashboard', false);
    }

    public function test_it_disallows_everything_in_production_when_the_indexing_setting_is_off(): void
    {
        $this->app->instance('env', 'production');
        app(SettingsService::class)->set('seo_indexing_enabled', false, 'bool');

        $response = $this->get('/robots.txt');

        $response->assertSee("User-agent: *\nDisallow: /", false);
    }

    public function test_it_lists_known_private_prefixes_in_production_with_indexing_on(): void
    {
        $this->app->instance('env', 'production');
        app(SettingsService::class)->set('seo_indexing_enabled', true, 'bool');

        $response = $this->get('/robots.txt');

        $response->assertSee('Disallow: /dashboard', false);
        $response->assertSee('Disallow: /login', false);
        $response->assertSee('Disallow: /cart', false);
    }

    public function test_it_never_mentions_the_secret_admin_path(): void
    {
        config(['platform.admin_path' => 'super-secret-panel-xyz']);
        $this->app->instance('env', 'production');
        app(SettingsService::class)->set('seo_indexing_enabled', true, 'bool');

        $response = $this->get('/robots.txt');

        $response->assertDontSee('super-secret-panel-xyz');
        $response->assertDontSee('admin');
    }

    public function test_it_references_the_sitemap_index_as_a_full_https_url(): void
    {
        config(['app.url' => 'https://lshopbridge.com']);

        $response = $this->get('/robots.txt');

        $response->assertSee('Sitemap: https://lshopbridge.com/sitemap.xml', false);
    }
}
