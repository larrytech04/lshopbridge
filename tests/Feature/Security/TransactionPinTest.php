<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The transaction PIN must be exactly 4 digits everywhere — its only job in
 * this app is authorizing an actual transfer (see
 * resources/views/dashboard/funding/create.blade.php), which only ever
 * collects 4, so a longer PIN set here could never actually be entered
 * there. It is never part of login or idle-session reauth (see
 * ReauthService). (The withdrawal flow that used to have its own such input
 * was removed 2026-08-12.)
 */
class TransactionPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_4_digit_pin_can_be_set_for_the_first_time(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => null]);

        $response = $this->actingAs($user)->put(route('security.pin'), [
            'pin' => '1234', 'pin_confirmation' => '1234',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertTrue(Hash::check('1234', $user->fresh()->transaction_pin));
    }

    public function test_a_5_digit_pin_is_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => null]);

        $response = $this->actingAs($user)->put(route('security.pin'), [
            'pin' => '12345', 'pin_confirmation' => '12345',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertNull($user->fresh()->transaction_pin);
    }

    public function test_a_6_digit_pin_is_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => null]);

        $response = $this->actingAs($user)->put(route('security.pin'), [
            'pin' => '123456', 'pin_confirmation' => '123456',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertNull($user->fresh()->transaction_pin);
    }

    public function test_changing_an_existing_pin_requires_the_correct_current_pin(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);

        $response = $this->actingAs($user)->put(route('security.pin'), [
            'current_pin' => '0000', 'pin' => '5678', 'pin_confirmation' => '5678',
        ]);

        $response->assertSessionHasErrors('current_pin');
        $this->assertTrue(Hash::check('1234', $user->fresh()->transaction_pin));
    }

    public function test_changing_an_existing_pin_with_the_correct_current_pin_succeeds(): void
    {
        $user = User::factory()->create(['status' => 'active', 'transaction_pin' => '1234']);

        $response = $this->actingAs($user)->put(route('security.pin'), [
            'current_pin' => '1234', 'pin' => '5678', 'pin_confirmation' => '5678',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertTrue(Hash::check('5678', $user->fresh()->transaction_pin));
    }
}
