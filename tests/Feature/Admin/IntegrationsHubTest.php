<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationsHubTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_index_shows_real_configuration_status_for_alerting_and_geo_ip(): void
    {
        config([
            'services.discord.webhook_url' => 'https://discord.example/webhook',
            'services.slack_alerts.webhook_url' => null,
            'services.ipinfo.api_key' => null,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.integrations.index'));

        $response->assertOk();
        $response->assertSeeText('Integrations hub');
        $response->assertSeeText('Discord critical alerts');
        $response->assertSeeText('Geo-IP / VPN detection');
    }
}
