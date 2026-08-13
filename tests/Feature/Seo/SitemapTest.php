<?php

namespace Tests\Feature\Seo;

use App\Models\Agent;
use App\Models\Guide;
use App\Models\Page;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_is_served_as_xml_and_lists_every_group(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('sitemap-pages.xml', false);
        $response->assertSee('sitemap-guides.xml', false);
        $response->assertSee('sitemap-shop-categories.xml', false);
        $response->assertSee('sitemap-shop-products.xml', false);
        $response->assertSee('sitemap-agents.xml', false);
    }

    public function test_an_unknown_group_404s(): void
    {
        $this->get('/sitemap-nonexistent.xml')->assertNotFound();
    }

    public function test_the_static_pages_sitemap_includes_the_homepage(): void
    {
        $response = $this->get('/sitemap-pages.xml');

        $response->assertOk();
        $response->assertSee('<loc>https://localhost/</loc>', false);
    }

    public function test_a_published_guide_is_included(): void
    {
        $guide = Guide::factory()->create(['is_published' => true, 'slug' => 'published-guide']);

        $this->get('/sitemap-guides.xml')->assertSee($guide->slug, false);
    }

    public function test_an_unpublished_guide_is_excluded(): void
    {
        $guide = Guide::factory()->create(['is_published' => false, 'slug' => 'draft-guide']);

        $this->get('/sitemap-guides.xml')->assertDontSee($guide->slug, false);
    }

    public function test_an_active_shop_category_is_included(): void
    {
        $category = ShopCategory::factory()->create(['is_active' => true, 'slug' => 'active-category']);

        $this->get('/sitemap-shop-categories.xml')->assertSee($category->slug, false);
    }

    public function test_an_inactive_shop_category_is_excluded(): void
    {
        $category = ShopCategory::factory()->create(['is_active' => false, 'slug' => 'inactive-category']);

        $this->get('/sitemap-shop-categories.xml')->assertDontSee($category->slug, false);
    }

    public function test_an_active_shop_product_is_included(): void
    {
        $product = ShopProduct::factory()->create(['is_active' => true, 'slug' => 'active-product']);

        $this->get('/sitemap-shop-products.xml')->assertSee($product->slug, false);
    }

    public function test_an_inactive_shop_product_is_excluded(): void
    {
        $product = ShopProduct::factory()->create(['is_active' => false, 'slug' => 'inactive-product']);

        $this->get('/sitemap-shop-products.xml')->assertDontSee($product->slug, false);
    }

    public function test_an_approved_agent_is_included(): void
    {
        $agent = Agent::factory()->approved()->create(['slug' => 'approved-agent']);

        $this->get('/sitemap-agents.xml')->assertSee($agent->slug, false);
    }

    public function test_a_pending_agent_is_excluded(): void
    {
        $agent = Agent::factory()->create(['status' => 'pending', 'slug' => 'pending-agent']);

        $this->get('/sitemap-agents.xml')->assertDontSee($agent->slug, false);
    }

    public function test_a_published_legal_page_is_included(): void
    {
        $page = Page::factory()->create(['is_published' => true, 'type' => 'legal', 'slug' => 'published-legal-page']);

        $this->get('/sitemap-pages.xml')->assertSee($page->slug, false);
    }

    public function test_an_unpublished_page_is_excluded(): void
    {
        $page = Page::factory()->create(['is_published' => false, 'type' => 'legal', 'slug' => 'unpublished-legal-page']);

        $this->get('/sitemap-pages.xml')->assertDontSee($page->slug, false);
    }

    public function test_a_record_explicitly_opted_out_via_seo_metadata_is_excluded(): void
    {
        // Guide/Page/ShopCategory have their own native SEO columns (see
        // SeoService::applyNativeSeoColumns()) with no sitemap_include
        // concept, so this only applies to models using the generic
        // seo_metadata table — ShopProduct here.
        $product = ShopProduct::factory()->create(['is_active' => true, 'slug' => 'opted-out-product']);
        $product->seoMetadata()->create(['sitemap_include' => false]);

        $this->get('/sitemap-shop-products.xml')->assertDontSee($product->slug, false);
    }

    public function test_a_record_with_noindex_robots_override_is_excluded_even_if_sitemap_include_is_true(): void
    {
        $product = ShopProduct::factory()->create(['is_active' => true, 'slug' => 'noindexed-product']);
        $product->seoMetadata()->create(['sitemap_include' => true, 'robots' => 'noindex,follow']);

        $this->get('/sitemap-shop-products.xml')->assertDontSee($product->slug, false);
    }

    public function test_all_urls_are_https(): void
    {
        Guide::factory()->create(['is_published' => true, 'slug' => 'https-check-guide']);

        $content = $this->get('/sitemap-guides.xml')->getContent();
        preg_match_all('#<loc>(.*?)</loc>#', $content, $matches);

        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $loc) {
            // Checking each <loc> value specifically, not the whole XML
            // document — the urlset element's xmlns is the fixed, mandatory
            // sitemaps.org namespace string (http://, not https://) and
            // must stay exactly that per the protocol spec.
            $this->assertStringStartsWith('https://', $loc);
        }
    }

    public function test_the_sitemap_never_includes_the_legacy_redirect_only_page_route(): void
    {
        // /p/{slug} (pages.show) is a 301-preserving legacy alias — the
        // canonical, indexable URL for a legal page is always /legal/{slug}.
        Page::factory()->create(['is_published' => true, 'type' => 'legal', 'slug' => 'a-legal-page']);

        $content = $this->get('/sitemap-pages.xml')->getContent();

        $this->assertStringNotContainsString('/p/a-legal-page', $content);
    }
}
