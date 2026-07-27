<?php

namespace Tests\Feature\Navigation;

use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_wishlist_routes(): void
    {
        $product = ShopProduct::factory()->create(['is_active' => true]);

        $this->get(route('wishlist.index'))->assertRedirect(route('login'));
        $this->post(route('wishlist.store', $product))->assertRedirect(route('login'));
    }

    public function test_user_can_add_a_product_to_their_wishlist(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $product = ShopProduct::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post(route('wishlist.store', $product));

        $response->assertRedirect();
        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'shop_product_id' => $product->id]);
    }

    public function test_adding_the_same_product_twice_does_not_duplicate(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $product = ShopProduct::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('wishlist.store', $product));
        $this->actingAs($user)->post(route('wishlist.store', $product));

        $this->assertSame(1, Wishlist::where('user_id', $user->id)->where('shop_product_id', $product->id)->count());
    }

    public function test_user_can_remove_a_product_from_their_wishlist(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $product = ShopProduct::factory()->create(['is_active' => true]);
        Wishlist::create(['user_id' => $user->id, 'shop_product_id' => $product->id]);

        $response = $this->actingAs($user)->delete(route('wishlist.destroy', $product));

        $response->assertRedirect();
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id, 'shop_product_id' => $product->id]);
    }

    public function test_cannot_wishlist_an_inactive_product(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $product = ShopProduct::factory()->create(['is_active' => false]);

        $this->actingAs($user)->post(route('wishlist.store', $product))->assertNotFound();
    }

    public function test_wishlist_index_only_shows_the_current_users_items(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $other = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $mine = ShopProduct::factory()->create(['is_active' => true, 'name' => 'Mine']);
        $theirs = ShopProduct::factory()->create(['is_active' => true, 'name' => 'Theirs']);
        Wishlist::create(['user_id' => $user->id, 'shop_product_id' => $mine->id]);
        Wishlist::create(['user_id' => $other->id, 'shop_product_id' => $theirs->id]);

        $response = $this->actingAs($user)->get(route('wishlist.index'));

        $response->assertOk();
        $response->assertSee('Mine');
        $response->assertDontSee('Theirs');
    }
}
