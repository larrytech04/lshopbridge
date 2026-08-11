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
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
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

    public function test_saving_general_integrations_persists_both_toggles(): void
    {
        $this->actingAs($this->admin())->put(route('admin.integrations.general'), [
            'google_login_enabled' => '1',
            'turnstile_enabled' => '1',
        ]);

        $this->assertTrue((bool) setting('google_login_enabled'));
        $this->assertTrue((bool) setting('turnstile_enabled'));
    }

    public function test_unchecking_both_toggles_saves_them_as_false(): void
    {
        app(\App\Services\Settings\SettingsService::class)->set('google_login_enabled', '1', 'bool', 'integrations');
        app(\App\Services\Settings\SettingsService::class)->set('turnstile_enabled', '1', 'bool', 'integrations');

        $this->actingAs($this->admin())->put(route('admin.integrations.general'), []);

        $this->assertFalse((bool) setting('google_login_enabled'));
        $this->assertFalse((bool) setting('turnstile_enabled'));
    }
}
