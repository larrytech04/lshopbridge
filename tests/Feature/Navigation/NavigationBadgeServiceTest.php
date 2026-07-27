<?php

namespace Tests\Feature\Navigation;

use App\Enums\KycStatus;
use App\Models\Dispute;
use App\Models\User;
use App\Services\Navigation\NavigationBadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationBadgeServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): NavigationBadgeService
    {
        return app(NavigationBadgeService::class);
    }

    public function test_verification_action_required_is_true_until_kyc_is_approved(): void
    {
        $user = User::factory()->create(['kyc_status' => KycStatus::Unverified]);

        $this->assertTrue($this->service()->forUser($user)['verification_action_required']);

        $user->update(['kyc_status' => KycStatus::Approved]);

        $this->assertFalse($this->service()->forUser($user->fresh())['verification_action_required']);
    }

    public function test_referral_reward_available_reflects_points_balance(): void
    {
        $withPoints = User::factory()->create(['points' => 500]);
        $withoutPoints = User::factory()->create(['points' => 0]);

        $this->assertTrue($this->service()->forUser($withPoints)['referral_reward_available']);
        $this->assertFalse($this->service()->forUser($withoutPoints)['referral_reward_available']);
    }

    private function makeDispute(User $user, string $reference): Dispute
    {
        return Dispute::create([
            'reference' => $reference,
            'user_id' => $user->id,
            'subject' => 'Test dispute',
            'description' => 'Test dispute description',
            'status' => 'open',
        ]);
    }

    public function test_support_awaiting_you_counts_only_open_disputes_where_staff_replied_last(): void
    {
        $user = User::factory()->create();

        $awaitingCustomer = $this->makeDispute($user, 'PB-DSP-0001');
        $awaitingCustomer->messages()->create(['user_id' => $user->id, 'message' => 'Staff replied', 'is_staff' => true]);

        $awaitingStaff = $this->makeDispute($user, 'PB-DSP-0002');
        $awaitingStaff->messages()->create(['user_id' => $user->id, 'message' => 'Customer replied', 'is_staff' => false]);

        $badges = $this->service()->forUser($user);

        $this->assertSame(1, $badges['support_awaiting_you']);
    }

    public function test_notifications_badge_matches_unread_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new \App\Notifications\SecurityAlert('New device sign-in', 'A new device just signed in.'));

        $badges = $this->service()->forUser($user->fresh());

        $this->assertSame(1, $badges['notifications']);
        $this->assertTrue($badges['security_alert']);
    }
}
