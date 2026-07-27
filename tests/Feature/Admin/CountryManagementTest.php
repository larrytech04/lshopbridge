<?php

namespace Tests\Feature\Admin;

use App\Enums\CountryLaunchStatus;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_non_admin_cannot_view_countries(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.countries.index'))->assertForbidden();
    }

    public function test_admin_can_view_countries_and_regions(): void
    {
        Country::factory()->create(['name' => 'Cameroon']);

        $this->actingAs($this->admin())
            ->get(route('admin.countries.index'))
            ->assertOk()
            ->assertSee('Countries & Regions')
            ->assertSee('Cameroon');
    }

    public function test_setting_launch_status_to_restricted_syncs_legacy_blocked_flag(): void
    {
        $country = Country::factory()->create(['launch_status' => 'active', 'is_active' => true, 'is_blocked' => false]);

        $this->actingAs($this->admin())
            ->post(route('admin.countries.status', $country), ['launch_status' => 'restricted'])
            ->assertRedirect();

        $country->refresh();
        $this->assertSame(CountryLaunchStatus::Restricted, $country->launch_status);
        $this->assertFalse($country->is_active);
        $this->assertTrue($country->is_blocked);
    }

    public function test_setting_launch_status_to_disabled_deactivates_without_blocking(): void
    {
        $country = Country::factory()->create(['launch_status' => 'active', 'is_active' => true, 'is_blocked' => false]);

        $this->actingAs($this->admin())
            ->post(route('admin.countries.status', $country), ['launch_status' => 'disabled'])
            ->assertRedirect();

        $country->refresh();
        $this->assertFalse($country->is_active);
        $this->assertFalse($country->is_blocked);
    }

    public function test_countries_have_no_delete_route(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.countries.destroy'));
    }

    public function test_update_persists_admin_notes(): void
    {
        $country = Country::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.countries.update', $country), [
            'name' => $country->name, 'iso2' => $country->iso2, 'launch_status' => 'active',
            'admin_notes' => 'Launched after regulatory review.',
        ])->assertRedirect();

        $this->assertSame('Launched after regulatory review.', $country->fresh()->admin_notes);
    }
}
