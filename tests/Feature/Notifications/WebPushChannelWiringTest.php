<?php

namespace Tests\Feature\Notifications;

use App\Models\Deposit;
use App\Models\User;
use App\Notifications\DepositConfirmed;
use App\Notifications\ReauthCodeMail;
use App\Notifications\SecurityAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

/**
 * Spot-checks that the webpush channel is actually wired in for the
 * notifications that matter most, rather than re-testing all thirteen of
 * them individually — the channel itself (WebPushChannel::send()) is a safe
 * no-op for a user with zero subscriptions, which is what every other
 * notification's feature test already exercises without noticing.
 */
class WebPushChannelWiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_confirmed_includes_push_when_the_preference_is_on(): void
    {
        $user = User::factory()->create(['preferences' => ['notify_web_push' => true]]);
        $deposit = Deposit::factory()->for($user)->create();

        $channels = (new DepositConfirmed($deposit))->via($user);

        $this->assertContains(WebPushChannel::class, $channels);
    }

    public function test_deposit_confirmed_skips_push_when_the_preference_is_off(): void
    {
        $user = User::factory()->create(['preferences' => ['notify_web_push' => false]]);
        $deposit = Deposit::factory()->for($user)->create();

        $channels = (new DepositConfirmed($deposit))->via($user);

        $this->assertNotContains(WebPushChannel::class, $channels);
    }

    public function test_a_requires_review_security_alert_still_pushes_even_with_the_preference_off(): void
    {
        $user = User::factory()->create(['preferences' => ['notify_web_push' => false]]);
        $notification = new SecurityAlert('New device', 'Message', requiresReview: true);

        $channels = $notification->via($user);

        $this->assertContains(WebPushChannel::class, $channels);
    }

    public function test_reauth_code_mail_includes_push_when_the_preference_is_on(): void
    {
        $user = User::factory()->create(['preferences' => ['notify_web_push' => true]]);

        $channels = (new ReauthCodeMail('ABC123', 10))->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains(WebPushChannel::class, $channels);
    }

    public function test_reauth_code_mail_always_emails_regardless_of_push_preference(): void
    {
        $user = User::factory()->create(['preferences' => ['notify_web_push' => false]]);

        $channels = (new ReauthCodeMail('ABC123', 10))->via($user);

        $this->assertContains('mail', $channels);
        $this->assertNotContains(WebPushChannel::class, $channels);
    }
}
