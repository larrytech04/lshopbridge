<?php

namespace Tests\Feature\Public;

use App\Models\Agent;
use App\Services\Security\HoneypotValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestReviewFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_legitimate_guest_review_is_created_pending_and_marked_unverified(): void
    {
        $agent = Agent::factory()->create(['status' => 'approved']);

        $response = $this->post(route('agents.guest-review', $agent), [
            'guest_name' => 'Happy Customer',
            'guest_email' => 'happy@example.com',
            'rating' => 5,
            'comment' => 'Great service, delivered on time.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'agent_id' => $agent->id,
            'user_id' => null,
            'is_guest' => true,
            'guest_name' => 'Happy Customer',
            'status' => 'pending',
        ]);
    }

    public function test_a_guest_review_never_auto_publishes(): void
    {
        $agent = Agent::factory()->create(['status' => 'approved']);

        $this->post(route('agents.guest-review', $agent), ['rating' => 5]);

        $this->assertDatabaseMissing('reviews', ['agent_id' => $agent->id, 'status' => 'approved']);
    }

    public function test_honeypot_plus_spam_content_creates_no_review(): void
    {
        $agent = Agent::factory()->create(['status' => 'approved']);
        $honeypotField = app(HoneypotValidationService::class)->fieldName();

        $response = $this->post(route('agents.guest-review', $agent), [
            'rating' => 1,
            'comment' => 'Buy backlinks now! http://a.xyz http://b.xyz http://c.xyz guaranteed profit',
            $honeypotField => 'filled',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_rating_out_of_range_is_rejected(): void
    {
        $agent = Agent::factory()->create(['status' => 'approved']);

        $response = $this->post(route('agents.guest-review', $agent), ['rating' => 9]);

        $response->assertSessionHasErrors('rating');
    }
}
