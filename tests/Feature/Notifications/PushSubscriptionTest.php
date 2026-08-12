<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\TestPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\PushSubscription;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_subscribe(): void
    {
        $this->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
            'keys' => ['p256dh' => 'key', 'auth' => 'token'],
        ])->assertUnauthorized();
    }

    public function test_a_user_can_subscribe(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
            'keys' => ['p256dh' => 'the-p256dh-key', 'auth' => 'the-auth-token'],
        ]);

        $response->assertOk()->assertJson(['status' => 'subscribed']);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'subscribable_type' => $user->getMorphClass(),
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
        ]);
    }

    public function test_subscribing_again_with_the_same_endpoint_updates_it_instead_of_duplicating(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/abc';

        $this->actingAs($user)->postJson(route('push.subscribe'), [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'old-key', 'auth' => 'old-token'],
        ]);

        $this->actingAs($user)->postJson(route('push.subscribe'), [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'new-key', 'auth' => 'new-token'],
        ]);

        $this->assertSame(1, PushSubscription::where('endpoint', $endpoint)->count());
        $this->assertSame('new-key', PushSubscription::where('endpoint', $endpoint)->first()->public_key);
    }

    public function test_a_user_can_unsubscribe(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/abc';
        $user->updatePushSubscription($endpoint, 'key', 'token');

        $response = $this->actingAs($user)->deleteJson(route('push.unsubscribe'), ['endpoint' => $endpoint]);

        $response->assertOk()->assertJson(['status' => 'unsubscribed']);
        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $endpoint]);
    }

    public function test_a_user_cannot_delete_another_users_subscription(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $attacker = User::factory()->create(['status' => 'active']);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/victim';
        $owner->updatePushSubscription($endpoint, 'key', 'token');

        $this->actingAs($attacker)->deleteJson(route('push.unsubscribe'), ['endpoint' => $endpoint]);

        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => $endpoint]);
    }

    public function test_a_test_notification_is_rejected_without_an_active_subscription(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->postJson(route('push.test'));

        $response->assertStatus(422)->assertJson(['status' => 'no-subscription']);
        Notification::assertNothingSent();
    }

    public function test_a_test_notification_is_sent_when_a_subscription_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        $user->updatePushSubscription('https://fcm.googleapis.com/fcm/send/abc', 'key', 'token');

        $response = $this->actingAs($user)->postJson(route('push.test'));

        $response->assertOk()->assertJson(['status' => 'sent']);
        Notification::assertSentTo($user, TestPush::class);
    }
}
