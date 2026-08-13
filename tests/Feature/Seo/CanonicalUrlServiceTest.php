<?php

namespace Tests\Feature\Seo;

use App\Services\Seo\CanonicalUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CanonicalUrlServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CanonicalUrlService
    {
        return app(CanonicalUrlService::class);
    }

    public function test_it_forces_https_even_when_the_request_arrived_over_http(): void
    {
        $request = Request::create('http://example.test/guides/how-to-import', 'GET');

        $canonical = $this->service()->forCurrentRequest($request);

        $this->assertStringStartsWith('https://', $canonical);
    }

    public function test_it_uses_the_configured_app_host_regardless_of_the_request_host(): void
    {
        config(['app.url' => 'https://lshopbridge.com']);
        $request = Request::create('http://totally-different-host.test/guides', 'GET');

        $canonical = $this->service()->forCurrentRequest($request);

        $this->assertStringStartsWith('https://lshopbridge.com', $canonical);
    }

    public function test_it_prefers_the_seo_canonical_domain_setting_over_app_url(): void
    {
        app(\App\Services\Settings\SettingsService::class)->set('seo_canonical_domain', 'www.lshopbridge.com');
        $request = Request::create('https://example.test/guides', 'GET');

        $canonical = $this->service()->forCurrentRequest($request);

        $this->assertStringStartsWith('https://www.lshopbridge.com', $canonical);
    }

    public function test_it_strips_known_tracking_parameters(): void
    {
        $request = Request::create('https://example.test/guides?utm_source=fb&utm_campaign=x&fbclid=abc123', 'GET');

        $canonical = $this->service()->forCurrentRequest($request);

        $this->assertStringNotContainsString('utm_source', $canonical);
        $this->assertStringNotContainsString('fbclid', $canonical);
    }

    public function test_it_keeps_the_pagination_parameter(): void
    {
        $request = Request::create('https://example.test/guides?page=2', 'GET');

        $canonical = $this->service()->forCurrentRequest($request);

        $this->assertStringContainsString('page=2', $canonical);
    }

    public function test_it_keeps_a_genuine_non_tracking_query_parameter(): void
    {
        $request = Request::create('https://example.test/agents?country=cameroon', 'GET');

        $canonical = $this->service()->forCurrentRequest($request);

        $this->assertStringContainsString('country=cameroon', $canonical);
    }

    public function test_it_strips_a_trailing_slash_on_a_non_root_path(): void
    {
        config(['app.url' => 'https://example.test']);
        $request = Request::create('https://example.test/guides/', 'GET');

        $canonical = $this->service()->forCurrentRequest($request);

        $this->assertSame('https://example.test/guides', $canonical);
    }

    public function test_it_keeps_the_root_path_as_a_single_slash(): void
    {
        config(['app.url' => 'https://example.test']);
        $request = Request::create('https://example.test/', 'GET');

        $canonical = $this->service()->forCurrentRequest($request);

        $this->assertSame('https://example.test/', $canonical);
    }

    public function test_normalize_adds_a_missing_leading_slash_instead_of_gluing_onto_the_host(): void
    {
        config(['app.url' => 'https://lshopbridge.com']);

        // A caller passing a bare relative path (e.g. a stored branding
        // path that was never run through asset() first) must never
        // produce a URL with no separator between host and path.
        $canonical = $this->service()->normalize('branding/og-image.jpg');

        $this->assertSame('https://lshopbridge.com/branding/og-image.jpg', $canonical);
    }

    public function test_from_override_normalizes_a_full_url(): void
    {
        config(['app.url' => 'https://lshopbridge.com']);

        $canonical = $this->service()->fromOverride('http://lshopbridge.com/old-guide/');

        $this->assertSame('https://lshopbridge.com/old-guide', $canonical);
    }

    public function test_from_override_normalizes_a_relative_path(): void
    {
        config(['app.url' => 'https://lshopbridge.com']);

        $canonical = $this->service()->fromOverride('/guides/new-guide');

        $this->assertSame('https://lshopbridge.com/guides/new-guide', $canonical);
    }

    public function test_normalize_is_idempotent(): void
    {
        config(['app.url' => 'https://lshopbridge.com']);

        $once = $this->service()->normalize('https://lshopbridge.com/guides/x');
        $twice = $this->service()->normalize($once);

        $this->assertSame($once, $twice);
    }
}
