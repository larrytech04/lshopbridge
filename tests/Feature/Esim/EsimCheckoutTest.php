<?php

namespace Tests\Feature\Esim;

use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\ShopVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EsimCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function esimVariant(): ShopVariant
    {
        $category = ShopCategory::factory()->create();
        $product = ShopProduct::factory()->create(['shop_category_id' => $category->id, 'type' => 'esim', 'name' => 'China Travel eSIM']);

        return ShopVariant::factory()->create(['shop_product_id' => $product->id, 'price' => 10000]);
    }

    private function fundedUser(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $wallet = $user->primaryWallet('XAF');
        $wallet->update(['balance' => 100000]);

        return $user;
    }

    public function test_checkout_page_shows_the_esim_pre_purchase_checklist(): void
    {
        $user = $this->fundedUser();
        $variant = $this->esimVariant();
        $this->actingAs($user)->post(route('cart.add'), ['variant_id' => $variant->id]);

        $response = $this->actingAs($user)->get(route('shop.checkout'));

        $response->assertOk();
        $response->assertSee('Before you pay: eSIM plans');
        $response->assertSee('esim_device_confirmed', false);
    }

    public function test_checkout_is_rejected_without_confirming_device_compatibility(): void
    {
        $user = $this->fundedUser();
        $variant = $this->esimVariant();
        $this->actingAs($user)->post(route('cart.add'), ['variant_id' => $variant->id]);

        $response = $this->actingAs($user)->post(route('shop.checkout.store'), [
            'payment_source' => 'wallet',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('esim_device_confirmed');
    }

    public function test_checkout_succeeds_once_confirmed_and_gives_an_honest_status_message(): void
    {
        $user = $this->fundedUser();
        $variant = $this->esimVariant();
        $this->actingAs($user)->post(route('cart.add'), ['variant_id' => $variant->id]);

        $response = $this->actingAs($user)->post(route('shop.checkout.store'), [
            'payment_source' => 'wallet',
            'email' => $user->email,
            'esim_device_confirmed' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertStringNotContainsString('ready!', session('success'));
    }

    public function test_checkout_for_a_non_esim_cart_does_not_require_confirmation(): void
    {
        $user = $this->fundedUser();
        $category = ShopCategory::factory()->create();
        $product = ShopProduct::factory()->create(['shop_category_id' => $category->id, 'type' => 'giftcard']);
        $variant = ShopVariant::factory()->create(['shop_product_id' => $product->id, 'price' => 5000]);
        $this->actingAs($user)->post(route('cart.add'), ['variant_id' => $variant->id]);

        $response = $this->actingAs($user)->post(route('shop.checkout.store'), [
            'payment_source' => 'wallet',
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors('esim_device_confirmed');
    }
}
