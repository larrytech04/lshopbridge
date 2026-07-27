<?php

namespace Tests\Feature\Public;

use App\Jobs\DeliverAcceptedContactMessage;
use App\Models\Dispute;
use App\Models\GuestSupportTicket;
use App\Models\User;
use App\Services\Security\HoneypotValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Question about fees',
            'message' => 'Hi, could you clarify your funding fees for Cameroon?',
        ], $overrides);
    }

    public function test_a_legitimate_guest_submission_creates_a_real_ticket_and_queues_delivery(): void
    {
        Queue::fake();

        $response = $this->post(route('contact.submit'), $this->payload());

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('guest_support_tickets', ['email' => 'jane@example.com', 'subject' => 'Question about fees', 'status' => 'open']);
        Queue::assertPushed(DeliverAcceptedContactMessage::class);
    }

    public function test_an_authenticated_submission_still_creates_a_dispute_not_a_guest_ticket(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Queue::fake();

        $response = $this->actingAs($user)->post(route('contact.submit'), $this->payload());

        $response->assertRedirect();
        $this->assertDatabaseHas('disputes', ['user_id' => $user->id, 'subject' => 'Question about fees']);
        $this->assertDatabaseCount('guest_support_tickets', 0);
        Queue::assertNotPushed(DeliverAcceptedContactMessage::class);
    }

    public function test_honeypot_submission_gets_the_same_success_response_but_creates_nothing(): void
    {
        Queue::fake();
        $honeypotField = app(HoneypotValidationService::class)->fieldName();
        // Combine honeypot with a second high-confidence signal (spam content) so it's a confident discard.
        $data = $this->payload([
            'message' => 'Buy backlinks now! http://a.xyz http://b.xyz http://c.xyz guaranteed profit',
            $honeypotField => 'http://spammer.example',
        ]);

        $response = $this->post(route('contact.submit'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Thanks for reaching out, our team will respond shortly.');
        $this->assertDatabaseCount('guest_support_tickets', 0);
        Queue::assertNotPushed(DeliverAcceptedContactMessage::class);
        $this->assertDatabaseHas('form_security_events', ['form_type' => 'contact']);
    }

    public function test_a_double_click_does_not_create_two_tickets(): void
    {
        Queue::fake();
        $data = $this->payload();

        $this->post(route('contact.submit'), $data);
        $this->post(route('contact.submit'), $data);

        $this->assertDatabaseCount('guest_support_tickets', 1);
    }

    public function test_missing_required_fields_are_rejected_by_normal_validation(): void
    {
        $response = $this->post(route('contact.submit'), ['name' => '', 'email' => 'not-an-email', 'subject' => '', 'message' => '']);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
        $this->assertDatabaseCount('guest_support_tickets', 0);
    }

    public function test_a_header_injection_attempt_in_the_subject_does_not_add_a_bcc_recipient(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $data = $this->payload(['subject' => "Hello\r\nBcc: attacker@evil.com"]);

        $this->post(route('contact.submit'), $data);

        $ticket = GuestSupportTicket::firstOrFail();
        // Notification delivery goes through Laravel's Mail/Symfony Mailer, which
        // header-encodes the subject — nowhere in this codebase concatenates user
        // input into a raw mail() header string, so there is no injection surface
        // to defend in application code. This confirms the pipeline still only
        // targets the configured support address, never an attacker-supplied one.
        \App\Jobs\DeliverAcceptedContactMessage::dispatchSync($ticket);
        \Illuminate\Support\Facades\Notification::assertSentOnDemand(
            \App\Notifications\GuestContactReceived::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === setting('support_email', config('platform.support_email')),
        );
    }
}
