<?php

namespace Tests\Feature\Admin;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_non_admin_cannot_view_agent_management(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.agents.index'))->assertForbidden();
    }

    public function test_admin_can_view_agent_management(): void
    {
        Agent::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.agents.index'))
            ->assertOk()
            ->assertSee('Agent Management');
    }

    public function test_status_tab_filters_agents(): void
    {
        Agent::factory()->create(['business_name' => 'Pending Co', 'status' => 'pending']);
        Agent::factory()->create(['business_name' => 'Approved Co', 'status' => 'approved']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.agents.index', ['tab' => 'pending']));

        $response->assertSee('Pending Co')->assertDontSee('Approved Co');
    }

    public function test_search_filters_by_business_name(): void
    {
        Agent::factory()->create(['business_name' => 'Guangzhou Cargo Express']);
        Agent::factory()->create(['business_name' => 'Yiwu Trade Bridge']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.agents.index', ['q' => 'Guangzhou']));

        $response->assertSee('Guangzhou Cargo Express')->assertDontSee('Yiwu Trade Bridge');
    }

    public function test_approve_transitions_status_and_writes_audit_log(): void
    {
        $agent = Agent::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.agents.approve', $agent))
            ->assertRedirect();

        $agent->refresh();
        $this->assertSame('approved', $agent->status->value);
        $this->assertNotNull($agent->verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.agent.approved', 'auditable_id' => $agent->id]);
    }

    public function test_reject_requires_a_reason(): void
    {
        $agent = Agent::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.agents.reject', $agent), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $agent->fresh()->status->value);
    }

    public function test_reject_with_reason_updates_status(): void
    {
        $agent = Agent::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.agents.reject', $agent), ['reason' => 'Documents unclear'])
            ->assertRedirect();

        $this->assertSame('rejected', $agent->fresh()->status->value);
        $this->assertSame('Documents unclear', $agent->fresh()->rejection_reason);
    }

    public function test_suspend_and_restore_round_trip(): void
    {
        $agent = Agent::factory()->approved()->create(['is_featured' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.agents.suspend', $agent), ['reason' => 'Policy violation'])
            ->assertRedirect();

        $agent->refresh();
        $this->assertSame('suspended', $agent->status->value);
        $this->assertFalse($agent->is_featured);

        $this->actingAs($this->admin())
            ->post(route('admin.agents.restore', $agent))
            ->assertRedirect();

        $this->assertSame('approved', $agent->fresh()->status->value);
    }

    public function test_only_approved_agents_can_be_featured(): void
    {
        $pending = Agent::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin())->post(route('admin.agents.feature', $pending));

        $this->assertFalse($pending->fresh()->is_featured);

        $approved = Agent::factory()->approved()->create();
        $this->actingAs($this->admin())->post(route('admin.agents.feature', $approved));

        $this->assertTrue($approved->fresh()->is_featured);
    }

    public function test_bulk_approve_updates_all_selected_agents(): void
    {
        $one = Agent::factory()->create(['status' => 'pending']);
        $two = Agent::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.agents.bulk-action'), [
                'action' => 'approve',
                'ids' => [$one->id, $two->id],
            ])
            ->assertRedirect();

        $this->assertSame('approved', $one->fresh()->status->value);
        $this->assertSame('approved', $two->fresh()->status->value);
    }

    public function test_bulk_action_does_not_allow_deletion(): void
    {
        $agent = Agent::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.agents.bulk-action'), [
                'action' => 'delete',
                'ids' => [$agent->id],
            ])
            ->assertSessionHasErrors('action');

        $this->assertNotNull(Agent::find($agent->id));
    }

    public function test_destroy_soft_deletes_agent(): void
    {
        $agent = Agent::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.agents.destroy', $agent), ['reason' => 'Closed business'])
            ->assertRedirect(route('admin.agents.index'));

        $this->assertSoftDeleted('agents', ['id' => $agent->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.agent.deleted']);
    }
}
