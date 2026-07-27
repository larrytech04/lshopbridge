<?php

namespace Tests\Feature\Admin;

use App\Models\GuestSupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestSupportTicketAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_index_lists_tickets_by_status(): void
    {
        GuestSupportTicket::create(['reference' => 'PB-GST-A', 'name' => 'A', 'email' => 'a@example.com', 'subject' => 'Open one', 'description' => 'x', 'status' => 'open']);
        GuestSupportTicket::create(['reference' => 'PB-GST-B', 'name' => 'B', 'email' => 'b@example.com', 'subject' => 'Resolved one', 'description' => 'x', 'status' => 'resolved']);

        $response = $this->actingAs($this->admin())->get(route('admin.support-tickets.index'));

        $response->assertOk();
        $response->assertSeeText('Open one');
        $response->assertDontSeeText('Resolved one');
    }

    public function test_assigning_a_ticket_to_self(): void
    {
        $admin = $this->admin();
        $ticket = GuestSupportTicket::create(['reference' => 'PB-GST-C', 'name' => 'C', 'email' => 'c@example.com', 'subject' => 'x', 'description' => 'x', 'status' => 'open']);

        $this->actingAs($admin)->post(route('admin.support-tickets.assign', $ticket));

        $ticket->refresh();
        $this->assertSame($admin->id, $ticket->assigned_to);
        $this->assertSame('in_progress', $ticket->status);
    }

    public function test_resolving_a_ticket_records_the_resolution(): void
    {
        $ticket = GuestSupportTicket::create(['reference' => 'PB-GST-D', 'name' => 'D', 'email' => 'd@example.com', 'subject' => 'x', 'description' => 'x', 'status' => 'open']);

        $this->actingAs($this->admin())->post(route('admin.support-tickets.resolve', $ticket), ['resolution' => 'Fixed via manual credit.']);

        $ticket->refresh();
        $this->assertSame('resolved', $ticket->status);
        $this->assertSame('Fixed via manual credit.', $ticket->resolution);
    }

    public function test_converting_to_a_dispute_requires_an_existing_account(): void
    {
        $ticket = GuestSupportTicket::create(['reference' => 'PB-GST-E', 'name' => 'E', 'email' => 'noaccount@example.com', 'subject' => 'x', 'description' => 'x', 'status' => 'open']);

        $response = $this->actingAs($this->admin())->post(route('admin.support-tickets.convert', $ticket));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('disputes', 0);
    }

    public function test_converting_to_a_dispute_when_the_account_exists(): void
    {
        $user = User::factory()->create(['email' => 'hasaccount@example.com', 'status' => 'active']);
        $ticket = GuestSupportTicket::create(['reference' => 'PB-GST-F', 'name' => 'F', 'email' => 'hasaccount@example.com', 'subject' => 'Billing question', 'description' => 'x', 'status' => 'open']);

        $response = $this->actingAs($this->admin())->post(route('admin.support-tickets.convert', $ticket));

        $response->assertRedirect();
        $this->assertDatabaseHas('disputes', ['user_id' => $user->id, 'subject' => 'Billing question']);
        $this->assertSame('closed', $ticket->fresh()->status);
    }
}
