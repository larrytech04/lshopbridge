<?php

namespace Tests\Feature\Admin;

use App\Models\BankAccount;
use App\Models\CryptoWallet;
use App\Models\MomoNumber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_non_admin_cannot_view_deposit_accounts(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.channels.index'))->assertForbidden();
    }

    public function test_account_numbers_are_masked_in_the_list(): void
    {
        $momo = MomoNumber::factory()->create(['number' => '233241234567']);

        $response = $this->actingAs($this->admin())->get(route('admin.channels.index'));

        $response->assertOk();
        $response->assertDontSee('233241234567');
    }

    public function test_reveal_requires_a_recently_confirmed_password(): void
    {
        $momo = MomoNumber::factory()->create(['number' => '233241234567']);

        $this->actingAs($this->admin())
            ->postJson(route('admin.channels.reveal', ['type' => 'momo', 'id' => $momo->id]))
            ->assertStatus(423);
    }

    public function test_reveal_returns_real_value_once_confirmed_and_is_audited(): void
    {
        $momo = MomoNumber::factory()->create(['number' => '233241234567']);

        $response = $this->actingAs($this->admin())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson(route('admin.channels.reveal', ['type' => 'momo', 'id' => $momo->id]));

        $response->assertOk()->assertJson(['value' => '233241234567']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.deposit_account.revealed']);
    }

    public function test_archive_soft_deletes_and_restore_brings_it_back(): void
    {
        $wallet = CryptoWallet::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.channels.destroy', ['type' => 'crypto', 'id' => $wallet->id]))
            ->assertRedirect();
        $this->assertSoftDeleted('crypto_wallets', ['id' => $wallet->id]);

        $this->actingAs($this->admin())
            ->post(route('admin.channels.restore', ['type' => 'crypto', 'id' => $wallet->id]))
            ->assertRedirect();
        $this->assertDatabaseHas('crypto_wallets', ['id' => $wallet->id, 'deleted_at' => null]);
    }

    public function test_set_active_toggles_bank_account_status(): void
    {
        $bank = BankAccount::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.channels.active', ['type' => 'bank', 'id' => $bank->id]), ['is_active' => 0])
            ->assertRedirect();

        $this->assertFalse($bank->fresh()->is_active);
    }
}
