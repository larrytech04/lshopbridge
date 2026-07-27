<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Security\TotpService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMfaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_are_reachable_without_mfa_when_the_setting_is_off(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_pages_redirect_to_enrollment_when_the_setting_is_on_and_admin_has_no_mfa(): void
    {
        app(SettingsService::class)->set('require_admin_mfa', true, 'bool', 'general');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertRedirect(route('security.two-factor.show'));
    }

    public function test_admin_pages_are_reachable_when_the_setting_is_on_and_admin_has_confirmed_mfa(): void
    {
        app(SettingsService::class)->set('require_admin_mfa', true, 'bool', 'general');
        $secret = app(TotpService::class)->generateSecret();
        $admin = User::factory()->create([
            'role' => 'admin', 'status' => 'active',
            'two_factor_enabled' => true, 'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }
}
