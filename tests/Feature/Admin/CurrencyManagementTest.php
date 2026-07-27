<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_non_admin_cannot_view_currencies(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.currencies.index'))->assertForbidden();
    }

    public function test_admin_can_view_and_create_currency(): void
    {
        $this->actingAs($this->admin())->post(route('admin.currencies.store'), [
            'code' => 'GHS', 'name' => 'Ghanaian Cedi', 'symbol' => 'GH₵', 'decimals' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('currencies', ['code' => 'GHS', 'name' => 'Ghanaian Cedi']);
    }

    public function test_updating_a_currency_never_changes_its_code(): void
    {
        $currency = Currency::factory()->create(['code' => 'XAF']);

        $this->actingAs($this->admin())->put(route('admin.currencies.update', $currency), [
            'code' => 'ZZZ', 'name' => 'CFA Franc', 'decimals' => 2,
        ])->assertRedirect();

        $this->assertSame('XAF', $currency->fresh()->code);
    }

    public function test_set_active_toggles_status(): void
    {
        $currency = Currency::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.currencies.active', $currency), ['is_active' => 0])
            ->assertRedirect();

        $this->assertFalse($currency->fresh()->is_active);
    }
}
