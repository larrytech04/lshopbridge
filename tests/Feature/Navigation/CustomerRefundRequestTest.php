<?php

namespace Tests\Feature\Navigation;

use App\Models\ShopOrder;
use App\Models\ShopRefund;
use App\Models\User;
use App\Services\Admin\ShopOrderAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRefundRequestTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrder(User $user, array $attrs = []): ShopOrder
    {
        return ShopOrder::factory()->create($attrs + [
            'user_id' => $user->id,
            'status' => 'paid',
            'total' => 5000,
            'currency' => 'XAF',
            'paid_at' => now(),
        ]);
    }

    public function test_guest_cannot_access_refunds(): void
    {
        $this->get(route('refunds.index'))->assertRedirect(route('login'));
    }

    public function test_customer_can_request_a_refund_on_an_eligible_order(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $order = $this->paidOrder($user);

        $response = $this->actingAs($user)->post(route('refunds.store'), [
            'shop_order_id' => $order->id,
            'reason' => 'Item never arrived',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shop_refunds', ['shop_order_id' => $order->id, 'status' => 'requested', 'requested_by' => $user->id]);
        $this->assertSame('refund_requested', $order->fresh()->status->value);
    }

    public function test_customer_cannot_request_a_refund_on_someone_elses_order(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $attacker = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $order = $this->paidOrder($owner);

        $this->actingAs($attacker)->post(route('refunds.store'), [
            'shop_order_id' => $order->id,
            'reason' => 'Not mine but trying anyway',
        ])->assertForbidden();
    }

    public function test_customer_cannot_request_a_refund_outside_the_eligibility_window(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $order = $this->paidOrder($user, ['paid_at' => now()->subDays(30)]);

        $this->actingAs($user)->post(route('refunds.store'), [
            'shop_order_id' => $order->id,
            'reason' => 'Too late but trying',
        ])->assertStatus(422);
    }

    public function test_customer_cannot_submit_a_second_request_while_one_is_pending(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $order = $this->paidOrder($user);
        ShopRefund::create(['shop_order_id' => $order->id, 'amount' => 5000, 'currency' => 'XAF', 'reason' => 'First', 'requested_by' => $user->id, 'status' => 'requested']);

        $this->actingAs($user)->post(route('refunds.store'), [
            'shop_order_id' => $order->id,
            'reason' => 'Second attempt',
        ])->assertStatus(422);

        $this->assertSame(1, ShopRefund::where('shop_order_id', $order->id)->count());
    }

    public function test_admin_approving_a_pending_request_updates_the_same_row_instead_of_duplicating(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $order = $this->paidOrder($user);

        $this->actingAs($user)->post(route('refunds.store'), ['shop_order_id' => $order->id, 'reason' => 'Broken item']);

        app(ShopOrderAdminService::class)->refund($order->fresh(), 5000, 'Approved', $admin);

        $this->assertSame(1, ShopRefund::where('shop_order_id', $order->id)->count());
        $refund = ShopRefund::where('shop_order_id', $order->id)->first();
        $this->assertSame('completed', $refund->status);
        $this->assertSame($user->id, $refund->requested_by);
        $this->assertSame($admin->id, $refund->approved_by);
        $this->assertSame('refunded', $order->fresh()->status->value);
    }

    public function test_admin_can_reject_a_pending_refund_request(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $order = $this->paidOrder($user);

        $this->actingAs($user)->post(route('refunds.store'), ['shop_order_id' => $order->id, 'reason' => 'Changed my mind']);
        $refund = ShopRefund::where('shop_order_id', $order->id)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.shop.orders.refunds.reject', [$order, $refund]), ['reason' => 'Outside policy'])
            ->assertRedirect();

        $this->assertSame('rejected', $refund->fresh()->status);
        $this->assertSame('paid', $order->fresh()->status->value);
    }
}
