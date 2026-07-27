<?php

namespace Tests\Feature\Navigation;

use App\Models\KycLevel;
use App\Models\PaymentMethod;
use App\Models\SavedPaymentMethod;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawFundsTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $attrs = []): User
    {
        $user = User::factory()->create($attrs + ['role' => 'user', 'status' => 'active', 'kyc_level' => 1]);
        $user->primaryWallet()->update(['balance' => 100000]);

        KycLevel::updateOrCreate(['level' => 1], [
            'name' => 'Level 1', 'is_active' => true, 'currency' => 'XAF',
            'per_transaction_limit' => 500000, 'daily_limit' => 1000000, 'monthly_limit' => 5000000,
        ]);

        return $user;
    }

    private function destination(User $user): SavedPaymentMethod
    {
        $method = PaymentMethod::create(['code' => 'orange_'.uniqid(), 'name' => 'Orange Money', 'type' => 'momo', 'is_active' => true, 'deposit_enabled' => true]);

        return SavedPaymentMethod::create(['user_id' => $user->id, 'payment_method_id' => $method->id, 'label' => 'My Orange', 'account_ref' => '677123456', 'is_default' => true]);
    }

    public function test_guest_cannot_access_withdrawals(): void
    {
        $this->get(route('withdrawals.index'))->assertRedirect(route('login'));
    }

    public function test_customer_without_a_pin_cannot_request_a_withdrawal(): void
    {
        $user = $this->customer();
        $dest = $this->destination($user);

        $response = $this->actingAs($user)->post(route('withdrawals.store'), [
            'amount' => 10000, 'saved_payment_method_id' => $dest->id, 'pin' => '1234',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertDatabaseCount('withdrawal_requests', 0);
    }

    public function test_customer_with_wrong_pin_is_rejected(): void
    {
        $user = $this->customer(['transaction_pin' => '1234']);
        $dest = $this->destination($user);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'amount' => 10000, 'saved_payment_method_id' => $dest->id, 'pin' => '9999',
        ])->assertSessionHasErrors('pin');

        $this->assertDatabaseCount('withdrawal_requests', 0);
    }

    public function test_customer_can_request_a_withdrawal_which_holds_the_funds(): void
    {
        $user = $this->customer(['transaction_pin' => '1234']);
        $dest = $this->destination($user);

        $response = $this->actingAs($user)->post(route('withdrawals.store'), [
            'amount' => 10000, 'saved_payment_method_id' => $dest->id, 'pin' => '1234',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('withdrawal_requests', ['user_id' => $user->id, 'amount' => 10000, 'status' => 'pending']);

        $wallet = $user->primaryWallet()->fresh();
        $this->assertEqualsWithDelta(10000, (float) $wallet->locked_balance, 0.01);
        $this->assertEqualsWithDelta(90000, $wallet->availableBalance(), 0.01);
        // Balance itself hasn't moved yet — only reserved.
        $this->assertEqualsWithDelta(100000, (float) $wallet->balance, 0.01);
    }

    public function test_customer_cannot_withdraw_more_than_available_balance(): void
    {
        $user = $this->customer(['transaction_pin' => '1234']);
        $dest = $this->destination($user);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'amount' => 999999, 'saved_payment_method_id' => $dest->id, 'pin' => '1234',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('withdrawal_requests', 0);
    }

    public function test_customer_cannot_use_someone_elses_saved_payment_method(): void
    {
        $user = $this->customer(['transaction_pin' => '1234']);
        $other = $this->customer(['transaction_pin' => '1234']);
        $theirDest = $this->destination($other);

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'amount' => 5000, 'saved_payment_method_id' => $theirDest->id, 'pin' => '1234',
        ])->assertForbidden();
    }

    public function test_customer_can_cancel_a_pending_withdrawal_and_funds_are_released(): void
    {
        $user = $this->customer(['transaction_pin' => '1234']);
        $dest = $this->destination($user);
        $this->actingAs($user)->post(route('withdrawals.store'), ['amount' => 10000, 'saved_payment_method_id' => $dest->id, 'pin' => '1234']);
        $withdrawal = WithdrawalRequest::first();

        $this->actingAs($user)->post(route('withdrawals.cancel', $withdrawal))->assertRedirect();

        $this->assertSame('cancelled', $withdrawal->fresh()->status->value);
        $this->assertEqualsWithDelta(0, (float) $user->primaryWallet()->fresh()->locked_balance, 0.01);
    }

    public function test_admin_approve_then_mark_paid_debits_the_wallet(): void
    {
        $user = $this->customer(['transaction_pin' => '1234']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $dest = $this->destination($user);
        $this->actingAs($user)->post(route('withdrawals.store'), ['amount' => 10000, 'saved_payment_method_id' => $dest->id, 'pin' => '1234']);
        $withdrawal = WithdrawalRequest::first();

        $this->actingAs($admin)->post(route('admin.withdrawals.approve', $withdrawal))->assertRedirect();
        $this->assertSame('approved', $withdrawal->fresh()->status->value);

        $this->actingAs($admin)->post(route('admin.withdrawals.mark-paid', $withdrawal), ['payout_reference' => 'MOMO-REF-1'])->assertRedirect();

        $withdrawal->refresh();
        $this->assertSame('paid', $withdrawal->status->value);
        $this->assertSame('MOMO-REF-1', $withdrawal->payout_reference);

        $wallet = $user->primaryWallet()->fresh();
        $this->assertEqualsWithDelta(90000, (float) $wallet->balance, 0.01);
        $this->assertEqualsWithDelta(0, (float) $wallet->locked_balance, 0.01);
    }

    public function test_admin_reject_releases_the_hold_without_debiting(): void
    {
        $user = $this->customer(['transaction_pin' => '1234']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $dest = $this->destination($user);
        $this->actingAs($user)->post(route('withdrawals.store'), ['amount' => 10000, 'saved_payment_method_id' => $dest->id, 'pin' => '1234']);
        $withdrawal = WithdrawalRequest::first();

        $this->actingAs($admin)->post(route('admin.withdrawals.reject', $withdrawal), ['reason' => 'Suspicious activity'])->assertRedirect();

        $withdrawal->refresh();
        $this->assertSame('rejected', $withdrawal->status->value);
        $wallet = $user->primaryWallet()->fresh();
        $this->assertEqualsWithDelta(100000, (float) $wallet->balance, 0.01);
        $this->assertEqualsWithDelta(0, (float) $wallet->locked_balance, 0.01);
    }

    public function test_non_admin_cannot_access_admin_withdrawal_actions(): void
    {
        $user = $this->customer(['transaction_pin' => '1234']);
        $dest = $this->destination($user);
        $this->actingAs($user)->post(route('withdrawals.store'), ['amount' => 10000, 'saved_payment_method_id' => $dest->id, 'pin' => '1234']);
        $withdrawal = WithdrawalRequest::first();

        $this->actingAs($user)->post(route('admin.withdrawals.approve', $withdrawal))->assertForbidden();
    }
}
