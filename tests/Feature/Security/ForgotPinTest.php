<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Notifications\PinResetCodeMail;
use App\Notifications\SecurityAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Self-service "forgot transaction PIN": password + emailed code, then a
 * one-time bypass of the normal current_pin requirement (see
 * PinResetService / SecurityController::updatePin()).
 */
class ForgotPinTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => 'active',
            'password' => Hash::make('password'),
            'transaction_pin' => '1234',
        ], $overrides));
    }

    public function test_the_confirm_step_404s_without_a_pin_set(): void
    {
        $user = $this->user(['transaction_pin' => null]);

        $this->actingAs($user)->get(route('security.pin.forgot'))->assertNotFound();
    }

    public function test_the_confirm_step_loads_when_a_pin_is_set(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('security.pin.forgot'))->assertOk();
    }

    public function test_the_wrong_password_is_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('security.pin.forgot'), ['password' => 'not-it'])
            ->assertSessionHasErrors('password');
    }

    public function test_the_correct_password_sends_a_code_and_advances(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->actingAs($user)->post(route('security.pin.forgot'), ['password' => 'password'])
            ->assertRedirect(route('security.pin.forgot.code'));

        Notification::assertSentTo($user, PinResetCodeMail::class);
        $this->assertNotNull($user->fresh()->pin_reset_code);
    }

    public function test_the_code_step_is_unreachable_without_confirming_the_password_first(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('security.pin.forgot.code'))
            ->assertRedirect(route('security.pin.forgot'));
    }

    public function test_the_wrong_code_is_rejected(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user)->post(route('security.pin.forgot'), ['password' => 'password']);

        $this->post(route('security.pin.forgot.code'), ['code' => 'WRONG1'])
            ->assertSessionHasErrors('code');
    }

    public function test_the_correct_code_verifies_and_returns_to_the_pin_tab(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user)->post(route('security.pin.forgot'), ['password' => 'password']);

        $plaintext = null;
        Notification::assertSentTo($user, PinResetCodeMail::class, function ($notification) use (&$plaintext) {
            $plaintext = $notification->code;

            return true;
        });

        $this->post(route('security.pin.forgot.code'), ['code' => $plaintext])
            ->assertRedirect(route('security.index', ['tab' => 'pin']));

        $this->assertNull($user->fresh()->pin_reset_code);
    }

    public function test_a_verified_reset_lets_a_new_pin_be_set_without_the_old_one(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user)->post(route('security.pin.forgot'), ['password' => 'password']);

        $plaintext = null;
        Notification::assertSentTo($user, PinResetCodeMail::class, function ($notification) use (&$plaintext) {
            $plaintext = $notification->code;

            return true;
        });
        $this->post(route('security.pin.forgot.code'), ['code' => $plaintext]);

        $response = $this->put(route('security.pin'), ['pin' => '5678', 'pin_confirmation' => '5678']);

        $response->assertSessionDoesntHaveErrors();
        $this->assertTrue(Hash::check('5678', $user->fresh()->transaction_pin));
    }

    public function test_the_reset_bypass_is_single_use(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user)->post(route('security.pin.forgot'), ['password' => 'password']);

        $plaintext = null;
        Notification::assertSentTo($user, PinResetCodeMail::class, function ($notification) use (&$plaintext) {
            $plaintext = $notification->code;

            return true;
        });
        $this->post(route('security.pin.forgot.code'), ['code' => $plaintext]);
        $this->put(route('security.pin'), ['pin' => '5678', 'pin_confirmation' => '5678']);

        // A second save right after must go back to needing the (new) current PIN.
        $response = $this->put(route('security.pin'), ['pin' => '9999', 'pin_confirmation' => '9999']);

        $response->assertSessionHasErrors('current_pin');
        $this->assertTrue(Hash::check('5678', $user->fresh()->transaction_pin));
    }

    public function test_the_security_page_hides_the_current_pin_field_while_a_reset_is_verified(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user)->post(route('security.pin.forgot'), ['password' => 'password']);

        $plaintext = null;
        Notification::assertSentTo($user, PinResetCodeMail::class, function ($notification) use (&$plaintext) {
            $plaintext = $notification->code;

            return true;
        });
        $this->post(route('security.pin.forgot.code'), ['code' => $plaintext]);

        $this->get(route('security.index'))->assertDontSee('name="current_pin"', false);
    }

    public function test_reset_code_attempts_are_rate_limited(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user)->post(route('security.pin.forgot'), ['password' => 'password']);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('security.pin.forgot.code'), ['code' => 'WRONG1']);
        }

        // The plaintext code — even if it were somehow known now — no longer works either.
        $this->post(route('security.pin.forgot.code'), ['code' => 'WRONG1'])
            ->assertSessionHasErrors('code');
    }

    public function test_resend_is_cooldown_limited(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user)->post(route('security.pin.forgot'), ['password' => 'password']);

        $this->post(route('security.pin.forgot.resend'))->assertSessionHasErrors('code');
    }

    public function test_a_completed_reset_notifies_the_account_owner(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user)->post(route('security.pin.forgot'), ['password' => 'password']);

        $plaintext = null;
        Notification::assertSentTo($user, PinResetCodeMail::class, function ($notification) use (&$plaintext) {
            $plaintext = $notification->code;

            return true;
        });
        $this->post(route('security.pin.forgot.code'), ['code' => $plaintext]);
        $this->put(route('security.pin'), ['pin' => '5678', 'pin_confirmation' => '5678']);

        Notification::assertSentTo($user, SecurityAlert::class, fn ($n) => $n->title === 'Your transaction PIN was reset' && $n->requiresReview === true);
    }

    public function test_a_normal_pin_change_notifies_without_requiring_review(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->actingAs($user)->put(route('security.pin'), [
            'current_pin' => '1234', 'pin' => '5678', 'pin_confirmation' => '5678',
        ]);

        Notification::assertSentTo($user, SecurityAlert::class, fn ($n) => $n->title === 'Your transaction PIN was changed' && $n->requiresReview === false);
    }
}
