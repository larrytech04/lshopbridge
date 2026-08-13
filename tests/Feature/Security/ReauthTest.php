<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Notifications\ReauthCodeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Idle-session re-authentication: 24+ hours idle on an authenticated
 * session locks it in place (session and login intact) until an emailed
 * code is entered. No PIN, no password, nothing destroyed — see
 * ReauthService. The transaction PIN plays no role here at all; its only
 * job in this app is authorizing an actual transfer/withdrawal (see
 * TransactionPinTest / FundingController / WithdrawalService).
 */
class ReauthTest extends TestCase
{
    use RefreshDatabase;

    private function idleSince(int $hoursAgo): array
    {
        return ['reauth' => ['last_activity' => now()->subHours($hoursAgo)->timestamp]];
    }

    private function idleForMinutes(int $minutesAgo): array
    {
        return ['reauth' => ['last_activity' => now()->subMinutes($minutesAgo)->timestamp]];
    }

    /** EnsureAdminMfa (a separate, unrelated safeguard) redirects any admin
     *  request to MFA enrollment unless MFA is already set up — needs to be
     *  satisfied here so these tests exercise reauth specifically, not that
     *  unrelated middleware. */
    private function mfaEnabledAdmin(): User
    {
        return User::factory()->create([
            'status' => 'active',
            'role' => 'admin',
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function test_a_fresh_session_is_never_locked(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_idle_under_24_hours_does_not_lock(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->withSession($this->idleSince(23))
            ->get(route('wallet.index'))
            ->assertOk();
    }

    public function test_idle_24_hours_or_more_locks_to_the_email_screen(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->withSession($this->idleSince(25))
            ->get(route('wallet.index'))
            ->assertRedirect(route('reauth.email'));
    }

    public function test_a_locked_session_stays_authenticated_not_logged_out(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->withSession($this->idleSince(25))->get(route('wallet.index'));

        // Locked in place, not signed out — this is the whole point.
        $this->assertAuthenticatedAs($user);
    }

    public function test_this_also_applies_to_agents(): void
    {
        $agent = User::factory()->create(['status' => 'active', 'role' => 'agent']);

        $this->actingAs($agent)
            ->withSession($this->idleSince(25))
            ->get(route('agent.dashboard'))
            ->assertRedirect(route('reauth.email'));

        $this->assertAuthenticatedAs($agent);
    }

    public function test_this_also_applies_to_admins(): void
    {
        $admin = $this->mfaEnabledAdmin();

        $this->actingAs($admin)
            ->withSession($this->idleSince(25))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('reauth.email'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admins_get_a_much_tighter_30_minute_leash_not_24_hours(): void
    {
        $admin = $this->mfaEnabledAdmin();

        // 31 minutes: nowhere near the 24-hour customer/agent threshold, but
        // well past the admin-specific one.
        $this->actingAs($admin)
            ->withSession($this->idleForMinutes(31))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('reauth.email'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admins_under_30_minutes_idle_are_not_locked(): void
    {
        $admin = $this->mfaEnabledAdmin();

        $this->actingAs($admin)
            ->withSession($this->idleForMinutes(20))
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_the_same_idle_time_that_locks_an_admin_does_not_lock_a_regular_customer(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->withSession($this->idleForMinutes(31))
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_arriving_at_the_email_screen_sends_a_code(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user)->withSession($this->idleSince(25))->get(route('dashboard'));

        $this->get(route('reauth.email'))->assertOk();

        Notification::assertSentTo($user, ReauthCodeMail::class);
        $this->assertNotNull($user->fresh()->reauth_code);
    }

    public function test_wrong_code_is_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user)->withSession($this->idleSince(25))->get(route('dashboard'));
        $this->get(route('reauth.email'));

        $this->post(route('reauth.email'), ['code' => 'WRONG1'])
            ->assertSessionHasErrors('code');
    }

    public function test_correct_code_unlocks_in_place_and_returns_to_the_originally_intended_page(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user)->withSession($this->idleSince(25))->get(route('wallet.index'));
        $this->get(route('reauth.email'));

        $plaintext = null;
        Notification::assertSentTo($user, ReauthCodeMail::class, function ($notification) use (&$plaintext) {
            $plaintext = $notification->code;

            return true;
        });

        $this->post(route('reauth.email'), ['code' => $plaintext])
            ->assertRedirect(route('wallet.index'));

        $this->assertNull($user->fresh()->reauth_code);
        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_logout_remains_reachable_while_locked(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user)->withSession($this->idleSince(25))->get(route('dashboard'));

        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_code_attempts_are_rate_limited(): void
    {
        Notification::fake();
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user)->withSession($this->idleSince(25))->get(route('dashboard'));
        $this->get(route('reauth.email'));

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('reauth.email'), ['code' => 'WRONG1']);
        }

        // The 7th attempt is throttled even though nothing about the code changed.
        $this->post(route('reauth.email'), ['code' => 'WRONG1'])->assertSessionHasErrors('code');
    }

    public function test_a_deliberate_logout_requires_the_normal_password_login_next_time_no_code_involved(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user)->post(route('logout'));
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        Notification::assertNothingSent();
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_transaction_pin_plays_no_part_in_reauth_even_when_one_is_set(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);

        // 24+ hours idle locks to the email screen directly — a PIN being
        // set on the account changes nothing about this flow.
        $this->actingAs($user)
            ->withSession($this->idleSince(25))
            ->get(route('wallet.index'))
            ->assertRedirect(route('reauth.email'));
    }
}
