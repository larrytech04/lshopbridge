<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    private function setMaintenanceMode(bool $on): void
    {
        app(SettingsService::class)->set('maintenance_mode', $on, 'bool');
    }

    public function test_guest_sees_maintenance_page_when_enabled(): void
    {
        $this->setMaintenanceMode(true);

        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertViewIs('errors.maintenance');
    }

    public function test_customer_is_blocked_while_maintenance_mode_is_enabled(): void
    {
        $this->setMaintenanceMode(true);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(503);
    }

    public function test_admin_bypasses_maintenance_mode(): void
    {
        $this->setMaintenanceMode(true);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_login_route_remains_reachable_during_maintenance(): void
    {
        $this->setMaintenanceMode(true);

        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_normal_traffic_flows_when_maintenance_mode_is_disabled(): void
    {
        $this->setMaintenanceMode(false);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }
}
