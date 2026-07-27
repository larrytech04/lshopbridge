<?php

namespace Tests\Feature\Esim;

use App\Models\Country;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\ShopVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EsimProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_esim_product_page_shows_plan_comparison_and_coverage(): void
    {
        Country::updateOrCreate(['iso2' => 'CN'], ['name' => 'China', 'flag_emoji' => '🇨🇳']);
        $category = ShopCategory::factory()->create(['slug' => 'esims']);
        $product = ShopProduct::factory()->create([
            'shop_category_id' => $category->id, 'type' => 'esim', 'name' => 'China Travel eSIM',
            'esim_scope' => 'local', 'esim_coverage_countries' => ['CN'],
        ]);
        ShopVariant::factory()->create([
            'shop_product_id' => $product->id, 'name' => '5GB · 30 days',
            'data_amount' => '5GB', 'validity_days' => 30, 'is_unlimited_data' => false,
            'topup_supported' => true,
        ]);

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('Coverage');
        $response->assertSee('China');
        $response->assertSee('Check my device compatibility');
        $response->assertSee(route('esim.compatibility.index'), false);
    }

    public function test_non_esim_product_page_does_not_show_esim_sections(): void
    {
        $category = ShopCategory::factory()->create(['slug' => 'gc-games']);
        $product = ShopProduct::factory()->create(['shop_category_id' => $category->id, 'type' => 'giftcard', 'name' => 'Steam Wallet Card']);
        ShopVariant::factory()->create(['shop_product_id' => $product->id]);

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertDontSee('Check my device compatibility');
    }
}
