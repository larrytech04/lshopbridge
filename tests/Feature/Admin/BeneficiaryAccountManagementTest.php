<?php

namespace Tests\Feature\Admin;

use App\Models\BeneficiaryAccount;
use App\Models\User;
use App\Services\Admin\BeneficiaryReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BeneficiaryAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_non_admin_cannot_view_wallet_accounts(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.beneficiaries.index'))->assertForbidden();
    }

    public function test_admin_can_view_wallet_accounts(): void
    {
        BeneficiaryAccount::factory()->count(2)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.beneficiaries.index'))
            ->assertOk()
            ->assertSee('China Wallet Accounts');
    }

    public function test_status_tab_filters_accounts(): void
    {
        BeneficiaryAccount::factory()->create(['account_name' => 'Pending Wallet', 'status' => 'pending']);
        BeneficiaryAccount::factory()->create(['account_name' => 'Approved Wallet', 'status' => 'approved']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.beneficiaries.index', ['tab' => 'pending']));

        $response->assertSee('Pending Wallet')->assertDontSee('Approved Wallet');
    }

    public function test_search_filters_by_account_name(): void
    {
        BeneficiaryAccount::factory()->create(['account_name' => 'Kofi Mensah']);
        BeneficiaryAccount::factory()->create(['account_name' => 'Ama Owusu']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.beneficiaries.index', ['q' => 'Kofi']));

        $response->assertSee('Kofi Mensah')->assertDontSee('Ama Owusu');
    }

    public function test_approve_transitions_status_and_writes_audit_log(): void
    {
        $account = BeneficiaryAccount::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.beneficiaries.approve', $account))
            ->assertRedirect();

        $account->refresh();
        $this->assertSame('approved', $account->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.beneficiary.approved', 'auditable_id' => $account->id]);
        $this->assertDatabaseHas('beneficiary_account_events', ['beneficiary_account_id' => $account->id, 'event' => 'approved']);
    }

    public function test_reject_requires_a_reason(): void
    {
        $account = BeneficiaryAccount::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.beneficiaries.reject', $account), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $account->fresh()->status->value);
    }

    public function test_reject_with_reason_updates_status_and_category(): void
    {
        $account = BeneficiaryAccount::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.beneficiaries.reject', $account), [
                'reason' => 'QR code was blurry',
                'category' => 'unclear_qr',
                'resubmission_allowed' => '1',
            ])
            ->assertRedirect();

        $account->refresh();
        $this->assertSame('rejected', $account->status->value);
        $this->assertSame('unclear_qr', $account->rejection_category);
        $this->assertTrue($account->resubmission_allowed);
    }

    public function test_suspend_and_restore_round_trip(): void
    {
        $account = BeneficiaryAccount::factory()->approved()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.beneficiaries.suspend', $account), ['reason' => 'Suspicious activity'])
            ->assertRedirect();

        $this->assertSame('suspended', $account->fresh()->status->value);

        $this->actingAs($this->admin())
            ->post(route('admin.beneficiaries.restore', $account))
            ->assertRedirect();

        $this->assertSame('approved', $account->fresh()->status->value);
    }

    public function test_request_info_sets_status_and_notifies_user(): void
    {
        $account = BeneficiaryAccount::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.beneficiaries.request-info', $account), ['reason_key' => 'qr_unclear'])
            ->assertRedirect();

        $this->assertSame('more_info_requested', $account->fresh()->status->value);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $account->user_id]);
    }

    public function test_bulk_action_does_not_allow_approval(): void
    {
        $account = BeneficiaryAccount::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.beneficiaries.bulk-action'), [
                'action' => 'approve',
                'ids' => [$account->id],
            ])
            ->assertSessionHasErrors('action');

        $this->assertSame('pending', $account->fresh()->status->value);
    }

    public function test_bulk_suspend_updates_all_selected_accounts(): void
    {
        $one = BeneficiaryAccount::factory()->approved()->create();
        $two = BeneficiaryAccount::factory()->approved()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.beneficiaries.bulk-action'), [
                'action' => 'suspend',
                'ids' => [$one->id, $two->id],
                'reason' => 'Bulk review',
            ])
            ->assertRedirect();

        $this->assertSame('suspended', $one->fresh()->status->value);
        $this->assertSame('suspended', $two->fresh()->status->value);
    }

    public function test_archive_soft_deletes_account(): void
    {
        $account = BeneficiaryAccount::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.beneficiaries.destroy', $account), ['reason' => 'Duplicate cleanup'])
            ->assertRedirect(route('admin.beneficiaries.index'));

        $this->assertSoftDeleted('beneficiary_accounts', ['id' => $account->id]);
    }

    public function test_duplicate_detection_flags_same_account_identifier_across_users(): void
    {
        $existing = BeneficiaryAccount::factory()->create(['account_id' => 'shared@alipay.cn', 'app_type' => 'alipay']);
        $incoming = BeneficiaryAccount::factory()->create(['account_id' => 'shared@alipay.cn', 'app_type' => 'alipay']);

        $svc = app(BeneficiaryReviewService::class);
        $duplicates = $svc->findDuplicates($incoming);

        $this->assertCount(1, $duplicates);
        $this->assertSame($existing->id, $duplicates[0]['beneficiary_account_id']);
    }

    public function test_duplicate_detection_does_not_flag_own_accounts(): void
    {
        $user = User::factory()->create();
        BeneficiaryAccount::factory()->create(['user_id' => $user->id, 'account_id' => 'same@alipay.cn', 'app_type' => 'alipay', 'is_default' => true]);
        $second = BeneficiaryAccount::factory()->create(['user_id' => $user->id, 'account_id' => 'same@alipay.cn', 'app_type' => 'alipay']);

        $svc = app(BeneficiaryReviewService::class);

        $this->assertCount(0, $svc->findDuplicates($second));
    }

    public function test_duplicate_submission_does_not_auto_reject(): void
    {
        BeneficiaryAccount::factory()->create(['account_id' => 'dup@alipay.cn', 'app_type' => 'alipay']);
        $incoming = BeneficiaryAccount::factory()->create(['account_id' => 'dup@alipay.cn', 'app_type' => 'alipay', 'status' => 'pending']);

        $this->assertSame('pending', $incoming->fresh()->status->value);
    }

    public function test_qr_access_is_denied_to_unrelated_users(): void
    {
        Storage::fake('private');
        $account = BeneficiaryAccount::factory()->create(['qr_path' => 'beneficiaries/qr/test.png']);
        Storage::disk('private')->put('beneficiaries/qr/test.png', 'fake-image-content');

        $stranger = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($stranger)
            ->get(route('files.show', ['kind' => 'beneficiary-qr', 'id' => $account->id]))
            ->assertForbidden();
    }

    public function test_qr_access_is_allowed_to_owner_and_admin(): void
    {
        Storage::fake('private');
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $account = BeneficiaryAccount::factory()->create(['user_id' => $owner->id, 'qr_path' => 'beneficiaries/qr/test.png']);
        Storage::disk('private')->put('beneficiaries/qr/test.png', 'fake-image-content');

        $this->actingAs($owner)
            ->get(route('files.show', ['kind' => 'beneficiary-qr', 'id' => $account->id]))
            ->assertOk();

        $this->actingAs($this->admin())
            ->get(route('files.show', ['kind' => 'beneficiary-qr', 'id' => $account->id]))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.beneficiary.qr_viewed', 'auditable_id' => $account->id]);
    }
}
