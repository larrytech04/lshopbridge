<?php

namespace Tests\Feature\Admin;

use App\Enums\WebhookStatus;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookMonitorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_index_lists_events_and_offers_retry_only_for_failed_ones(): void
    {
        WebhookEvent::create(['provider_code' => 'flutterwave', 'status' => WebhookStatus::Processed]);
        $failed = WebhookEvent::create(['provider_code' => 'flutterwave', 'status' => WebhookStatus::Failed]);

        $response = $this->actingAs($this->admin())->get(route('admin.webhooks.index'));

        $response->assertOk();
        $response->assertSee(route('admin.webhooks.retry', $failed), false);
    }

    public function test_retry_is_rejected_for_a_non_failed_event(): void
    {
        $event = WebhookEvent::create(['provider_code' => 'flutterwave', 'status' => WebhookStatus::Processed]);

        $response = $this->actingAs($this->admin())->post(route('admin.webhooks.retry', $event));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, $event->fresh()->retry_count);
    }

    public function test_retry_on_a_failed_event_with_unknown_provider_still_records_the_attempt(): void
    {
        $event = WebhookEvent::create([
            'provider_code' => 'no-such-provider',
            'status' => WebhookStatus::Failed,
            'payload' => ['reference' => 'REF123'],
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.webhooks.retry', $event));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $event->refresh();
        $this->assertSame(1, $event->retry_count);
        $this->assertNotNull($event->last_retried_at);
    }
}
