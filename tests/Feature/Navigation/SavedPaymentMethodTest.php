<?php

namespace Tests\Feature\Navigation;

use App\Models\PaymentMethod;
use App\Models\SavedPaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::create([
            'code' => 'orange_money',
            'name' => 'Orange Money',
            'type' => 'momo',
            'is_active' => true,
            'deposit_enabled' => true,
        ]);
    }

    public function test_guest_cannot_access_saved_payment_methods(): void
    {
        $this->get(route('payment-methods.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_save_a_payment_method(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $method = $this->paymentMethod();

        $response = $this->actingAs($user)->post(route('payment-methods.store'), [
            'payment_method_id' => $method->id,
            'label' => 'My Orange Money',
            'account_ref' => '677123456',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('saved_payment_methods', ['user_id' => $user->id, 'label' => 'My Orange Money']);
    }

    public function test_first_saved_method_becomes_default_automatically(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $method = $this->paymentMethod();

        $this->actingAs($user)->post(route('payment-methods.store'), [
            'payment_method_id' => $method->id,
            'label' => 'First method',
        ]);

        $this->assertDatabaseHas('saved_payment_methods', ['user_id' => $user->id, 'label' => 'First method', 'is_default' => true]);
    }

    public function test_setting_a_new_default_clears_the_previous_one(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $method = $this->paymentMethod();

        $first = SavedPaymentMethod::create(['user_id' => $user->id, 'payment_method_id' => $method->id, 'label' => 'First', 'is_default' => true]);
        $second = SavedPaymentMethod::create(['user_id' => $user->id, 'payment_method_id' => $method->id, 'label' => 'Second', 'is_default' => false]);

        $this->actingAs($user)->post(route('payment-methods.default', $second));

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_user_cannot_edit_another_users_saved_payment_method(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $attacker = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $method = $this->paymentMethod();
        $saved = SavedPaymentMethod::create(['user_id' => $owner->id, 'payment_method_id' => $method->id, 'label' => 'Owner method']);

        $this->actingAs($attacker)->put(route('payment-methods.update', $saved), ['label' => 'Hacked'])->assertForbidden();
        $this->actingAs($attacker)->delete(route('payment-methods.destroy', $saved))->assertForbidden();

        $this->assertDatabaseHas('saved_payment_methods', ['id' => $saved->id, 'label' => 'Owner method']);
    }

    public function test_masked_account_ref_only_reveals_the_last_three_characters(): void
    {
        $saved = new SavedPaymentMethod(['account_ref' => '677123456']);

        $this->assertSame('••••••456', $saved->maskedAccountRef());
    }
}
