<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\Auth\AdminSessionController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminLoginSplitTest extends TestCase
{
    use RefreshDatabase;

    private function adminPath(string $path = ''): string
    {
        return '/'.config('platform.admin_path').$path;
    }

    private function enableTurnstile(): void
    {
        config([
            'services.turnstile.site_key' => '1x00000000000000000000AA',
            'services.turnstile.secret_key' => '1x0000000000000000000000000000AA',
        ]);
        \App\Models\Setting::create(['key' => 'turnstile_enabled', 'value' => '1', 'type' => 'bool', 'group' => 'general']);
    }

    public function test_a_correct_admin_password_is_rejected_on_the_public_login_form(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin', 'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', ['email' => $admin->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_correct_regular_user_password_is_rejected_on_the_admin_login_form(): void
    {
        $user = User::factory()->create([
            'role' => 'user', 'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post($this->adminPath('/login'), ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_correct_admin_password_succeeds_on_the_admin_login_form(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin', 'status' => 'active',
            'password' => Hash::make('password'),
            'two_factor_enabled' => true,
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post($this->adminPath('/login'), ['email' => $admin->email, 'password' => 'password']);

        // MFA is mandatory for admins, so a fully successful login still
        // stops at the second-factor challenge rather than the dashboard.
        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
    }

    public function test_an_unknown_email_is_rejected_on_the_admin_login_form(): void
    {
        $response = $this->post($this->adminPath('/login'), ['email' => 'nobody@example.com', 'password' => 'whatever']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_login_attempts_are_rate_limited(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'password' => Hash::make('password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post($this->adminPath('/login'), ['email' => $admin->email, 'password' => 'wrong']);
        }

        $response = $this->post($this->adminPath('/login'), ['email' => $admin->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_unauthenticated_visit_to_an_admin_page_redirects_to_the_admin_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_an_unauthenticated_visit_to_a_regular_page_still_redirects_to_the_public_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_hard_idle_logout_redirects_to_the_admin_login_not_the_passwordless_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->withSession(['reauth' => ['last_activity' => now()->subMinutes(16)->timestamp]])
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_an_admin_email_on_the_passwordless_welcome_back_screen_is_sent_to_the_admin_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $regular = User::factory()->create(['role' => 'user', 'status' => 'active']);

        // Trip the regular hard-logout flow for a non-admin so the "welcome
        // back" screen is reachable at all.
        $this->actingAs($regular)
            ->withSession(['reauth' => ['last_activity' => now()->subMinutes(16)->timestamp]])
            ->get(route('wallet.index'));

        $this->post(route('reauth.identify'), ['email' => $admin->email])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_admin_login_is_blocked_without_a_turnstile_token_when_enabled(): void
    {
        $this->enableTurnstile();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'password' => Hash::make('password')]);

        $response = $this->post($this->adminPath('/login'), ['email' => $admin->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_login_succeeds_with_a_valid_turnstile_token(): void
    {
        $this->enableTurnstile();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'password' => Hash::make('password')]);

        $response = $this->post($this->adminPath('/login'), [
            'email' => $admin->email,
            'password' => 'password',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_login_turnstile_check_runs_even_on_the_public_login_style_conditional_setting(): void
    {
        // The admin login always requires the widget when Turnstile is
        // enabled, unlike the public form's "conditional" appearance mode
        // which only shows it after a failed attempt.
        $this->enableTurnstile();
        \App\Models\Setting::create(['key' => 'turnstile_appearance_mode', 'value' => 'conditional', 'type' => 'string', 'group' => 'general']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'password' => Hash::make('password')]);

        $response = $this->post($this->adminPath('/login'), ['email' => $admin->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_successful_admin_login_marks_this_device(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'password' => Hash::make('password')]);

        $response = $this->post($this->adminPath('/login'), ['email' => $admin->email, 'password' => 'password']);

        $response->assertCookie(AdminSessionController::DEVICE_COOKIE);
    }

    public function test_a_marked_device_gets_the_admin_login_on_a_regular_guest_gated_page(): void
    {
        $this->withCookie(AdminSessionController::DEVICE_COOKIE, '1')
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_a_marked_device_gets_the_admin_login_when_visiting_the_public_login_page_directly(): void
    {
        $this->withCookie(AdminSessionController::DEVICE_COOKIE, '1')
            ->get('/login')
            ->assertRedirect(route('admin.login'));
    }

    public function test_an_unmarked_device_still_gets_the_normal_public_login_page(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }
}
