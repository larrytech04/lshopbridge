<?php

namespace Tests\Feature\Seo;

use App\Models\Page;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Services\Seo\SeoService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SeoServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SeoService
    {
        return app(SeoService::class);
    }

    private function request(): Request
    {
        return Request::create('https://example.test/some-page', 'GET');
    }

    public function test_defaults_builds_a_sane_title_description_and_canonical(): void
    {
        $seo = $this->service()->defaults($this->request());

        $this->assertNotEmpty($seo->title);
        $this->assertNotEmpty($seo->description);
        $this->assertStringStartsWith('https://', $seo->canonical);
    }

    public function test_the_admin_configured_default_share_image_is_a_real_well_formed_url(): void
    {
        config(['app.url' => 'https://lshopbridge.com']);
        app(SettingsService::class)->set('seo_default_og_image', 'branding/seo_default_og_image-123.jpg');

        $seo = $this->service()->defaults($this->request());

        // The exact bug this guards: forgetting to run a stored relative
        // path through asset() first used to glue it straight onto the
        // host with no separating slash.
        $this->assertSame('https://lshopbridge.com/branding/seo_default_og_image-123.jpg', $seo->ogImage);
    }

    public function test_falls_back_to_the_site_logo_when_no_default_share_image_is_configured(): void
    {
        $seo = $this->service()->defaults($this->request());

        $this->assertNotNull($seo->ogImage);
        $this->assertStringStartsWith('https://', $seo->ogImage);
    }

    public function test_indexing_is_disallowed_outside_production_regardless_of_the_setting(): void
    {
        app(SettingsService::class)->set('seo_indexing_enabled', true, 'bool');

        $this->assertFalse($this->service()->isIndexingAllowed());
    }

    public function test_indexing_is_allowed_in_production_when_the_setting_is_on(): void
    {
        $this->app->instance('env', 'production');
        app(SettingsService::class)->set('seo_indexing_enabled', true, 'bool');

        $this->assertTrue($this->service()->isIndexingAllowed());
    }

    public function test_indexing_is_disallowed_in_production_when_the_setting_is_off(): void
    {
        $this->app->instance('env', 'production');
        app(SettingsService::class)->set('seo_indexing_enabled', false, 'bool');

        $this->assertFalse($this->service()->isIndexingAllowed());
    }

    public function test_default_robots_reflects_indexing_allowed_state(): void
    {
        $this->app->instance('env', 'production');
        app(SettingsService::class)->set('seo_indexing_enabled', true, 'bool');

        $seo = $this->service()->defaults($this->request());

        $this->assertSame('index,follow', $seo->robots);
    }

    public function test_default_robots_is_noindex_when_indexing_is_not_allowed(): void
    {
        $seo = $this->service()->defaults($this->request());

        $this->assertSame('noindex,nofollow', $seo->robots);
    }

    public function test_build_never_lets_an_override_force_indexing_when_not_allowed(): void
    {
        // Outside production, no explicit override should be able to force
        // index,follow — see SeoService::enforceIndexingSafeguard().
        $seo = $this->service()->build($this->request(), ['robots' => 'index,follow']);

        $this->assertSame('noindex,nofollow', $seo->robots);
    }

    public function test_build_still_allows_a_more_restrictive_override_than_the_default(): void
    {
        $this->app->instance('env', 'production');
        app(SettingsService::class)->set('seo_indexing_enabled', true, 'bool');

        $seo = $this->service()->build($this->request(), ['robots' => 'noindex,follow']);

        $this->assertSame('noindex,follow', $seo->robots);
    }

    public function test_for_model_never_lets_a_native_column_or_metadata_row_force_indexing_when_not_allowed(): void
    {
        $product = \App\Models\ShopProduct::factory()->create();
        $product->seoMetadata()->create(['robots' => 'index,follow']);

        $seo = $this->service()->forModel($this->request(), $product->fresh());

        $this->assertSame('noindex,nofollow', $seo->robots);
    }

    public function test_for_model_falls_back_to_defaults_when_no_native_seo_columns_are_set(): void
    {
        $page = Page::factory()->create(['title' => 'Refund Policy', 'meta_title' => null, 'meta_description' => null]);

        $seo = $this->service()->forModel($this->request(), $page);
        $defaults = $this->service()->defaults($this->request());

        $this->assertSame($defaults->title, $seo->title);
    }

    public function test_for_model_uses_a_pages_own_native_meta_title_and_description_columns(): void
    {
        // Page already shipped its own meta_title/meta_description columns
        // (with a real admin form) before this SEO system existed — see
        // SeoService::applyNativeSeoColumns().
        $page = Page::factory()->create([
            'meta_title' => 'Refund Policy | LshopBridge',
            'meta_description' => 'How refunds work on LshopBridge.',
        ]);

        $seo = $this->service()->forModel($this->request(), $page);

        $this->assertSame('Refund Policy | LshopBridge', $seo->title);
        $this->assertSame('How refunds work on LshopBridge.', $seo->description);
    }

    public function test_for_model_uses_a_shop_categorys_own_native_seo_title_and_canonical_url_columns(): void
    {
        // ShopCategory's own column is named seo_title, not meta_title —
        // confirms the fallback-to-either-name logic actually works, not
        // just the meta_title case.
        config(['app.url' => 'https://lshopbridge.com']);
        $category = ShopCategory::factory()->create([
            'seo_title' => 'Gift Cards | LshopBridge',
            'canonical_url' => '/shop/c/gift-cards',
        ]);

        $seo = $this->service()->forModel($this->request(), $category);

        $this->assertSame('Gift Cards | LshopBridge', $seo->title);
        $this->assertSame('https://lshopbridge.com/shop/c/gift-cards', $seo->canonical);
    }

    public function test_for_model_overrides_win_over_a_pages_native_seo_columns(): void
    {
        $page = Page::factory()->create(['meta_title' => 'Stored Title']);

        $seo = $this->service()->forModel($this->request(), $page, ['title' => 'Explicit Override']);

        $this->assertSame('Explicit Override', $seo->title);
    }

    public function test_for_model_uses_the_stored_seo_metadata_row_for_a_model_with_no_native_columns(): void
    {
        // ShopProduct has no dedicated SEO columns of its own — it uses the
        // generic seo_metadata table via HasSeoMetadata.
        $product = ShopProduct::factory()->create();
        $product->seoMetadata()->create([
            'meta_title' => 'Product Title | LshopBridge',
            'meta_description' => 'A real product description.',
        ]);

        $seo = $this->service()->forModel($this->request(), $product->fresh());

        $this->assertSame('Product Title | LshopBridge', $seo->title);
        $this->assertSame('A real product description.', $seo->description);
    }

    public function test_for_model_normalizes_a_relative_canonical_override_from_the_seo_metadata_table(): void
    {
        config(['app.url' => 'https://lshopbridge.com']);
        $product = ShopProduct::factory()->create();
        $product->seoMetadata()->create(['canonical_override' => '/shop/p/example']);

        $seo = $this->service()->forModel($this->request(), $product->fresh());

        $this->assertSame('https://lshopbridge.com/shop/p/example', $seo->canonical);
    }

    public function test_append_structured_data_adds_without_discarding_existing_blocks(): void
    {
        $seo = $this->service()->defaults($this->request());
        $seo = $this->service()->appendStructuredData($seo, ['@type' => 'A']);
        $seo = $this->service()->appendStructuredData($seo, ['@type' => 'B']);

        $this->assertCount(2, $seo->structuredData);
        $this->assertSame('A', $seo->structuredData[0]['@type']);
        $this->assertSame('B', $seo->structuredData[1]['@type']);
    }

    public function test_with_breadcrumbs_sets_breadcrumbs_and_appends_matching_json_ld(): void
    {
        $items = [['name' => 'Home', 'url' => 'https://example.test/']];
        $seo = $this->service()->defaults($this->request());

        $seo = $this->service()->withBreadcrumbs($seo, $items);

        $this->assertSame($items, $seo->breadcrumbs);
        $this->assertSame('BreadcrumbList', $seo->structuredData[0]['@type']);
    }

    public function test_organization_block_omits_socials_that_are_not_configured(): void
    {
        $block = $this->service()->organizationBlock();

        $this->assertArrayNotHasKey('sameAs', $block);
    }

    public function test_organization_block_includes_only_configured_social_links(): void
    {
        app(SettingsService::class)->set('social_x', 'https://x.com/lshopbridge');

        $block = $this->service()->organizationBlock();

        $this->assertSame(['https://x.com/lshopbridge'], $block['sameAs']);
    }

    public function test_organization_block_prefers_company_trading_name_when_set(): void
    {
        app(SettingsService::class)->set('company_trading_name', 'LshopBridge Ltd');

        $block = $this->service()->organizationBlock();

        $this->assertSame('LshopBridge Ltd', $block['name']);
    }

    public function test_website_block_has_a_matching_stable_id(): void
    {
        config(['app.url' => 'https://lshopbridge.com']);

        $block = $this->service()->websiteBlock();

        $this->assertSame('https://lshopbridge.com/#website', $block['@id']);
    }
}
