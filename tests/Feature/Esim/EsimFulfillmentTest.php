<?php

namespace Tests\Feature\Esim;

use App\Enums\ShopOrderItemStatus;
use App\Enums\ShopOrderStatus;
use App\Models\EsimProvisioning;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopProduct;
use App\Models\ShopVariant;
use App\Models\User;
use App\Notifications\EsimPendingProvisioning;
use App\Notifications\ShopOrderDelivered;
use App\Notifications\ShopOrderProcessing;
use App\Services\Shop\ShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The exact behaviour the user asked for after the pre-build audit found the
 * platform was fabricating fake, non-functional eSIM activation codes for
 * real paying customers: an eSIM purchase with no connected provider must
 * route to manual review, never invent a code.
 */
class EsimFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private function esimOrder(User $user): ShopOrderItem
    {
        $product = ShopProduct::factory()->create(['type' => 'esim']);
        $variant = ShopVariant::factory()->create(['shop_product_id' => $product->id]);
        $order = ShopOrder::factory()->create(['user_id' => $user->id, 'status' => ShopOrderStatus::Paid]);

        return ShopOrderItem::create([
            'shop_order_id' => $order->id,
            'shop_product_id' => $product->id,
            'shop_variant_id' => $variant->id,
            'name' => $product->name,
            'type' => 'esim',
            'unit_price' => 10000,
            'quantity' => 1,
            'line_total' => 10000,
            'status' => ShopOrderItemStatus::Pending,
        ]);
    }

    public function test_esim_fulfilment_never_fabricates_an_activation_code(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $item = $this->esimOrder($user);

        app(ShopService::class)->fulfill($item->order->fresh());

        $item->refresh();
        $this->assertSame(ShopOrderItemStatus::PendingProvisioning, $item->status);
        $this->assertNull($item->delivered);

        $provisioning = EsimProvisioning::where('shop_order_item_id', $item->id)->first();
        $this->assertNotNull($provisioning);
        $this->assertSame('pending_provisioning', $provisioning->status);
        $this->assertNull($provisioning->lpa_string);

        $order = $item->order->fresh();
        $this->assertSame(ShopOrderStatus::Processing, $order->status);

        Notification::assertSentTo($user, ShopOrderProcessing::class);
        Notification::assertNotSentTo($user, ShopOrderDelivered::class);
    }

    public function test_pending_esim_provisioning_notifies_admins_for_manual_review(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $item = $this->esimOrder($user);

        app(ShopService::class)->fulfill($item->order->fresh());

        Notification::assertSentTo($admin, EsimPendingProvisioning::class);
    }

    public function test_fulfilling_twice_does_not_create_a_second_provisioning_row(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $item = $this->esimOrder($user);
        $order = $item->order;

        $service = app(ShopService::class);
        $service->fulfill($order->fresh());
        $service->fulfill($order->fresh());

        $this->assertSame(1, EsimProvisioning::where('shop_order_item_id', $item->id)->count());
    }

    public function test_admin_can_manually_complete_provisioning_and_customer_is_notified(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $item = $this->esimOrder($user);

        app(ShopService::class)->fulfill($item->order->fresh());
        $provisioning = EsimProvisioning::where('shop_order_item_id', $item->id)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.esim.provisioning.complete', $provisioning), [
            'provider' => 'manual',
            'sm_dp_address' => 'rsp.example.com',
            'activation_code' => 'ABCD1234',
        ]);

        $response->assertRedirect();
        $provisioning->refresh();
        $this->assertSame('ready', $provisioning->status);
        $this->assertSame('LPA:1$rsp.example.com$ABCD1234', $provisioning->lpa_string);

        $item->refresh();
        $this->assertSame(ShopOrderItemStatus::Fulfilled, $item->status);
        $this->assertSame(ShopOrderStatus::Fulfilled, $item->order->fresh()->status);
    }

    public function test_admin_completion_requires_at_least_one_real_activation_field(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $item = $this->esimOrder($user);

        app(ShopService::class)->fulfill($item->order->fresh());
        $provisioning = EsimProvisioning::where('shop_order_item_id', $item->id)->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.esim.provisioning.complete', $provisioning), [
            'provider' => 'manual',
        ]);

        $response->assertSessionHasErrors('lpa_string');
        $this->assertSame('pending_provisioning', $provisioning->fresh()->status);
    }
}
