<?php

namespace Tests\Feature\Esim;

use App\Models\ImportSource;
use App\Services\Esim\Connectors\AiraloConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Exercises AiraloConnector entirely against Http::fake() — this MUST never
 * hit a real Airalo endpoint (no credentials exist yet, and the eSIM spec's
 * own testing rule is "never run live provider orders in automated tests").
 */
class AiraloConnectorTest extends TestCase
{
    use RefreshDatabase;

    private function source(): ImportSource
    {
        return ImportSource::create([
            'code' => 'esim_providers_test', 'name' => 'Airalo', 'group' => 'digital_service',
            'connector_class' => AiraloConnector::class, 'status' => 'connected', 'is_active' => true,
            'credentials' => ['client_id' => 'test-id', 'client_secret' => 'test-secret', 'environment' => 'sandbox'],
        ]);
    }

    public function test_test_connection_succeeds_when_token_endpoint_responds(): void
    {
        Http::fake([
            'sandbox-partners-api.airalo.com/v2/token' => Http::response(['data' => ['access_token' => 'tok_123']], 200),
        ]);

        $result = (new AiraloConnector)->testConnection($this->source());

        $this->assertTrue($result['connected']);
    }

    public function test_test_connection_fails_gracefully_on_auth_error(): void
    {
        Http::fake([
            'sandbox-partners-api.airalo.com/v2/token' => Http::response(['message' => 'invalid_client'], 401),
        ]);

        $result = (new AiraloConnector)->testConnection($this->source());

        $this->assertFalse($result['connected']);
    }

    public function test_create_order_sends_idempotency_key_and_package_id(): void
    {
        Http::fake([
            'sandbox-partners-api.airalo.com/v2/token' => Http::response(['data' => ['access_token' => 'tok_123']], 200),
            'sandbox-partners-api.airalo.com/v2/orders' => Http::response(['data' => ['id' => 'order_999', 'sims' => [['iccid' => '8910', 'lpa' => 'LPA:1$rsp.airalo.com$ABC']]]], 200),
        ]);

        $result = (new AiraloConnector)->createOrder($this->source(), 'pkg_123', 'idem-key-1');

        $this->assertSame('order_999', $result['id']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox-partners-api.airalo.com/v2/orders'
                && $request['package_id'] === 'pkg_123'
                && $request->hasHeader('Idempotency-Key', 'idem-key-1');
        });
    }

    public function test_retrieve_provisioning_extracts_activation_fields_from_order(): void
    {
        Http::fake([
            'sandbox-partners-api.airalo.com/v2/token' => Http::response(['data' => ['access_token' => 'tok_123']], 200),
            'sandbox-partners-api.airalo.com/v2/orders/order_999' => Http::response(['data' => [
                'id' => 'order_999',
                'sims' => [[
                    'iccid' => '8910300000000000001',
                    'lpa' => 'LPA:1$rsp.airalo.com$ABC123',
                    'rsp' => 'rsp.airalo.com',
                    'matching_id' => 'ABC123',
                    'qrcode_url' => 'https://example.com/qr.png',
                    'direct_apple_installation_url' => 'https://esimsetup.apple.com/foo',
                ]],
            ]], 200),
        ]);

        $provisioning = (new AiraloConnector)->retrieveProvisioning($this->source(), 'order_999');

        $this->assertSame('8910300000000000001', $provisioning['iccid']);
        $this->assertSame('LPA:1$rsp.airalo.com$ABC123', $provisioning['lpa_string']);
        $this->assertSame('rsp.airalo.com', $provisioning['sm_dp_address']);
    }

    public function test_get_usage_computes_used_from_total_and_remaining(): void
    {
        Http::fake([
            'sandbox-partners-api.airalo.com/v2/token' => Http::response(['data' => ['access_token' => 'tok_123']], 200),
            'sandbox-partners-api.airalo.com/v2/sims/8910/usage' => Http::response(['data' => ['total' => 5000, 'remaining' => 3200]], 200),
        ]);

        $usage = (new AiraloConnector)->getUsage($this->source(), '8910');

        $this->assertSame(5000, $usage['total_mb']);
        $this->assertSame(3200, $usage['remaining_mb']);
        $this->assertSame(1800, $usage['used_mb']);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $source = $this->source();
        $source->update(['credentials' => array_merge($source->credentials, ['webhook_secret' => 'whsec_test'])]);

        $result = (new AiraloConnector)->handleWebhook($source, ['x-airalo-signature' => ['bogus']], '{"event":"order.completed"}');

        $this->assertFalse($result['valid']);
    }

    public function test_webhook_with_valid_signature_is_accepted(): void
    {
        $source = $this->source();
        $source->update(['credentials' => array_merge($source->credentials, ['webhook_secret' => 'whsec_test'])]);
        $body = '{"event":"order.completed","order_id":"order_999"}';
        $signature = hash_hmac('sha256', $body, 'whsec_test');

        $result = (new AiraloConnector)->handleWebhook($source, ['x-airalo-signature' => [$signature]], $body);

        $this->assertTrue($result['valid']);
        $this->assertSame('order.completed', $result['event']);
    }

    public function test_capabilities_do_not_include_undeclared_cancel_or_refund(): void
    {
        $capabilities = (new AiraloConnector)->capabilities();

        $this->assertNotContains('cancelOrder', $capabilities);
        $this->assertNotContains('requestRefund', $capabilities);
    }

    public function test_calling_an_undeclared_capability_throws(): void
    {
        $this->expectException(\App\Exceptions\ConnectorCapabilityException::class);

        (new AiraloConnector)->cancelOrder($this->source(), 'order_999');
    }
}
