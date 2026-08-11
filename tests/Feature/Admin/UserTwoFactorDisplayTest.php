<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\SecurityAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserTwoFactorDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_admin_cannot_directly_enable_2fa_through_the_edit_form(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active', 'two_factor_enabled' => false]);

        $this->actingAs($this->admin())->put(route('admin.users.update', $user), [
            'role' => 'user',
            'status' => 'active',
            'kyc_level' => 0,
            'name' => $user->name,
            'email' => $user->email,
            'two_factor_enabled' => '1',
        ])->assertRedirect();

        $fresh = $user->fresh();
        $this->assertFalse((bool) $fresh->two_factor_enabled);
        $this->assertFalse($fresh->hasMfaEnabled());
    }

    public function test_admin_reset_two_factor_clears_every_mfa_column_and_notifies_the_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'user', 'status' => 'active',
            'two_factor_enabled' => true,
            'two_factor_secret' => 'SOMESECRET',
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['hashed-code'],
        ]);

        $this->actingAs($this->admin())->post(route('admin.users.reset-2fa', $user))->assertRedirect();

        $fresh = $user->fresh();
        $this->assertFalse((bool) $fresh->two_factor_enabled);
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_confirmed_at);
        $this->assertNull($fresh->two_factor_recovery_codes);
        $this->assertNotNull($fresh->two_factor_disabled_at);
        Notification::assertSentTo($user, SecurityAlert::class);
    }
}
