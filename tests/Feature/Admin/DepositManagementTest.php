<?php

namespace Tests\Feature\Admin;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\User;
use App\Services\Deposit\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function depositor(): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->primaryWallet();

        return $user;
    }

    // --------------------------------------------------------------- access

    public function test_non_admin_cannot_view_deposit_management(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.deposits.index'))->assertForbidden();
    }

    public function test_admin_can_view_deposit_management(): void
    {
        Deposit::factory()->count(2)->for($this->depositor(), 'user')->create();

        $this->actingAs($this->admin())
            ->get(route('admin.deposits.index'))
            ->assertOk()
            ->assertSee('Deposit Management');
    }

    public function test_tab_filters_by_status(): void
    {
        Deposit::factory()->for($this->depositor(), 'user')->create(['reference' => 'PB-DEP-PENDINGX', 'status' => 'pending']);
        Deposit::factory()->for($this->depositor(), 'user')->create(['reference' => 'PB-DEP-CONFIRMED', 'status' => 'confirmed']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.deposits.index', ['tab' => 'pending']));

        $response->assertSee('PB-DEP-PENDINGX')->assertDontSee('PB-DEP-CONFIRMED');
    }

    public function test_search_filters_by_reference(): void
    {
        Deposit::factory()->for($this->depositor(), 'user')->create(['reference' => 'PB-DEP-FINDME01']);
        Deposit::factory()->for($this->depositor(), 'user')->create(['reference' => 'PB-DEP-OTHER002']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.deposits.index', ['q' => 'FINDME']));

        $response->assertSee('PB-DEP-FINDME01')->assertDontSee('PB-DEP-OTHER002');
    }

    // ------------------------------------------------------- confirm & credit

    public function test_confirm_credits_wallet_and_marks_confirmed(): void
    {
        $user = $this->depositor();
        $deposit = Deposit::factory()->for($user, 'user')->create(['status' => 'pending', 'net_amount' => 5000]);
        $wallet = $user->primaryWallet();
        $startingBalance = (float) $wallet->balance;

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.confirm', $deposit))
            ->assertRedirect();

        $deposit->refresh();
        $this->assertSame('confirmed', $deposit->status->value);
        $this->assertNotNull($deposit->confirmed_at);
        $this->assertEqualsWithDelta($startingBalance + 5000, (float) $wallet->fresh()->balance, 0.001);
        $this->assertDatabaseHas('wallet_transactions', ['source_type' => Deposit::class, 'source_id' => $deposit->id, 'type' => 'credit']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deposit.confirmed', 'auditable_id' => $deposit->id]);
        $this->assertDatabaseHas('deposit_events', ['deposit_id' => $deposit->id, 'event' => 'confirmed', 'to_status' => 'confirmed']);
    }

    public function test_confirm_is_idempotent_and_never_double_credits(): void
    {
        $user = $this->depositor();
        $deposit = Deposit::factory()->for($user, 'user')->create(['status' => 'pending', 'net_amount' => 3000]);
        $wallet = $user->primaryWallet();

        $svc = app(DepositService::class);
        $svc->confirm($deposit->fresh(), $this->admin());
        $svc->confirm($deposit->fresh(), $this->admin());
        $svc->confirm($deposit->fresh(), $this->admin());

        $this->assertEqualsWithDelta(3000.0, (float) $wallet->fresh()->balance, 0.001);
        $this->assertSame(1, \App\Models\WalletTransaction::where('source_type', Deposit::class)->where('source_id', $deposit->id)->count());
    }

    public function test_confirm_route_is_idempotent_when_called_twice(): void
    {
        $user = $this->depositor();
        $deposit = Deposit::factory()->for($user, 'user')->create(['status' => 'pending', 'net_amount' => 4000]);
        $wallet = $user->primaryWallet();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.deposits.confirm', $deposit));
        $this->actingAs($admin)->post(route('admin.deposits.confirm', $deposit));

        $this->assertEqualsWithDelta(4000.0, (float) $wallet->fresh()->balance, 0.001);
        $this->assertSame(1, \App\Models\WalletTransaction::where('source_type', Deposit::class)->where('source_id', $deposit->id)->count());
    }

    // ------------------------------------------------------------------ reject

    public function test_reject_requires_a_reason(): void
    {
        $deposit = Deposit::factory()->for($this->depositor(), 'user')->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.reject', $deposit), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $deposit->fresh()->status->value);
    }

    public function test_reject_does_not_touch_the_wallet(): void
    {
        $user = $this->depositor();
        $deposit = Deposit::factory()->for($user, 'user')->create(['status' => 'pending', 'net_amount' => 2000]);
        $wallet = $user->primaryWallet();
        $startingBalance = (float) $wallet->balance;

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.reject', $deposit), ['reason' => 'Proof unreadable']);

        $this->assertSame('rejected', $deposit->fresh()->status->value);
        $this->assertEqualsWithDelta($startingBalance, (float) $wallet->fresh()->balance, 0.001);
    }

    // -------------------------------------------------------- refund & reverse

    public function test_refund_only_allowed_on_confirmed_deposit(): void
    {
        $deposit = Deposit::factory()->for($this->depositor(), 'user')->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.refund', $deposit), ['reason' => 'Customer requested refund']);

        $this->assertSame('pending', $deposit->fresh()->status->value);
    }

    public function test_refund_debits_wallet_and_marks_refunded(): void
    {
        $user = $this->depositor();
        $deposit = Deposit::factory()->for($user, 'user')->confirmed()->create(['net_amount' => 5000]);
        $wallet = $user->primaryWallet();
        app(\App\Services\Wallet\WalletService::class)->credit($wallet, 5000, 'deposit', $deposit, 'seed');

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.refund', $deposit), ['reason' => 'Customer requested refund'])
            ->assertRedirect();

        $deposit->refresh();
        $this->assertSame('refunded', $deposit->status->value);
        $this->assertNotNull($deposit->refunded_at);
        $this->assertEqualsWithDelta(0.0, (float) $wallet->fresh()->balance, 0.001);
    }

    public function test_refund_cannot_happen_twice(): void
    {
        $user = $this->depositor();
        $deposit = Deposit::factory()->for($user, 'user')->confirmed()->create(['net_amount' => 5000]);
        $wallet = $user->primaryWallet();
        app(\App\Services\Wallet\WalletService::class)->credit($wallet, 5000, 'deposit', $deposit, 'seed');

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.deposits.refund', $deposit), ['reason' => 'First refund']);
        $this->actingAs($admin)->post(route('admin.deposits.refund', $deposit->fresh()), ['reason' => 'Second attempt']);

        $this->assertEqualsWithDelta(0.0, (float) $wallet->fresh()->balance, 0.001);
        $this->assertSame(1, \App\Models\WalletTransaction::where('source_type', Deposit::class)->where('source_id', $deposit->id)->where('type', 'debit')->count());
    }

    public function test_refund_is_blocked_when_wallet_balance_is_insufficient(): void
    {
        $user = $this->depositor();
        $deposit = Deposit::factory()->for($user, 'user')->confirmed()->create(['net_amount' => 5000]);
        $wallet = $user->primaryWallet();
        // Customer already spent the credited funds — balance is now lower than the deposit amount.
        app(\App\Services\Wallet\WalletService::class)->credit($wallet, 1000, 'deposit', $deposit, 'seed');

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.refund', $deposit), ['reason' => 'Refund attempt'])
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $deposit->fresh()->status->value);
        $this->assertEqualsWithDelta(1000.0, (float) $wallet->fresh()->balance, 0.001);
    }

    public function test_reverse_debits_wallet_and_marks_reversed(): void
    {
        $user = $this->depositor();
        $deposit = Deposit::factory()->for($user, 'user')->confirmed()->create(['net_amount' => 2500]);
        $wallet = $user->primaryWallet();
        app(\App\Services\Wallet\WalletService::class)->credit($wallet, 2500, 'deposit', $deposit, 'seed');

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.reverse', $deposit), ['reason' => 'Provider chargeback'])
            ->assertRedirect();

        $deposit->refresh();
        $this->assertSame('reversed', $deposit->status->value);
        $this->assertEqualsWithDelta(0.0, (float) $wallet->fresh()->balance, 0.001);
    }

    // ------------------------------------------------------------- reconciliation

    public function test_reconciliation_status_computed_for_manual_confirmed_deposit(): void
    {
        $deposit = Deposit::factory()->for($this->depositor(), 'user')->create(['status' => 'confirmed', 'is_automated' => false]);

        $this->assertSame('manually_reconciled', app(DepositService::class)->computeReconciliationStatus($deposit));
    }

    public function test_reconciliation_status_pending_for_open_deposit(): void
    {
        $deposit = Deposit::factory()->for($this->depositor(), 'user')->create(['status' => 'pending']);

        $this->assertSame('provider_pending', app(DepositService::class)->computeReconciliationStatus($deposit));
    }

    public function test_admin_can_manually_set_reconciliation_status(): void
    {
        $deposit = Deposit::factory()->for($this->depositor(), 'user')->confirmed()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.reconcile', $deposit), ['status' => 'matched', 'note' => 'Confirmed against MTN statement'])
            ->assertRedirect();

        $this->assertSame('matched', $deposit->fresh()->reconciliation_status);
    }

    // -------------------------------------------------------------- duplicates

    public function test_duplicate_detection_flags_same_provider_reference(): void
    {
        $existing = Deposit::factory()->for($this->depositor(), 'user')->create(['provider_reference' => 'PROV-DUP-001']);
        $incoming = Deposit::factory()->for($this->depositor(), 'user')->create(['provider_reference' => 'PROV-DUP-001']);

        $duplicates = app(DepositService::class)->findDuplicates($incoming);

        $this->assertNotEmpty($duplicates);
        $this->assertSame($existing->id, $duplicates[0]['deposit_id']);
    }

    public function test_duplicate_submission_does_not_auto_reject(): void
    {
        Deposit::factory()->for($this->depositor(), 'user')->create(['provider_reference' => 'PROV-DUP-002']);
        $incoming = Deposit::factory()->for($this->depositor(), 'user')->create(['provider_reference' => 'PROV-DUP-002', 'status' => 'pending']);

        $this->assertSame('pending', $incoming->fresh()->status->value);
    }

    // ---------------------------------------------------------------- bulk

    public function test_bulk_action_does_not_allow_confirmation(): void
    {
        $deposit = Deposit::factory()->for($this->depositor(), 'user')->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.bulk-action'), ['action' => 'confirm', 'ids' => [$deposit->id]])
            ->assertSessionHasErrors('action');

        $this->assertSame('pending', $deposit->fresh()->status->value);
    }

    public function test_bulk_investigate_flags_all_selected_deposits(): void
    {
        $one = Deposit::factory()->for($this->depositor(), 'user')->create();
        $two = Deposit::factory()->for($this->depositor(), 'user')->create();

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.bulk-action'), ['action' => 'investigate', 'ids' => [$one->id, $two->id]])
            ->assertRedirect();

        $this->assertTrue((bool) $one->fresh()->flagged_for_investigation);
        $this->assertTrue((bool) $two->fresh()->flagged_for_investigation);
    }

    // ----------------------------------------------------------- other actions

    public function test_place_under_review_transitions_status(): void
    {
        $deposit = Deposit::factory()->for($this->depositor(), 'user')->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.under-review', $deposit))
            ->assertRedirect();

        $this->assertSame('under_review', $deposit->fresh()->status->value);
    }

    public function test_assign_sets_reviewer(): void
    {
        $deposit = Deposit::factory()->for($this->depositor(), 'user')->create();
        $reviewer = $this->admin();

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.assign', $deposit), ['reviewer_id' => $reviewer->id])
            ->assertRedirect();

        $this->assertSame($reviewer->id, $deposit->fresh()->assigned_to);
    }

    public function test_add_note_stores_note_and_event(): void
    {
        $deposit = Deposit::factory()->for($this->depositor(), 'user')->create();

        $this->actingAs($this->admin())
            ->post(route('admin.deposits.notes', $deposit), ['note' => 'Called customer, awaiting response.'])
            ->assertRedirect();

        $this->assertSame('Called customer, awaiting response.', $deposit->fresh()->admin_notes);
        $this->assertDatabaseHas('deposit_events', ['deposit_id' => $deposit->id, 'event' => 'note_added']);
    }
}
