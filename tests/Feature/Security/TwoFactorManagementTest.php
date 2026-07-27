<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Security\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorManagementTest extends TestCase
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

    private function user(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active', 'password' => Hash::make('password')]);
    }

    public function test_enrollment_page_generates_a_pending_secret_and_shows_manual_key(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->get(route('security.two-factor.show'));

        $response->assertOk();
        $response->assertViewIs('security.two-factor.enroll');
        $this->assertNotEmpty(session('pending_2fa_secret'));
    }

    public function test_confirming_with_the_correct_code_enables_mfa_and_flashes_recovery_codes(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get(route('security.two-factor.show'));
        $secret = session('pending_2fa_secret');

        $response = $this->actingAs($user)->post(route('security.two-factor.confirm'), [
            'code' => $this->totpCodeFor($secret),
        ]);

        $response->assertRedirect(route('security.two-factor.show'));
        $response->assertSessionHas('recovery_codes');
        $this->assertTrue($user->fresh()->hasMfaEnabled());
    }

    public function test_confirming_with_the_wrong_code_does_not_enable_mfa(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get(route('security.two-factor.show'));

        $response = $this->actingAs($user)->post(route('security.two-factor.confirm'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasMfaEnabled());
    }

    public function test_disabling_requires_the_correct_current_password(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $user = User::factory()->create([
            'role' => 'user', 'status' => 'active', 'password' => Hash::make('password'),
            'two_factor_enabled' => true, 'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [Hash::make('X')],
        ]);

        $wrong = $this->actingAs($user)->delete(route('security.two-factor.disable'), ['password' => 'not-the-password']);
        $wrong->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->hasMfaEnabled());

        $right = $this->actingAs($user)->delete(route('security.two-factor.disable'), ['password' => 'password']);
        $right->assertRedirect(route('security.two-factor.show'));

        $fresh = $user->fresh();
        $this->assertFalse($fresh->hasMfaEnabled());
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_confirmed_at);
        $this->assertNull($fresh->two_factor_recovery_codes);
        $this->assertNotNull($fresh->two_factor_disabled_at);
    }

    public function test_regenerating_recovery_codes_requires_password_and_replaces_the_old_ones(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $oldHash = Hash::make('OLD-CODE');
        $user = User::factory()->create([
            'role' => 'user', 'status' => 'active', 'password' => Hash::make('password'),
            'two_factor_enabled' => true, 'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [$oldHash],
        ]);

        $response = $this->actingAs($user)->post(route('security.two-factor.recovery-codes'), ['password' => 'password']);

        $response->assertSessionHas('recovery_codes');
        $newCodes = $user->fresh()->two_factor_recovery_codes;
        $this->assertNotSame([$oldHash], $newCodes);
        $this->assertCount(8, $newCodes);
    }
}
