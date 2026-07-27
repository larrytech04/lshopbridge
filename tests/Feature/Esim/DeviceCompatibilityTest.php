<?php

namespace Tests\Feature\Esim;

use App\Models\EsimDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EsimDevice::create([
            'brand' => 'Apple', 'model' => 'iPhone 14', 'esim_supported' => true,
            'min_os_version' => 'iOS 16', 'dual_sim_support' => true, 'max_active_esims' => 8,
            'installation_method' => 'qr_manual', 'source' => 'manual', 'status' => 'active',
        ]);
        EsimDevice::create([
            'brand' => 'Apple', 'model' => 'iPhone X', 'esim_supported' => false,
            'source' => 'manual', 'status' => 'active',
        ]);
        EsimDevice::create([
            'brand' => 'Nokia', 'model' => 'Discontinued Model', 'esim_supported' => false,
            'source' => 'manual', 'status' => 'disabled',
        ]);
    }

    public function test_checker_page_loads_with_active_brands(): void
    {
        $response = $this->get(route('esim.compatibility.index'));

        $response->assertOk();
        $response->assertSee('Apple');
    }

    public function test_models_endpoint_returns_only_active_devices_for_the_brand(): void
    {
        $response = $this->getJson(route('esim.compatibility.models', ['brand' => 'Apple']));

        $response->assertOk();
        $models = collect($response->json('models'))->pluck('model');
        $this->assertTrue($models->contains('iPhone 14'));
        $this->assertTrue($models->contains('iPhone X'));
    }

    public function test_disabled_devices_are_excluded_from_brand_list(): void
    {
        $response = $this->getJson(route('esim.compatibility.models', ['brand' => 'Nokia']));

        $response->assertOk();
        $this->assertEmpty($response->json('models'));
    }

    public function test_check_returns_real_supported_flag_and_notes(): void
    {
        $response = $this->postJson(route('esim.compatibility.check'), ['brand' => 'Apple', 'model' => 'iPhone 14']);

        $response->assertOk()->assertJson([
            'found' => true,
            'esim_supported' => true,
            'min_os_version' => 'iOS 16',
        ]);
    }

    public function test_check_reports_unsupported_device_honestly(): void
    {
        $response = $this->postJson(route('esim.compatibility.check'), ['brand' => 'Apple', 'model' => 'iPhone X']);

        $response->assertOk()->assertJson(['found' => true, 'esim_supported' => false]);
    }

    public function test_check_for_unknown_device_never_fabricates_a_supported_answer(): void
    {
        $response = $this->postJson(route('esim.compatibility.check'), ['brand' => 'Apple', 'model' => 'iPhone 999 Ultra']);

        $response->assertOk()->assertJson(['found' => false]);
        $this->assertArrayNotHasKey('esim_supported', $response->json());
    }
}
