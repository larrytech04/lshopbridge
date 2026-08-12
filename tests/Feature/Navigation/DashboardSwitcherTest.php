<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Switch dashboard" quick-jump (User/Agent/Admin) beside "Visit
 * website" is a super-admin-only convenience — a regular admin or customer
 * has no legitimate reason to see a link into an area they can't use.
 */
class DashboardSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_super_admin_sees_the_dashboard_switcher(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin', 'status' => 'active',
            'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertSee(__('Switch dashboard'));
    }

    public function test_a_regular_admin_does_not_see_the_dashboard_switcher(): void
    {
        $user = User::factory()->create([
            'role' => 'admin', 'status' => 'active',
            'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertDontSee(__('Switch dashboard'));
    }

    public function test_a_regular_user_does_not_see_the_dashboard_switcher(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('dashboard'))->assertDontSee(__('Switch dashboard'));
    }
}
