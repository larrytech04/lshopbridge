<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_non_admin_cannot_view_banners(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.banners.index'))->assertForbidden();
    }

    public function test_admin_can_create_banner_with_targeting(): void
    {
        $country = Country::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.banners.store'), [
            'title' => 'Verified-only promo', 'type' => 'promo', 'position' => 'home',
            'audience' => 'verified', 'country_id' => $country->id, 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('banners', ['title' => 'Verified-only promo', 'audience' => 'verified', 'country_id' => $country->id]);
    }

    public function test_archive_soft_deletes_and_restore_brings_it_back(): void
    {
        $banner = Banner::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.banners.destroy', $banner))->assertRedirect();
        $this->assertSoftDeleted('banners', ['id' => $banner->id]);

        $this->actingAs($this->admin())->post(route('admin.banners.restore', $banner))->assertRedirect();
        $this->assertDatabaseHas('banners', ['id' => $banner->id, 'deleted_at' => null]);
    }

    // --------------------------------------------------------------- targeting enforcement

    public function test_expired_banner_is_not_visible(): void
    {
        $banner = Banner::factory()->create(['ends_at' => now()->subDay()]);

        $this->assertFalse($banner->isVisibleTo(null));
    }

    public function test_future_banner_is_not_yet_visible(): void
    {
        $banner = Banner::factory()->create(['starts_at' => now()->addDay()]);

        $this->assertFalse($banner->isVisibleTo(null));
    }

    public function test_guest_only_banner_is_hidden_from_logged_in_users(): void
    {
        $banner = Banner::factory()->create(['audience' => 'guest']);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->assertTrue($banner->isVisibleTo(null));
        $this->assertFalse($banner->isVisibleTo($user));
    }

    public function test_country_targeted_banner_only_visible_to_that_country(): void
    {
        $cameroon = Country::factory()->create();
        $nigeria = Country::factory()->create();
        $banner = Banner::factory()->create(['country_id' => $cameroon->id]);

        $userInCameroon = User::factory()->create(['role' => 'user', 'status' => 'active', 'country_id' => $cameroon->id]);
        $userInNigeria = User::factory()->create(['role' => 'user', 'status' => 'active', 'country_id' => $nigeria->id]);

        $this->assertTrue($banner->isVisibleTo($userInCameroon));
        $this->assertFalse($banner->isVisibleTo($userInNigeria));
    }

    public function test_first_visible_returns_at_most_one_banner(): void
    {
        Banner::factory()->create(['audience' => 'guest', 'sort' => 1]);
        Banner::factory()->create(['audience' => 'everyone', 'sort' => 2]);

        $visible = app(\App\Services\Admin\BannerAdminService::class)->firstVisible(Banner::active()->get(), null);

        $this->assertNotNull($visible);
    }
}
