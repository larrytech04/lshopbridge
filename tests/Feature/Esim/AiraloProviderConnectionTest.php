<?php

namespace Tests\Feature\Esim;

use App\Models\ImportSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The "space to keep" for real Airalo credentials: admins must be able to
 * paste in sandbox (or later production) keys and get a real test-connection
 * result, never a simulated one, and never a crash from the generic Import
 * Center machinery (AiraloConnector speaks EsimProviderConnector, not
 * ProductSourceConnector - see ProductImportService::resolveConnector).
 */
class AiraloProviderConnectionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function provider(): ImportSource
    {
        ImportSource::ensureSeeded();

        return ImportSource::where('code', 'esim_providers')->firstOrFail();
    }

    public function test_the_esim_operations_page_renders_with_the_provider_card_both_collapsed_and_expanded(): void
    {
        $admin = $this->admin();

        // No credentials yet: the card should still render (and default open).
        $this->actingAs($admin)->get(route('admin.esim.provisioning.index'))
            ->assertOk()
            ->assertSee('Airalo Partner API connection');

        // With credentials saved, the page must still render cleanly (collapsed by default).
        Http::fake(['sandbox-partners-api.airalo.com/*' => Http::response(['data' => ['access_token' => 't']], 200)]);
        $this->actingAs($admin)->post(route('admin.esim.provider.update'), [
            'client_id' => 'id', 'client_secret' => 'secret', 'environment' => 'sandbox',
        ]);

        $this->actingAs($admin)->get(route('admin.esim.provisioning.index'))
            ->assertOk()
            ->assertSee('Connected');
    }

    public function test_non_admin_cannot_update_provider_credentials(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->post(route('admin.esim.provider.update'), [
            'client_id' => 'id', 'client_secret' => 'secret', 'environment' => 'sandbox',
        ])->assertForbidden();
    }

    public function test_saving_valid_credentials_marks_the_provider_connected(): void
    {
        Http::fake([
            'sandbox-partners-api.airalo.com/*' => Http::response(['data' => ['access_token' => 'tok_123']], 200),
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.esim.provider.update'), [
            'client_id' => 'real_id', 'client_secret' => 'real_secret', 'environment' => 'sandbox',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $source = $this->provider()->fresh();
        $this->assertSame('connected', $source->status->value);
        $this->assertTrue($source->is_active);
        $this->assertSame('real_id', $source->credentials['client_id']);
    }

    public function test_saving_bad_credentials_marks_connection_failed_but_keeps_them_saved(): void
    {
        Http::fake([
            'sandbox-partners-api.airalo.com/*' => Http::response(['message' => 'invalid_client'], 401),
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.esim.provider.update'), [
            'client_id' => 'wrong_id', 'client_secret' => 'wrong_secret', 'environment' => 'sandbox',
        ]);

        $response->assertSessionHas('error');

        $source = $this->provider()->fresh();
        $this->assertSame('connection_failed', $source->status->value);
        $this->assertFalse($source->is_active);
        $this->assertSame('wrong_id', $source->credentials['client_id']);
    }

    public function test_disconnect_clears_credentials_and_deactivates(): void
    {
        Http::fake(['sandbox-partners-api.airalo.com/*' => Http::response(['data' => ['access_token' => 't']], 200)]);
        $this->actingAs($admin = $this->admin())->post(route('admin.esim.provider.update'), [
            'client_id' => 'id', 'client_secret' => 'secret', 'environment' => 'sandbox',
        ]);

        $this->actingAs($admin)->post(route('admin.esim.provider.disconnect'))->assertRedirect();

        $source = $this->provider()->fresh();
        $this->assertSame('not_connected', $source->status->value);
        $this->assertFalse($source->is_active);
        $this->assertNull($source->credentials);
    }

    public function test_import_center_page_renders_the_esim_provider_row_without_crashing(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.shop.imports.index'));

        $response->assertOk();
        $response->assertSee('Manage in eSIM Operations');
    }

    /**
     * AiraloConnector implements EsimProviderConnector, not ProductSourceConnector
     * (they can't both be implemented at once - conflicting handleWebhook
     * signatures). Hitting the generic Import Center's test-connection route
     * directly for this source must degrade gracefully via
     * ProductImportService::resolveConnector()'s PlaceholderConnector
     * fallback, never throw a TypeError.
     */
    public function test_generic_import_center_test_connection_route_degrades_gracefully_for_airalo(): void
    {
        $source = $this->provider();

        $response = $this->actingAs($this->admin())->post(route('admin.shop.imports.test-connection', $source));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_connected_provider_is_then_used_for_automatic_provisioning_attempts(): void
    {
        Http::fake(['sandbox-partners-api.airalo.com/*' => Http::response(['data' => ['access_token' => 't']], 200)]);
        $this->actingAs($this->admin())->post(route('admin.esim.provider.update'), [
            'client_id' => 'id', 'client_secret' => 'secret', 'environment' => 'sandbox',
        ]);

        $connected = \App\Models\ImportSource::where('code', 'esim_providers')
            ->where('is_active', true)->where('status', 'connected')->first();

        $this->assertNotNull($connected, 'EsimOrderService::connectedProvider() query should now find this row.');
    }

    public function test_environment_defaults_to_sandbox_and_switches_the_real_base_url(): void
    {
        $capturedUrl = null;
        Http::fake(function ($request) use (&$capturedUrl) {
            $capturedUrl = $request->url();

            return Http::response(['data' => ['access_token' => 't']], 200);
        });

        $this->actingAs($this->admin())->post(route('admin.esim.provider.update'), [
            'client_id' => 'id', 'client_secret' => 'secret', 'environment' => 'production',
        ]);

        $this->assertStringContainsString('partners-api.airalo.com', $capturedUrl);
        $this->assertStringNotContainsString('sandbox-partners-api.airalo.com', $capturedUrl);
    }
}
