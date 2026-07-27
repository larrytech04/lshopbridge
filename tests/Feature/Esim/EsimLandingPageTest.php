<?php

namespace Tests\Feature\Esim;

use App\Models\Country;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\ShopVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EsimLandingPageTest extends TestCase
{
    use RefreshDatabase;

    private function esimProduct(string $name, string $scope, ?array $coverage, array $variantAttrs = []): ShopProduct
    {
        $category = ShopCategory::factory()->create();
        $product = ShopProduct::factory()->create([
            'shop_category_id' => $category->id, 'type' => 'esim', 'name' => $name,
            'esim_scope' => $scope, 'esim_coverage_countries' => $coverage,
        ]);
        ShopVariant::factory()->create($variantAttrs + ['shop_product_id' => $product->id]);

        return $product;
    }

    public function test_landing_page_lists_only_esim_products_with_active_variants(): void
    {
        Country::updateOrCreate(['iso2' => 'CN'], ['name' => 'China', 'flag_emoji' => '🇨🇳']);
        $this->esimProduct('China Travel eSIM', 'local', ['CN']);

        $giftcardCategory = ShopCategory::factory()->create();
        ShopProduct::factory()->create(['shop_category_id' => $giftcardCategory->id, 'type' => 'giftcard', 'name' => 'Amazon Gift Card']);

        $response = $this->get(route('esim.index'));

        $response->assertOk();
        $response->assertSee('China Travel eSIM');
        $response->assertDontSee('Amazon Gift Card');
    }

    public function test_search_filters_by_product_name_or_region(): void
    {
        $this->esimProduct('China Travel eSIM', 'local', ['CN']);
        $this->esimProduct('Global eSIM', 'global', null);

        $response = $this->get(route('esim.index', ['q' => 'China']));

        $response->assertOk();
        $response->assertSee('China Travel eSIM');
        $response->assertDontSee('Global eSIM');
    }

    public function test_scope_tab_filters_products(): void
    {
        $this->esimProduct('China Travel eSIM', 'local', ['CN']);
        $this->esimProduct('Global eSIM', 'global', null);

        $response = $this->get(route('esim.index', ['scope' => 'global']));

        $response->assertOk();
        $response->assertSee('Global eSIM');
        $response->assertDontSee('China Travel eSIM');
    }

    public function test_destination_chips_reflect_only_real_coverage_data(): void
    {
        Country::updateOrCreate(['iso2' => 'CN'], ['name' => 'China', 'flag_emoji' => '🇨🇳']);
        Country::updateOrCreate(['iso2' => 'US'], ['name' => 'United States', 'flag_emoji' => '🇺🇸']);
        $this->esimProduct('China Travel eSIM', 'local', ['CN']);
        $this->esimProduct('Global eSIM', 'global', null);

        $response = $this->get(route('esim.index'));
        $response->assertOk();

        // Scope to the destination-chip list specifically — the unrelated onboarding
        // popup elsewhere on the page legitimately mentions "United States".
        $html = $response->getContent();
        $start = strpos($html, 'mt-4 flex flex-wrap gap-2');
        $end = strpos($html, 'no-scrollbar mt-6 flex', $start);
        $chipHtml = substr($html, $start, $end - $start);

        $this->assertStringContainsString('China', $chipHtml);
        $this->assertStringNotContainsString('United States', $chipHtml);
    }

    public function test_inactive_esim_product_is_not_listed(): void
    {
        $product = $this->esimProduct('Inactive eSIM', 'local', ['CN']);
        $product->update(['is_active' => false]);

        $response = $this->get(route('esim.index'));

        $response->assertOk();
        $response->assertDontSee('Inactive eSIM');
    }
}
