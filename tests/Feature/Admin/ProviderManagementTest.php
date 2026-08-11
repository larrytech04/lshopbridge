<?php

namespace Tests\Feature\Admin;

use App\Models\PaymentProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_non_admin_cannot_view_providers(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.providers.index'))->assertForbidden();
    }

    public function test_admin_can_view_providers(): void
    {
        PaymentProvider::factory()->create(['code' => 'mtn_momo', 'name' => 'MTN Mobile Money']);

        $this->actingAs($this->admin())
            ->get(route('admin.providers.index'))
            ->assertOk()
            ->assertSee('MTN Mobile Money');
    }

    // --------------------------------------------------------------- security

    public function test_credentials_are_never_serialized_to_json(): void
    {
        $provider = PaymentProvider::factory()->create(['credentials' => ['api_key' => 'super-secret-value']]);

        $json = $provider->toArray();

        $this->assertArrayNotHasKey('credentials', $json);
    }

    public function test_updating_credentials_requires_a_recently_confirmed_password(): void
    {
        $provider = PaymentProvider::factory()->create(['code' => 'mtn_momo']);

        $response = $this->actingAs($this->admin())->put(route('admin.providers.update', $provider), [
            'name' => 'MTN Mobile Money', 'mode' => 'sandbox', 'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.password.confirm'));
    }

    public function test_updating_credentials_succeeds_once_password_is_confirmed(): void
    {
        $provider = PaymentProvider::factory()->create(['code' => 'mtn_momo']);

        $this->actingAs($this->admin())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.providers.update', $provider), [
                'name' => 'Renamed Provider', 'mode' => 'sandbox', 'is_active' => 1,
                'credentials' => ['api_key' => 'new-key-value'],
            ])
            ->assertRedirect();

        $provider->refresh();
        $this->assertSame('Renamed Provider', $provider->name);
        $this->assertSame('new-key-value', $provider->credentials['api_key']);
    }

    public function test_blank_credential_field_keeps_existing_secret(): void
    {
        $provider = PaymentProvider::factory()->create(['code' => 'mtn_momo', 'credentials' => ['api_key' => 'keep-me']]);

        $this->actingAs($this->admin())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.providers.update', $provider), [
                'name' => $provider->name, 'mode' => 'sandbox', 'is_active' => 1,
                'credentials' => ['api_key' => ''],
            ])
            ->assertRedirect();

        $this->assertSame('keep-me', $provider->fresh()->credentials['api_key']);
    }

    public function test_test_connection_requires_a_recently_confirmed_password(): void
    {
        $provider = PaymentProvider::factory()->create(['code' => 'mtn_momo']);

        $this->actingAs($this->admin())
            ->post(route('admin.providers.test-connection', $provider))
            ->assertRedirect(route('admin.password.confirm'));
    }

    public function test_test_connection_persists_result_honestly(): void
    {
        $provider = PaymentProvider::factory()->create(['code' => 'mtn_momo', 'mode' => 'sandbox']);

        $this->actingAs($this->admin())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.providers.test-connection', $provider))
            ->assertRedirect();

        $provider->refresh();
        $this->assertNotNull($provider->last_tested_at);
        $this->assertTrue($provider->last_test_ok);
    }

    // --------------------------------------------------------------- lifecycle

    public function test_archive_soft_deletes_and_restore_brings_it_back(): void
    {
        $provider = PaymentProvider::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.providers.destroy', $provider))->assertRedirect();
        $this->assertSoftDeleted('payment_providers', ['id' => $provider->id]);

        $this->actingAs($this->admin())->post(route('admin.providers.restore', $provider))->assertRedirect();
        $this->assertDatabaseHas('payment_providers', ['id' => $provider->id, 'deleted_at' => null]);
    }

    public function test_set_active_toggles_status(): void
    {
        $provider = PaymentProvider::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.providers.active', $provider), ['is_active' => 0])
            ->assertRedirect();

        $this->assertFalse($provider->fresh()->is_active);
    }
}
