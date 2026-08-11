<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Security\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMfaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_redirect_to_enrollment_when_the_admin_has_no_mfa(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertRedirect(route('security.two-factor.show'));
    }

    public function test_the_enrollment_page_itself_stays_reachable_without_mfa(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->get(route('security.two-factor.show'))->assertOk();
    }

    public function test_admin_pages_are_reachable_once_mfa_is_confirmed(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $admin = User::factory()->create([
            'role' => 'admin', 'status' => 'active',
            'two_factor_enabled' => true, 'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_a_confirmed_passkey_also_satisfies_the_requirement(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->webauthnCredentials()->create([
            'name' => 'Test passkey',
            'credential_id' => base64_encode(random_bytes(16)),
            'public_key' => base64_encode(random_bytes(32)),
            'attestation_type' => 'none',
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'transports' => [],
            'counter' => 0,
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_non_admin_users_are_never_gated_by_this_check(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}
