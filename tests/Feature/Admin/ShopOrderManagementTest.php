<?php

namespace Tests\Feature\Admin;

use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    private function orderWithWallet(array $attrs = []): ShopOrder
    {
        $user = User::factory()->create(['status' => 'active']);
        $wallet = $user->primaryWallet('XAF');
        $wallet->update(['balance' => 100000]);

        return ShopOrder::factory()->for($user, 'user')->create($attrs + ['currency' => 'XAF']);
    }

    public function test_non_admin_cannot_view_orders(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.shop.orders.index'))->assertForbidden();
    }

    public function test_admin_can_view_orders(): void
    {
        $this->orderWithWallet();

        $this->actingAs($this->admin())
            ->get(route('admin.shop.orders.index'))
            ->assertOk()
            ->assertSee('Shop Orders');
    }

    public function test_start_processing_transitions_a_paid_order(): void
    {
        $order = $this->orderWithWallet(['status' => 'paid']);

        $this->actingAs($this->admin())->post(route('admin.shop.orders.start-processing', $order));

        $this->assertDatabaseHas('shop_orders', ['id' => $order->id, 'status' => 'processing']);
        $this->assertDatabaseHas('shop_order_events', ['shop_order_id' => $order->id, 'event' => 'processing_started']);
    }

    public function test_mark_shipped_records_tracking_and_transitions_status(): void
    {
        $order = $this->orderWithWallet(['status' => 'processing']);

        $this->actingAs($this->admin())->post(route('admin.shop.orders.mark-shipped', $order), [
            'tracking_number' => 'TRK123', 'carrier' => 'DHL',
        ]);

        $this->assertDatabaseHas('shop_orders', ['id' => $order->id, 'status' => 'shipped', 'tracking_number' => 'TRK123', 'carrier' => 'DHL']);
    }

    public function test_cancel_refunds_wallet_and_transitions_status(): void
    {
        $order = $this->orderWithWallet(['status' => 'paid', 'total' => 5000]);
        $walletBefore = $order->user->primaryWallet('XAF')->balance;

        $this->actingAs($this->admin())->post(route('admin.shop.orders.cancel', $order), ['reason' => 'Customer request']);

        $this->assertDatabaseHas('shop_orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertEqualsWithDelta((float) $walletBefore + 5000, (float) $order->user->primaryWallet('XAF')->fresh()->balance, 0.01);
    }

    public function test_cancel_cannot_apply_to_a_settled_order(): void
    {
        $order = $this->orderWithWallet(['status' => 'delivered']);

        $this->actingAs($this->admin())->post(route('admin.shop.orders.cancel', $order), ['reason' => 'Too late'])
            ->assertSessionHasErrors('status');
    }

    public function test_partial_refund_credits_wallet_and_marks_partially_refunded(): void
    {
        $order = $this->orderWithWallet(['status' => 'paid', 'total' => 10000]);

        $this->actingAs($this->admin())->post(route('admin.shop.orders.refund', $order), [
            'amount' => 4000, 'reason' => 'Partial issue',
        ]);

        $this->assertDatabaseHas('shop_orders', ['id' => $order->id, 'status' => 'partially_refunded']);
        $this->assertDatabaseHas('shop_refunds', ['shop_order_id' => $order->id, 'amount' => 4000]);
    }

    public function test_refunding_the_remaining_balance_marks_fully_refunded(): void
    {
        $order = $this->orderWithWallet(['status' => 'paid', 'total' => 10000]);

        $this->actingAs($this->admin())->post(route('admin.shop.orders.refund', $order), ['amount' => 4000, 'reason' => 'First']);
        $this->actingAs($this->admin())->post(route('admin.shop.orders.refund', $order), ['amount' => 6000, 'reason' => 'Second']);

        $this->assertDatabaseHas('shop_orders', ['id' => $order->id, 'status' => 'refunded']);
    }

    public function test_cannot_refund_more_than_the_refundable_balance(): void
    {
        $order = $this->orderWithWallet(['status' => 'paid', 'total' => 5000]);

        $this->actingAs($this->admin())->post(route('admin.shop.orders.refund', $order), ['amount' => 4000, 'reason' => 'First'])
            ->assertSessionDoesntHaveErrors();

        $response = $this->actingAs($this->admin())->post(route('admin.shop.orders.refund', $order), ['amount' => 2000, 'reason' => 'Too much']);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('shop_refunds', 1);
    }

    public function test_cannot_double_refund_the_same_amount(): void
    {
        $order = $this->orderWithWallet(['status' => 'paid', 'total' => 5000]);

        $this->actingAs($this->admin())->post(route('admin.shop.orders.refund', $order), ['amount' => 5000, 'reason' => 'Full']);
        $response = $this->actingAs($this->admin())->post(route('admin.shop.orders.refund', $order), ['amount' => 5000, 'reason' => 'Again']);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('shop_refunds', 1);
    }

    public function test_add_note_appends_to_admin_notes(): void
    {
        $order = $this->orderWithWallet();

        $response = $this->actingAs($this->admin())->post(route('admin.shop.orders.notes', $order), ['note' => 'Called customer']);

        $response->assertRedirect();
        $this->assertStringContainsString('Called customer', $order->fresh()->admin_notes);
    }

    public function test_assign_sets_assigned_staff(): void
    {
        $order = $this->orderWithWallet();
        $staff = $this->admin();

        $response = $this->actingAs($this->admin())->post(route('admin.shop.orders.assign', $order), ['staff_id' => $staff->id]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shop_orders', ['id' => $order->id, 'assigned_to' => $staff->id]);
    }

    public function test_resend_delivery_succeeds_without_error(): void
    {
        $order = $this->orderWithWallet(['status' => 'fulfilled']);

        $response = $this->actingAs($this->admin())->post(route('admin.shop.orders.resend-delivery', $order));

        $response->assertRedirect();
        $this->assertDatabaseHas('shop_order_events', ['shop_order_id' => $order->id, 'event' => 'digital_delivery_resent']);
    }

    public function test_request_refund_flags_the_order_and_records_the_prior_status(): void
    {
        $order = $this->orderWithWallet(['status' => 'paid']);

        $response = $this->actingAs($this->admin())->post(route('admin.shop.orders.request-refund', $order), ['reason' => 'Customer complaint']);

        $response->assertRedirect();
        $this->assertDatabaseHas('shop_orders', ['id' => $order->id, 'status' => 'refund_requested']);
        $this->assertDatabaseHas('shop_order_events', ['shop_order_id' => $order->id, 'event' => 'refund_requested', 'from_status' => 'paid', 'to_status' => 'refund_requested']);
    }

    public function test_row_detail_returns_json_with_events_and_items(): void
    {
        $order = $this->orderWithWallet();

        $response = $this->actingAs($this->admin())->get(route('admin.shop.orders.row-detail', $order));

        $response->assertOk()->assertJsonStructure(['order' => ['reference', 'status'], 'items', 'events', 'refunds']);
    }
}
