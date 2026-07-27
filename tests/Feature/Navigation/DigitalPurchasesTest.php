<?php

namespace Tests\Feature\Navigation;

use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalPurchasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_digital_purchases_only_lists_orders_with_a_non_physical_item(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $digitalProduct = ShopProduct::factory()->create(['type' => 'giftcard']);
        $physicalProduct = ShopProduct::factory()->create(['type' => 'physical']);

        $digitalOrder = ShopOrder::factory()->create(['user_id' => $user->id, 'reference' => 'PB-DIGITAL-ORDER']);
        ShopOrderItem::create(['shop_order_id' => $digitalOrder->id, 'shop_product_id' => $digitalProduct->id, 'name' => $digitalProduct->name, 'quantity' => 1, 'unit_price' => 10, 'line_total' => 10]);

        $physicalOrder = ShopOrder::factory()->create(['user_id' => $user->id, 'reference' => 'PB-PHYSICAL-ORDER']);
        ShopOrderItem::create(['shop_order_id' => $physicalOrder->id, 'shop_product_id' => $physicalProduct->id, 'name' => $physicalProduct->name, 'quantity' => 1, 'unit_price' => 10, 'line_total' => 10]);

        $response = $this->actingAs($user)->get(route('shop.orders.digital'));

        $response->assertOk();
        $response->assertSee('PB-DIGITAL-ORDER');
        $response->assertDontSee('PB-PHYSICAL-ORDER');
    }

    public function test_guest_cannot_access_digital_purchases(): void
    {
        $this->get(route('shop.orders.digital'))->assertRedirect(route('login'));
    }
}
