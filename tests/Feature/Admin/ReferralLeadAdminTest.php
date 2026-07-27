<?php

namespace Tests\Feature\Admin;

use App\Models\ReferralLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralLeadAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_updating_status_to_contacted_records_who_and_when(): void
    {
        $admin = $this->admin();
        $lead = ReferralLead::create(['reference' => 'PB-REF-A', 'name' => 'Lead', 'email' => 'lead@example.com', 'status' => 'new']);

        $this->actingAs($admin)->put(route('admin.referral-leads.update', $lead), ['status' => 'contacted']);

        $lead->refresh();
        $this->assertSame('contacted', $lead->status);
        $this->assertSame($admin->id, $lead->contacted_by);
        $this->assertNotNull($lead->contacted_at);
    }

    public function test_index_filters_by_status(): void
    {
        ReferralLead::create(['reference' => 'PB-REF-B', 'name' => 'New Lead', 'email' => 'new@example.com', 'status' => 'new']);
        ReferralLead::create(['reference' => 'PB-REF-C', 'name' => 'Converted Lead', 'email' => 'conv@example.com', 'status' => 'converted']);

        $response = $this->actingAs($this->admin())->get(route('admin.referral-leads.index', ['status' => 'new']));

        $response->assertOk();
        $response->assertSeeText('New Lead');
        $response->assertDontSeeText('Converted Lead');
    }
}
