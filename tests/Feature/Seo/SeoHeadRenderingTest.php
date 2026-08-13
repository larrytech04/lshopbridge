<?php

namespace Tests\Feature\Seo;

use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end: does a real public page, through the real layout, actually
 * emit correct <head> tags — not just "does the service return the right
 * DTO" (see SeoServiceTest) but "does the Blade component wire it in
 * correctly and exactly once."
 */
class SeoHeadRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_has_exactly_one_title_tag(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '<title>'));
    }

    public function test_the_homepage_title_matches_the_configured_convention(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Africa-China Payments, eSIMs and Digital Services | LshopBridge', false);
    }

    public function test_the_homepage_canonical_is_https_and_appears_exactly_once(): void
    {
        $response = $this->get(route('home'));
        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, 'rel="canonical"'));
        $this->assertMatchesRegularExpression('#<link rel="canonical" href="https://#', $content);
    }

    public function test_the_homepage_has_a_robots_meta_tag(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    public function test_indexing_flips_to_index_follow_in_production_with_the_setting_on(): void
    {
        $this->app->instance('env', 'production');
        app(SettingsService::class)->set('seo_indexing_enabled', true, 'bool');

        $response = $this->get(route('home'));

        $response->assertSee('<meta name="robots" content="index,follow">', false);
    }

    public function test_a_second_public_page_has_a_different_title_than_the_homepage(): void
    {
        $home = $this->get(route('home'))->getContent();
        $faqs = $this->get(route('public.faqs'))->getContent();

        preg_match('#<title>(.*?)</title>#', $home, $homeTitle);
        preg_match('#<title>(.*?)</title>#', $faqs, $faqsTitle);

        $this->assertNotSame($homeTitle[1] ?? null, $faqsTitle[1] ?? null);
    }

    public function test_no_duplicate_meta_description_tags(): void
    {
        $response = $this->get(route('home'));

        $this->assertSame(1, substr_count($response->getContent(), 'name="description"'));
    }

    public function test_og_and_twitter_image_urls_are_https(): void
    {
        $response = $this->get(route('home'));
        $content = $response->getContent();

        preg_match('#<meta property="og:image" content="([^"]+)"#', $content, $og);
        preg_match('#<meta name="twitter:image" content="([^"]+)"#', $content, $tw);

        $this->assertStringStartsWith('https://', $og[1] ?? '');
        $this->assertStringStartsWith('https://', $tw[1] ?? '');
    }

    public function test_the_homepage_carries_exactly_one_organization_and_one_website_block(): void
    {
        $content = $this->get(route('home'))->getContent();

        $this->assertSame(1, substr_count($content, '"@type":"Organization"'));
        $this->assertSame(1, substr_count($content, '"@type":"WebSite"'));
    }

    public function test_organization_and_website_blocks_are_valid_parseable_json(): void
    {
        $content = $this->get(route('home'))->getContent();
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $content, $matches);

        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $json) {
            $this->assertNotNull(json_decode($json), 'Structured data block is not valid JSON: '.$json);
        }
    }

    public function test_a_non_homepage_public_page_does_not_carry_the_organization_block(): void
    {
        // Organization/WebSite are homepage-specific by design (see
        // SeoService's docblock) — asserting this stays true so a future
        // edit doesn't accidentally duplicate the entity across every page.
        $content = $this->get(route('public.faqs'))->getContent();

        $this->assertStringNotContainsString('"@type":"Organization"', $content);
    }

    public function test_theme_color_is_not_duplicated_by_the_seo_component(): void
    {
        $response = $this->get(route('home'));

        $this->assertSame(1, substr_count($response->getContent(), 'name="theme-color"'));
    }
}
