<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorPasskeyChallengeTest extends TestCase
{
    use RefreshDatabase;

    private function passkeyFor(User $user): WebauthnCredential
    {
        return WebauthnCredential::create([
            'user_id' => $user->id,
            'name' => 'Test device',
            'credential_id' => base64_encode(random_bytes(16)),
            'public_key' => base64_encode(random_bytes(32)),
            'attestation_type' => 'none',
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'transports' => [],
            'counter' => 0,
        ]);
    }

    private function userWithPasskeyOnly(): User
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active', 'password' => Hash::make('password')]);
        $this->passkeyFor($user);

        return $user;
    }

    public function test_a_user_with_only_a_passkey_still_hits_the_mfa_challenge_on_login(): void
    {
        $user = $this->userWithPasskeyOnly();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
    }

    public function test_challenge_page_offers_the_passkey_option_when_no_totp_is_set_up(): void
    {
        $user = $this->userWithPasskeyOnly();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->get(route('two-factor.challenge'));

        $response->assertOk();
        $response->assertSee('Use a passkey instead');
        $response->assertDontSee('Authentication code');
    }

    public function test_passkey_options_requires_a_pending_challenge_session(): void
    {
        $response = $this->postJson(route('two-factor.passkey.options'));

        $response->assertStatus(419);
    }

    public function test_passkey_options_returns_a_challenge_once_password_step_is_done(): void
    {
        $user = $this->userWithPasskeyOnly();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->postJson(route('two-factor.passkey.options'));

        $response->assertOk();
        $response->assertJsonStructure(['challenge', 'rpId', 'allowCredentials']);
        $this->assertNotEmpty(session('mfa_passkey_challenge'));
    }

    public function test_passkey_verify_rejects_a_malformed_response_and_does_not_log_in(): void
    {
        $user = $this->userWithPasskeyOnly();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->postJson(route('two-factor.passkey.options'));

        $response = $this->postJson(route('two-factor.passkey.verify'), ['response' => ['bogus' => true]]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_passkey_verify_without_options_first_fails_cleanly(): void
    {
        $user = $this->userWithPasskeyOnly();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->postJson(route('two-factor.passkey.verify'), ['response' => ['id' => 'x']]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_passkey_challenge_is_rate_limited_after_repeated_failures(): void
    {
        $user = $this->userWithPasskeyOnly();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->postJson(route('two-factor.passkey.options'));

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('two-factor.passkey.verify'), ['response' => ['bogus' => true]]);
        }

        $response = $this->postJson(route('two-factor.passkey.verify'), ['response' => ['bogus' => true]]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Too many attempts', $response->json('errors.response.0'));
        $this->assertGuest();
    }
}
