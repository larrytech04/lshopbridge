<?php

namespace Tests\Feature\Navigation;

use App\Enums\KycStatus;
use App\Models\User;
use App\Notifications\SecurityAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAttentionCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_no_attention_cards_for_a_clean_account(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active', 'points' => 0]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('security alert needs your attention');
        $response->assertDontSee('referral rewards available');
    }

    public function test_dashboard_shows_a_security_alert_card_when_one_exists(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->notify(new SecurityAlert('New device sign-in', 'A new device just signed in.', requiresReview: true));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('security alert needs your attention');
    }

    public function test_dashboard_shows_a_referral_reward_card_when_points_are_available(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active', 'points' => 250]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('referral rewards available');
    }
}
