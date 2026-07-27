<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasskeyManagementTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    private function passkeyFor(User $user, array $overrides = []): WebauthnCredential
    {
        return WebauthnCredential::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Test device',
            'credential_id' => base64_encode(random_bytes(16)),
            'public_key' => base64_encode(random_bytes(32)),
            'attestation_type' => 'none',
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'transports' => [],
            'counter' => 0,
        ], $overrides));
    }

    public function test_guest_cannot_access_passkey_routes(): void
    {
        $this->get(route('security.passkeys.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_only_the_users_own_passkeys(): void
    {
        $user = $this->user();
        $other = $this->user();
        $mine = $this->passkeyFor($user, ['name' => 'Mine']);
        $this->passkeyFor($other, ['name' => 'Not mine']);

        $response = $this->actingAs($user)->get(route('security.passkeys.index'));

        $response->assertOk();
        $response->assertSee('Mine');
        $response->assertDontSee('Not mine');
    }

    public function test_register_options_returns_a_challenge_and_stashes_it_in_session(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->postJson(route('security.passkeys.register-options'));

        $response->assertOk();
        $response->assertJsonStructure(['challenge', 'rp' => ['name', 'id'], 'user' => ['id', 'name', 'displayName'], 'pubKeyCredParams']);
        $this->assertNotEmpty(session('passkey_register_challenge'));
    }

    public function test_store_rejects_a_malformed_response_without_crashing(): void
    {
        $user = $this->user();
        $this->actingAs($user)->postJson(route('security.passkeys.register-options'));

        $response = $this->actingAs($user)->postJson(route('security.passkeys.store'), [
            'name' => 'My device',
            'response' => ['not' => 'a real webauthn response'],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('webauthn_credentials', 0);
    }

    public function test_store_without_a_prior_options_call_fails_cleanly(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->postJson(route('security.passkeys.store'), [
            'name' => 'My device',
            'response' => ['id' => 'x'],
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_delete_another_users_passkey(): void
    {
        $owner = $this->user();
        $attacker = $this->user();
        $passkey = $this->passkeyFor($owner);

        $this->actingAs($attacker)->delete(route('security.passkeys.destroy', $passkey))->assertNotFound();
        $this->assertDatabaseHas('webauthn_credentials', ['id' => $passkey->id]);
    }

    public function test_user_can_delete_their_own_passkey(): void
    {
        $user = $this->user();
        $passkey = $this->passkeyFor($user);

        $this->actingAs($user)->delete(route('security.passkeys.destroy', $passkey))->assertRedirect();
        $this->assertDatabaseMissing('webauthn_credentials', ['id' => $passkey->id]);
    }
}
