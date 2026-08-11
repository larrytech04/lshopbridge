<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Notifications\ReauthCodeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReauthTest extends TestCase
{
    use RefreshDatabase;

    private function idleSince(int $minutesAgo): array
    {
        return ['reauth' => ['last_activity' => now()->subMinutes($minutesAgo)->timestamp]];
    }

    public function test_a_fresh_session_is_never_locked(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_idle_past_the_threshold_locks_a_user_with_a_pin_to_the_pin_screen(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);

        $this->actingAs($user)
            ->withSession($this->idleSince(16))
            ->get(route('wallet.index'))
            ->assertRedirect(route('reauth.pin'));
    }

    public function test_idle_past_the_threshold_locks_a_user_without_a_pin_straight_to_the_email_screen(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => null]);

        $this->actingAs($user)
            ->withSession($this->idleSince(16))
            ->get(route('wallet.index'))
            ->assertRedirect(route('reauth.email'));
    }

    public function test_idle_under_the_threshold_does_not_lock(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);

        $this->actingAs($user)
            ->withSession($this->idleSince(10))
            ->get(route('wallet.index'))
            ->assertOk();
    }

    public function test_wrong_pin_is_rejected_and_stays_on_the_pin_stage(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);
        $this->actingAs($user)->withSession($this->idleSince(16))->get(route('dashboard'));

        $this->post(route('reauth.pin'), ['pin' => '0000'])
            ->assertSessionHasErrors('pin');

        $this->get(route('reauth.pin'))->assertOk();
    }

    public function test_correct_pin_advances_to_the_email_stage_and_sends_a_code(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);
        $this->actingAs($user)->withSession($this->idleSince(16))->get(route('dashboard'));

        $this->post(route('reauth.pin'), ['pin' => '1234'])
            ->assertRedirect(route('reauth.email'));

        Notification::assertSentTo($user, ReauthCodeMail::class);
        $this->assertNotNull($user->fresh()->reauth_code);
    }

    public function test_wrong_code_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => null]);
        $this->actingAs($user)->withSession($this->idleSince(16))->get(route('dashboard'));
        $this->get(route('reauth.email'));

        $this->post(route('reauth.email'), ['code' => 'WRONG1'])
            ->assertSessionHasErrors('code');
    }

    public function test_correct_code_unlocks_and_returns_to_the_originally_intended_page(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => null]);
        $this->actingAs($user)->withSession($this->idleSince(16))->get(route('wallet.index'));
        $this->get(route('reauth.email'));

        $plaintext = null;
        Notification::assertSentTo($user, ReauthCodeMail::class, function ($notification) use (&$plaintext) {
            $plaintext = $notification->code;

            return true;
        });

        $this->post(route('reauth.email'), ['code' => $plaintext])
            ->assertRedirect(route('wallet.index'));

        $this->assertNull($user->fresh()->reauth_code);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_logout_remains_reachable_while_locked(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);
        $this->actingAs($user)->withSession($this->idleSince(16))->get(route('dashboard'));

        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_pin_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);
        $this->actingAs($user)->withSession($this->idleSince(16))->get(route('dashboard'));

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('reauth.pin'), ['pin' => '0000']);
        }

        // 6th attempt is throttled even though nothing about the PIN changed.
        $this->post(route('reauth.pin'), ['pin' => '0000'])->assertSessionHasErrors('pin');
    }

    public function test_idle_past_thirty_minutes_logs_the_user_out_instead_of_soft_locking(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);

        $this->actingAs($user)
            ->withSession($this->idleSince(31))
            ->get(route('wallet.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_after_a_hard_logout_skips_the_pin_and_goes_straight_to_the_email_stage(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'status' => 'active',
            'password' => Hash::make('password'),
            'transaction_pin' => '1234',
        ]);

        // Trip the 30-minute hard logout first.
        $this->actingAs($user)->withSession($this->idleSince(31))->get(route('wallet.index'));
        $this->assertGuest();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('reauth.email'));
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, ReauthCodeMail::class);

        // Confirms the PIN stage was skipped entirely, not just that email
        // comes after it.
        $this->get(route('reauth.pin'))->assertRedirect(route('reauth.email'));
    }

    public function test_the_pending_code_requirement_is_consumed_only_once(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'status' => 'active',
            'password' => Hash::make('password'),
            'transaction_pin' => null,
        ]);

        $this->actingAs($user)->withSession($this->idleSince(31))->get(route('wallet.index'));

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('reauth.email'));

        $plaintext = null;
        Notification::assertSentTo($user, ReauthCodeMail::class, function ($notification) use (&$plaintext) {
            $plaintext = $notification->code;

            return true;
        });
        $this->post(route('reauth.email'), ['code' => $plaintext])->assertRedirect();

        // Logging out and back in again should NOT re-arm the email stage a
        // second time, now that the pending flag has been consumed.
        $this->post(route('logout'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }
}
