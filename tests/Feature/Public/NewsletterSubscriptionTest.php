<?php

namespace Tests\Feature\Public;

use App\Models\NewsletterSubscriber;
use App\Services\Security\HoneypotValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_legitimate_subscription_is_recorded(): void
    {
        $response = $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);

        $response->assertRedirect();
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'reader@example.com', 'status' => 'subscribed']);
    }

    public function test_resubscribing_after_unsubscribing_reactivates_the_same_row(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'returning@example.com',
            'status' => 'unsubscribed',
            'unsubscribe_token' => 'tok123',
            'unsubscribed_at' => now(),
        ]);

        $this->post(route('newsletter.subscribe'), ['email' => 'returning@example.com']);

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertSame('subscribed', $subscriber->fresh()->status);
    }

    public function test_unsubscribe_link_deactivates_the_subscriber(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'leaving@example.com',
            'status' => 'subscribed',
            'unsubscribe_token' => 'tok456',
            'subscribed_at' => now(),
        ]);

        $response = $this->get(route('newsletter.unsubscribe', 'tok456'));

        $response->assertRedirect();
        $this->assertSame('unsubscribed', $subscriber->fresh()->status);
    }

    public function test_honeypot_signup_creates_no_subscriber_but_looks_successful(): void
    {
        $honeypotField = app(HoneypotValidationService::class)->fieldName();

        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'bot@example.com',
            $honeypotField => 'filled',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $response = $this->post(route('newsletter.subscribe'), ['email' => 'not-an-email']);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }
}
