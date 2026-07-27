<?php

namespace Tests\Feature\Public;

use App\Jobs\DeliverAcceptedContactMessage;
use App\Models\GuestSupportTicket;
use App\Services\Security\HoneypotValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestSupportFormTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'category' => 'deposit',
            'subject' => 'My deposit is stuck',
            'description' => 'I sent a deposit two days ago and it still shows pending.',
        ], $overrides);
    }

    public function test_a_legitimate_submission_creates_a_ticket_and_queues_delivery(): void
    {
        Queue::fake();

        $response = $this->post(route('support.guest.store'), $this->payload());

        $response->assertRedirect();
        $this->assertDatabaseHas('guest_support_tickets', ['email' => 'sam@example.com', 'category' => 'deposit']);
        Queue::assertPushed(DeliverAcceptedContactMessage::class);
    }

    public function test_honeypot_plus_spam_content_is_silently_discarded(): void
    {
        Queue::fake();
        $honeypotField = app(HoneypotValidationService::class)->fieldName();

        $response = $this->post(route('support.guest.store'), $this->payload([
            'description' => 'Buy backlinks now! http://a.xyz http://b.xyz http://c.xyz guaranteed profit',
            $honeypotField => 'filled',
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('guest_support_tickets', 0);
    }

    public function test_invalid_category_is_rejected_by_validation(): void
    {
        $response = $this->post(route('support.guest.store'), $this->payload(['category' => 'not-a-real-category']));

        $response->assertSessionHasErrors('category');
    }

    public function test_a_valid_attachment_is_stored_on_the_private_disk_with_a_random_name(): void
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->image('proof.jpg')->size(500);

        $this->post(route('support.guest.store'), array_merge($this->payload(), ['attachment' => $file]));

        $ticket = GuestSupportTicket::firstOrFail();
        $this->assertNotNull($ticket->attachment_path);
        $this->assertStringNotContainsString('proof.jpg', $ticket->attachment_path);
        Storage::disk('private')->assertExists($ticket->attachment_path);
    }

    public function test_an_executable_disguised_as_an_attachment_is_rejected(): void
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->create('malware.php', 10, 'application/x-php');

        $response = $this->post(route('support.guest.store'), array_merge($this->payload(), ['attachment' => $file]));

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('guest_support_tickets', 0);
    }

    public function test_an_oversized_attachment_is_rejected(): void
    {
        Storage::fake('private');
        $file = UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf');

        $response = $this->post(route('support.guest.store'), array_merge($this->payload(), ['attachment' => $file]));

        $response->assertSessionHasErrors('attachment');
    }

    public function test_a_discarded_submissions_attachment_is_never_written_to_disk(): void
    {
        Storage::fake('private');
        $honeypotField = app(HoneypotValidationService::class)->fieldName();
        $file = UploadedFile::fake()->image('proof.jpg');

        $this->post(route('support.guest.store'), array_merge($this->payload([
            'description' => 'Buy backlinks now! http://a.xyz http://b.xyz http://c.xyz guaranteed profit',
            $honeypotField => 'filled',
        ]), ['attachment' => $file]));

        Storage::disk('private')->assertDirectoryEmpty('guest-support');
    }
}
