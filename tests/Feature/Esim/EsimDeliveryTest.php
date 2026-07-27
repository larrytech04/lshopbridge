<?php

namespace Tests\Feature\Esim;

use App\Models\EsimProvisioning;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QR codes and activation data must never leak to anyone but the order owner
 * (or an admin) — no public URL, no cross-account access, per the eSIM
 * spec's QR-security rules.
 */
class EsimDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function readyProvisioning(User $owner): EsimProvisioning
    {
        $product = ShopProduct::factory()->create(['type' => 'esim']);
        $order = ShopOrder::factory()->create(['user_id' => $owner->id]);
        $item = ShopOrderItem::create([
            'shop_order_id' => $order->id, 'shop_product_id' => $product->id,
            'name' => $product->name, 'type' => 'esim', 'unit_price' => 10000,
            'quantity' => 1, 'line_total' => 10000, 'status' => 'fulfilled',
        ]);

        return EsimProvisioning::create([
            'shop_order_item_id' => $item->id,
            'provider' => 'manual',
            'status' => 'ready',
            'sm_dp_address' => 'rsp.example.com',
            'activation_code' => 'ABCD1234',
            'lpa_string' => 'LPA:1$rsp.example.com$ABCD1234',
        ]);
    }

    public function test_owner_can_view_their_install_page(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $provisioning = $this->readyProvisioning($owner);

        $this->actingAs($owner)->get(route('esim.mine.show', $provisioning))
            ->assertOk()
            ->assertSee('rsp.example.com');
    }

    public function test_another_customer_cannot_view_someone_elses_install_page(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $provisioning = $this->readyProvisioning($owner);

        $this->actingAs($stranger)->get(route('esim.mine.show', $provisioning))->assertForbidden();
    }

    public function test_qr_endpoint_streams_a_png_only_to_the_owner(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $stranger = User::factory()->create(['status' => 'active']);
        $provisioning = $this->readyProvisioning($owner);

        $this->actingAs($owner)->get(route('esim.mine.qr', $provisioning))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->actingAs($stranger)->get(route('esim.mine.qr', $provisioning))->assertForbidden();
    }

    public function test_viewing_the_install_page_records_a_qr_reveal(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $provisioning = $this->readyProvisioning($owner);

        $this->assertSame(0, $provisioning->fresh()->qr_reveal_count);

        $this->actingAs($owner)->get(route('esim.mine.show', $provisioning));

        $this->assertSame(1, $provisioning->fresh()->qr_reveal_count);
        $this->assertNotNull($provisioning->fresh()->first_qr_reveal_at);
    }

    public function test_pending_provisioning_has_no_qr_endpoint_access(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $provisioning = $this->readyProvisioning($owner);
        $provisioning->update(['status' => 'pending_provisioning', 'lpa_string' => null, 'sm_dp_address' => null, 'activation_code' => null]);

        $this->actingAs($owner)->get(route('esim.mine.qr', $provisioning))->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $provisioning = $this->readyProvisioning($owner);

        $this->get(route('esim.mine.show', $provisioning))->assertRedirect(route('login'));
    }
}
