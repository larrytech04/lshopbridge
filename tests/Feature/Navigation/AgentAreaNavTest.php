<?php

namespace Tests\Feature\Navigation;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * layouts/app.blade.php's $isAgentArea decides which nav partial renders
 * (partials.nav-agent vs partials.nav-user) — must reflect whether the
 * account can actually use the agent area, not just its literal role
 * column. A super admin with a real linked Agent profile (reachable via
 * EnsureUserRole's super-admin bypass on role:agent) was landing on agent
 * pages under the plain customer nav, with no way to reach Business
 * profile / Shipping rates / Leads / Reviews at all.
 */
class AgentAreaNavTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_real_agent_sees_the_agent_nav(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'agent']);
        Agent::factory()->create(['user_id' => $user->id, 'status' => 'approved']);

        $response = $this->actingAs($user)->get(route('agent.dashboard'));

        $response->assertOk();
        $response->assertSee('Business profile');
        $response->assertSee('Shipping rates');
    }

    public function test_a_regular_customer_sees_the_customer_nav_not_the_agent_one(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Business profile');
        $response->assertDontSee('Shipping rates');
    }

    public function test_a_super_admin_with_a_linked_agent_profile_sees_the_agent_nav(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'super_admin', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
        Agent::factory()->create(['user_id' => $admin->id, 'status' => 'suspended', 'business_name' => 'Internal Preview']);

        $response = $this->actingAs($admin)->get(route('agent.dashboard'));

        $response->assertOk();
        $response->assertSee('Business profile');
        $response->assertSee('Shipping rates');
        $response->assertSee('Orders / leads');
    }

    public function test_a_super_admin_with_no_linked_agent_profile_still_gets_the_customer_nav(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'super_admin', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Business profile');
    }
}
