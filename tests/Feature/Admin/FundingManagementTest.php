<?php

namespace Tests\Feature\Admin;

use App\Models\FundingRequest;
use App\Models\User;
use App\Services\Funding\FundingService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    private function requester(): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->primaryWallet();

        return $user;
    }

    // --------------------------------------------------------------- access

    public function test_non_admin_cannot_view_funding_management(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.funding.index'))->assertForbidden();
    }

    public function test_admin_can_view_funding_management(): void
    {
        FundingRequest::factory()->count(2)->for($this->requester(), 'user')->create();

        $this->actingAs($this->admin())
            ->get(route('admin.funding.index'))
            ->assertOk()
            ->assertSee('China Wallet Funding');
    }

    public function test_tab_filters_by_status(): void
    {
        FundingRequest::factory()->for($this->requester(), 'user')->create(['reference' => 'PB-FND-UNDERREV', 'status' => 'manual_review']);
        FundingRequest::factory()->for($this->requester(), 'user')->completed()->create(['reference' => 'PB-FND-COMPLETE']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.funding.index', ['tab' => 'under_review']));

        $response->assertSee('PB-FND-UNDERREV')->assertDontSee('PB-FND-COMPLETE');
    }

    public function test_search_filters_by_reference(): void
    {
        FundingRequest::factory()->for($this->requester(), 'user')->create(['reference' => 'PB-FND-FINDME01']);
        FundingRequest::factory()->for($this->requester(), 'user')->create(['reference' => 'PB-FND-OTHER002']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.funding.index', ['q' => 'FINDME']));

        $response->assertSee('PB-FND-FINDME01')->assertDontSee('PB-FND-OTHER002');
    }

    // ---------------------------------------------------------------- refund

    public function test_refund_is_blocked_on_a_delivered_funding_request(): void
    {
        $user = $this->requester();
        $funding = FundingRequest::factory()->for($user, 'user')->completed()->create(['total_charged' => 50000]);

        $this->actingAs($this->admin())
            ->post(route('admin.funding.refund', $funding), ['reason' => 'Attempted refund'])
            ->assertSessionHas('error');

        $this->assertSame('funding_successful', $funding->fresh()->status->value);
        $this->assertDatabaseMissing('wallet_transactions', ['source_type' => FundingRequest::class, 'source_id' => $funding->id, 'category' => 'refund']);
    }

    public function test_refund_credits_wallet_and_marks_refunded(): void
    {
        $user = $this->requester();
        $funding = FundingRequest::factory()->for($user, 'user')->create(['status' => 'manual_review', 'total_charged' => 30000]);
        $wallet = $user->primaryWallet();

        $this->actingAs($this->admin())
            ->post(route('admin.funding.refund', $funding), ['reason' => 'Customer requested cancellation'])
            ->assertRedirect();

        $funding->refresh();
        $this->assertSame('refunded', $funding->status->value);
        $this->assertEqualsWithDelta(30000.0, (float) $wallet->fresh()->balance, 0.001);
        $this->assertDatabaseHas('funding_events', ['funding_request_id' => $funding->id, 'event' => 'refund_completed']);
    }

    public function test_refund_cannot_happen_twice(): void
    {
        $user = $this->requester();
        $funding = FundingRequest::factory()->for($user, 'user')->create(['status' => 'manual_review', 'total_charged' => 15000]);
        $wallet = $user->primaryWallet();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.funding.refund', $funding), ['reason' => 'First']);
        $this->actingAs($admin)->post(route('admin.funding.refund', $funding->fresh()), ['reason' => 'Second attempt']);

        $this->assertEqualsWithDelta(15000.0, (float) $wallet->fresh()->balance, 0.001);
        $this->assertSame(1, \App\Models\WalletTransaction::where('source_type', FundingRequest::class)->where('source_id', $funding->id)->where('category', 'refund')->count());
    }

    // ------------------------------------------------------------- markFailed

    public function test_mark_failed_refunds_wallet_and_transitions_status(): void
    {
        $user = $this->requester();
        $funding = FundingRequest::factory()->for($user, 'user')->create(['status' => 'funding_processing', 'total_charged' => 20000]);
        $wallet = $user->primaryWallet();

        $this->actingAs($this->admin())
            ->post(route('admin.funding.mark-failed', $funding), ['reason' => 'Provider rejected the payout'])
            ->assertRedirect();

        $funding->refresh();
        $this->assertSame('funding_failed', $funding->status->value);
        $this->assertEqualsWithDelta(20000.0, (float) $wallet->fresh()->balance, 0.001);
    }

    public function test_mark_failed_cannot_apply_to_a_completed_request(): void
    {
        $funding = FundingRequest::factory()->for($this->requester(), 'user')->completed()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.funding.mark-failed', $funding), ['reason' => 'Attempt'])
            ->assertSessionHas('error');

        $this->assertSame('funding_successful', $funding->fresh()->status->value);
    }

    // ----------------------------------------------------------------- cancel

    public function test_cancel_refunds_wallet_and_transitions_status(): void
    {
        $user = $this->requester();
        $funding = FundingRequest::factory()->for($user, 'user')->create(['status' => 'payment_successful', 'total_charged' => 12000]);
        $wallet = $user->primaryWallet();

        $this->actingAs($this->admin())
            ->post(route('admin.funding.cancel', $funding), ['reason' => 'Wrong recipient selected'])
            ->assertRedirect();

        $funding->refresh();
        $this->assertSame('cancelled', $funding->status->value);
        $this->assertEqualsWithDelta(12000.0, (float) $wallet->fresh()->balance, 0.001);
    }

    public function test_cancel_cannot_apply_to_a_completed_request(): void
    {
        $funding = FundingRequest::factory()->for($this->requester(), 'user')->completed()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.funding.cancel', $funding), ['reason' => 'Attempt'])
            ->assertSessionHas('error');

        $this->assertSame('funding_successful', $funding->fresh()->status->value);
    }

    // -------------------------------------------------------- completeManually

    public function test_complete_manually_is_idempotent(): void
    {
        $funding = FundingRequest::factory()->for($this->requester(), 'user')->create(['status' => 'manual_review']);
        $admin = $this->admin();

        $svc = app(FundingService::class);
        $svc->completeManually($funding->fresh(), $admin, null, 'First completion');
        $svc->completeManually($funding->fresh(), $admin, null, 'Second attempt');

        $this->assertSame(1, \App\Models\FundingEvent::where('funding_request_id', $funding->id)->where('event', 'completed')->count());
    }

    // ------------------------------------------------------------- reconciliation

    public function test_reconciliation_status_computed_for_open_request(): void
    {
        $funding = FundingRequest::factory()->for($this->requester(), 'user')->create(['status' => 'payment_pending']);

        $this->assertSame('provider_pending', app(FundingService::class)->computeReconciliationStatus($funding));
    }

    public function test_reconciliation_status_matched_for_auto_completed_request(): void
    {
        $funding = FundingRequest::factory()->for($this->requester(), 'user')->completed()->create(['processed_by' => null]);

        $this->assertSame('matched', app(FundingService::class)->computeReconciliationStatus($funding));
    }

    public function test_admin_can_manually_set_reconciliation_status(): void
    {
        $funding = FundingRequest::factory()->for($this->requester(), 'user')->completed()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.funding.reconcile', $funding), ['status' => 'matched', 'note' => 'Confirmed against provider export'])
            ->assertRedirect();

        $this->assertSame('matched', $funding->fresh()->reconciliation_status);
    }

    // -------------------------------------------------------------- duplicates

    public function test_duplicate_detection_flags_same_provider_reference(): void
    {
        $existing = FundingRequest::factory()->for($this->requester(), 'user')->create(['provider_reference' => 'ALI-DUP-001']);
        $incoming = FundingRequest::factory()->for($this->requester(), 'user')->create(['provider_reference' => 'ALI-DUP-001']);

        $duplicates = app(FundingService::class)->findDuplicates($incoming);

        $this->assertNotEmpty($duplicates);
        $this->assertSame($existing->id, $duplicates[0]['funding_request_id']);
    }

    // ---------------------------------------------------------------- bulk

    public function test_bulk_action_does_not_allow_completion(): void
    {
        $funding = FundingRequest::factory()->for($this->requester(), 'user')->create(['status' => 'manual_review']);

        $this->actingAs($this->admin())
            ->post(route('admin.funding.bulk-action'), ['action' => 'complete', 'ids' => [$funding->id]])
            ->assertSessionHasErrors('action');

        $this->assertSame('manual_review', $funding->fresh()->status->value);
    }

    public function test_bulk_investigate_flags_all_selected_requests(): void
    {
        $one = FundingRequest::factory()->for($this->requester(), 'user')->create();
        $two = FundingRequest::factory()->for($this->requester(), 'user')->create();

        $this->actingAs($this->admin())
            ->post(route('admin.funding.bulk-action'), ['action' => 'investigate', 'ids' => [$one->id, $two->id]])
            ->assertRedirect();

        $this->assertTrue((bool) $one->fresh()->flagged_for_investigation);
        $this->assertTrue((bool) $two->fresh()->flagged_for_investigation);
    }

    // ----------------------------------------------------------- other actions

    public function test_assign_sets_reviewer(): void
    {
        $funding = FundingRequest::factory()->for($this->requester(), 'user')->create();
        $reviewer = $this->admin();

        $this->actingAs($this->admin())
            ->post(route('admin.funding.assign', $funding), ['reviewer_id' => $reviewer->id])
            ->assertRedirect();

        $this->assertSame($reviewer->id, $funding->fresh()->assigned_to);
    }

    public function test_add_note_stores_note_and_event(): void
    {
        $funding = FundingRequest::factory()->for($this->requester(), 'user')->create();

        $this->actingAs($this->admin())
            ->post(route('admin.funding.notes', $funding), ['note' => 'Called customer to confirm recipient.'])
            ->assertRedirect();

        $this->assertSame('Called customer to confirm recipient.', $funding->fresh()->admin_notes);
        $this->assertDatabaseHas('funding_events', ['funding_request_id' => $funding->id, 'event' => 'note_added']);
    }
}
