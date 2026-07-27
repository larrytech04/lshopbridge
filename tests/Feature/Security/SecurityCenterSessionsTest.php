<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\SecurityController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityCenterSessionsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    private function insertSession(string $id, int $userId): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode('test'),
            'last_activity' => time(),
        ]);
    }

    public function test_user_cannot_revoke_another_users_session(): void
    {
        $owner = $this->user();
        $attacker = $this->user();
        $this->insertSession('victim-session-id', $owner->id);

        $response = $this->actingAs($attacker)->delete(route('security.sessions.revoke', 'victim-session-id'));

        $response->assertNotFound();
        $this->assertDatabaseHas('sessions', ['id' => 'victim-session-id']);
    }

    public function test_user_can_revoke_their_own_session(): void
    {
        $user = $this->user();
        $this->insertSession('my-other-session-id', $user->id);

        $response = $this->actingAs($user)->delete(route('security.sessions.revoke', 'my-other-session-id'));

        $response->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'my-other-session-id']);
    }

    public function test_revoking_a_nonexistent_session_id_404s(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->delete(route('security.sessions.revoke', 'does-not-exist'));

        $response->assertNotFound();
    }

    public function test_revoke_others_keeps_the_current_session_and_removes_the_rest(): void
    {
        // Laravel's test HTTP client doesn't guarantee the same session id
        // across two separate simulated requests, so "which row is current"
        // can't be observed reliably at the HTTP layer. Exercise the
        // controller action directly instead, with a request bound to a
        // session whose id we control, matching one of the fixture rows.
        $user = $this->user();
        // Store::setId() silently discards anything that isn't a valid
        // (40-char alphanumeric) session id and generates a random one
        // instead, so the fixture "current" id has to look like a real one.
        $currentId = \Illuminate\Support\Str::random(40);
        $this->insertSession($currentId, $user->id);
        $this->insertSession('some-other-device-session-id', $user->id);

        $request = Request::create('/security/sessions/revoke-others', 'DELETE');
        $request->setUserResolver(fn () => $user);
        $session = app('session')->driver('array');
        $session->setId($currentId);
        $session->start();
        $request->setLaravelSession($session);

        $this->assertSame($currentId, $request->session()->getId());

        app(SecurityController::class)->revokeOtherSessions($request);

        $this->assertDatabaseHas('sessions', ['id' => $currentId]);
        $this->assertDatabaseMissing('sessions', ['id' => 'some-other-device-session-id']);
    }
}
