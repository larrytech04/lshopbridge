<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use App\Services\Shipping\ShippingRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackShipmentTest extends TestCase
{
    use RefreshDatabase;

    private function shipmentFor(User $user): \App\Models\ShippingRequest
    {
        $svc = app(ShippingRequestService::class);

        return $svc->submit($svc->createDraft($user, [
            'origin_country' => 'CN', 'origin_city' => 'Guangzhou',
            'destination_country' => 'CM', 'destination_city' => 'Douala',
            'package_description' => 'Test package', 'package_currency' => 'XAF',
        ]), $user);
    }

    public function test_guest_cannot_access_track_shipment(): void
    {
        $this->get(route('shipments.track'))->assertRedirect(route('login'));
    }

    public function test_customer_can_find_their_own_shipment_by_reference(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $shipment = $this->shipmentFor($user);

        $response = $this->actingAs($user)->get(route('shipments.track', ['q' => $shipment->reference]));

        $response->assertOk();
        $response->assertSee('Guangzhou');
        $response->assertSee('Douala');
    }

    public function test_customer_cannot_find_another_customers_shipment(): void
    {
        $owner = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $stranger = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $shipment = $this->shipmentFor($owner);

        $response = $this->actingAs($stranger)->get(route('shipments.track', ['q' => $shipment->reference]));

        $response->assertOk();
        $response->assertDontSee('Guangzhou');
        $response->assertDontSee('Douala');
        $response->assertSee('No matching shipment found');
    }

    public function test_unknown_reference_shows_not_found_state(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $response = $this->actingAs($user)->get(route('shipments.track', ['q' => 'PB-SHR-DOESNOTEXIST']));

        $response->assertOk();
        $response->assertSee('No matching shipment found');
    }
}
