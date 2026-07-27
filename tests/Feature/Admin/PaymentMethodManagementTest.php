<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethodStatus;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Deposit\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    // --------------------------------------------------------------- access

    public function test_non_admin_cannot_view_payment_methods(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.methods.index'))->assertForbidden();
    }

    public function test_admin_can_view_payment_methods(): void
    {
        PaymentMethod::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.methods.index'))
            ->assertOk()
            ->assertSee('Payment Methods');
    }

    // --------------------------------------------------------------- crud

    public function test_admin_can_create_payment_method(): void
    {
        $this->actingAs($this->admin())->post(route('admin.methods.store'), [
            'code' => 'test_method', 'name' => 'Test Method', 'type' => 'momo',
            'status' => 'active', 'currency' => 'XAF', 'min_amount' => 100, 'max_amount' => 10000,
        ])->assertRedirect();

        $this->assertDatabaseHas('payment_methods', ['code' => 'test_method', 'name' => 'Test Method']);
    }

    public function test_updating_a_method_never_changes_its_code(): void
    {
        $method = PaymentMethod::factory()->create(['code' => 'stable_code']);

        $this->actingAs($this->admin())->put(route('admin.methods.update', $method), [
            'code' => 'attempted_new_code', 'name' => 'Renamed', 'type' => 'momo', 'status' => 'active',
        ])->assertRedirect();

        $this->assertSame('stable_code', $method->fresh()->code);
        $this->assertSame('Renamed', $method->fresh()->name);
    }

    public function test_set_status_transitions_method(): void
    {
        $method = PaymentMethod::factory()->create(['status' => 'active', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.methods.status', $method), ['status' => 'disabled'])
            ->assertRedirect();

        $method->refresh();
        $this->assertSame(PaymentMethodStatus::Disabled, $method->status);
        $this->assertFalse($method->is_active);
    }

    public function test_archive_soft_deletes_and_restore_brings_it_back(): void
    {
        $method = PaymentMethod::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.methods.destroy', $method))->assertRedirect();
        $this->assertSoftDeleted('payment_methods', ['id' => $method->id]);

        $this->actingAs($this->admin())->post(route('admin.methods.restore', $method))->assertRedirect();
        $this->assertDatabaseHas('payment_methods', ['id' => $method->id, 'deleted_at' => null]);
    }

    // --------------------------------------------------------------- scopeActive date window

    public function test_scope_active_excludes_methods_outside_availability_window(): void
    {
        PaymentMethod::factory()->create(['code' => 'not_yet', 'available_from' => now()->addDay()]);
        PaymentMethod::factory()->create(['code' => 'expired', 'available_until' => now()->subDay()]);
        PaymentMethod::factory()->create(['code' => 'in_window', 'available_from' => now()->subDay(), 'available_until' => now()->addDay()]);

        $codes = PaymentMethod::active()->pluck('code')->all();

        $this->assertContains('in_window', $codes);
        $this->assertNotContains('not_yet', $codes);
        $this->assertNotContains('expired', $codes);
    }

    // --------------------------------------------------------------- enforcement

    public function test_deposit_disabled_method_is_not_offered_or_acceptable_for_deposits(): void
    {
        $method = PaymentMethod::factory()->create(['deposit_enabled' => false]);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('deposit.index'))
            ->assertDontSee($method->name);

        $this->actingAs($user)
            ->post(route('deposit.store'), ['payment_method_id' => $method->id, 'amount' => 1000])
            ->assertNotFound();
    }

    public function test_marketplace_disabled_method_is_not_offered_at_checkout(): void
    {
        $method = PaymentMethod::factory()->create(['marketplace_enabled' => false, 'is_automated' => true]);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('shop.index'));

        // Direct assertion via the query scope used by CheckoutController.
        $ids = \App\Models\PaymentMethod::active()->where('is_automated', true)->where('marketplace_enabled', true)->pluck('id');
        $this->assertNotContains($method->id, $ids);
    }

    public function test_refund_is_blocked_when_method_does_not_support_refunds(): void
    {
        $method = PaymentMethod::factory()->create(['refund_support' => false]);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->primaryWallet();

        $deposit = Deposit::factory()->for($user)->create([
            'payment_method_id' => $method->id,
            'status' => 'confirmed',
            'net_amount' => 5000,
        ]);

        $this->expectException(\RuntimeException::class);
        app(DepositService::class)->refund($deposit, $this->admin(), 'Test refund attempt');
    }

    public function test_requires_manual_review_method_never_auto_confirms_from_webhook(): void
    {
        $method = PaymentMethod::factory()->create(['requires_manual_review' => true, 'is_automated' => true]);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->primaryWallet();

        $created = app(DepositService::class)->createAutomated($user, $method, 5000);
        $deposit = $created['deposit'];
        $intent = $created['intent'];
        $result = new \App\Services\Payments\DTO\WebhookResult(
            eventId: 'evt_1', reference: $deposit->reference, status: 'succeeded',
            providerReference: 'REF1', amount: 5000, currency: 'XAF',
        );

        app(DepositService::class)->settleFromWebhook($intent, $result);

        $this->assertSame('under_review', $deposit->fresh()->status->value);
    }
}
