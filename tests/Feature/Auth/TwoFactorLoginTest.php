<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Security\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    private function totpCodeFor(string $secret): string
    {
        $totp = app(TotpService::class);

        $decode = new \ReflectionMethod($totp, 'base32Decode');
        $decode->setAccessible(true);
        $key = $decode->invoke($totp, $secret);

        $codeAt = new \ReflectionMethod($totp, 'codeAt');
        $codeAt->setAccessible(true);

        return $codeAt->invoke($totp, $key, intdiv(time(), 30));
    }

    private function userWithMfa(string $secret): User
    {
        return User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'password' => Hash::make('password'),
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [Hash::make('AAAA1111-BBBB2222')],
        ]);
    }

    public function test_login_without_mfa_logs_in_directly(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active', 'password' => Hash::make('password')]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_mfa_enabled_does_not_establish_a_session_and_redirects_to_challenge(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $user = $this->userWithMfa($secret);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
    }

    public function test_correct_totp_code_completes_login(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $user = $this->userWithMfa($secret);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertGuest();

        $response = $this->post(route('two-factor.verify'), ['code' => $this->totpCodeFor($secret)]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_incorrect_totp_code_is_rejected_and_does_not_log_in(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $user = $this->userWithMfa($secret);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->post(route('two-factor.verify'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_completes_login_and_cannot_be_reused(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $user = $this->userWithMfa($secret);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('two-factor.verify'), ['code' => 'AAAA1111-BBBB2222']);
        $this->assertAuthenticatedAs($user);
        $this->assertCount(0, $user->fresh()->two_factor_recovery_codes);

        $this->post(route('logout'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $response = $this->post(route('two-factor.verify'), ['code' => 'AAAA1111-BBBB2222']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_challenge_is_rate_limited_after_too_many_wrong_attempts(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $user = $this->userWithMfa($secret);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.verify'), ['code' => '000000']);
        }

        $response = $this->post(route('two-factor.verify'), ['code' => $this->totpCodeFor($secret)]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_cancel_clears_pending_challenge_and_returns_to_login(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $user = $this->userWithMfa($secret);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('two-factor.cancel'));

        $response = $this->get(route('two-factor.challenge'));
        $response->assertRedirect(route('login'));
    }
}
