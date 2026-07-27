<?php

namespace Tests\Feature\Navigation;

use App\Enums\ShippingRequestStatus;
use App\Models\Agent;
use App\Models\Country;
use App\Models\ShippingQuote;
use App\Models\ShippingRequest;
use App\Models\User;
use App\Services\Shipping\ShippingRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingRequestTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    private function agent(): Agent
    {
        $user = User::factory()->create(['role' => 'agent', 'status' => 'active']);

        return Agent::factory()->approved()->create(['user_id' => $user->id]);
    }

    private function baseData(): array
    {
        return [
            'origin_country' => 'CN', 'origin_city' => 'Guangzhou',
            'destination_country' => 'CM', 'destination_city' => 'Douala',
            'package_description' => 'Electronics', 'package_currency' => 'XAF',
        ];
    }

    public function test_guest_cannot_access_shipping_requests(): void
    {
        $this->get(route('shipping-requests.index'))->assertRedirect(route('login'));
    }

    public function test_customer_can_create_and_submit_a_shipping_request(): void
    {
        $user = $this->customer();
        Country::firstOrCreate(['iso2' => 'CN'], ['name' => 'China', 'is_active' => true]);
        Country::firstOrCreate(['iso2' => 'CM'], ['name' => 'Cameroon', 'is_active' => true]);

        $response = $this->actingAs($user)->post(route('shipping-requests.store'), $this->baseData());

        $shippingRequest = ShippingRequest::firstOrFail();
        $response->assertRedirect(route('shipping-requests.show', $shippingRequest));
        $this->assertSame('awaiting_quotes', $shippingRequest->status->value);
        $this->assertSame($user->id, $shippingRequest->user_id);
    }

    public function test_customer_cannot_view_another_customers_request(): void
    {
        $owner = $this->customer();
        $attacker = $this->customer();
        $svc = app(ShippingRequestService::class);
        $req = $svc->submit($svc->createDraft($owner, $this->baseData()), $owner);

        $this->actingAs($attacker)->get(route('shipping-requests.show', $req))->assertForbidden();
    }

    public function test_agent_can_quote_on_an_open_request_and_status_becomes_quote_received(): void
    {
        $customer = $this->customer();
        $agent = $this->agent();
        $svc = app(ShippingRequestService::class);
        $req = $svc->submit($svc->createDraft($customer, $this->baseData()), $customer);

        $response = $this->actingAs($agent->user)->post(route('agent.shipping-requests.quote', $req), [
            'price' => 50000, 'eta_days' => 7, 'notes' => 'Air freight',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shipping_quotes', ['shipping_request_id' => $req->id, 'agent_id' => $agent->id, 'price' => 50000]);
        $this->assertSame('quote_received', $req->fresh()->status->value);
    }

    public function test_agent_cannot_quote_twice_but_can_update_their_quote(): void
    {
        $customer = $this->customer();
        $agent = $this->agent();
        $svc = app(ShippingRequestService::class);
        $req = $svc->submit($svc->createDraft($customer, $this->baseData()), $customer);

        $this->actingAs($agent->user)->post(route('agent.shipping-requests.quote', $req), ['price' => 50000, 'eta_days' => 7]);
        $this->actingAs($agent->user)->post(route('agent.shipping-requests.quote', $req), ['price' => 40000, 'eta_days' => 5]);

        $this->assertSame(1, ShippingQuote::where('shipping_request_id', $req->id)->count());
        $this->assertDatabaseHas('shipping_quotes', ['shipping_request_id' => $req->id, 'price' => 40000]);
    }

    public function test_accepting_a_quote_rejects_the_others_and_locks_in_the_agent(): void
    {
        $customer = $this->customer();
        $agent1 = $this->agent();
        $agent2 = $this->agent();
        $svc = app(ShippingRequestService::class);
        $req = $svc->submit($svc->createDraft($customer, $this->baseData()), $customer);
        $q1 = $svc->submitQuote($req->fresh(), $agent1, 50000, 7, null);
        $q2 = $svc->submitQuote($req->fresh(), $agent2, 45000, 10, null);

        $response = $this->actingAs($customer)->post(route('shipping-requests.quotes.accept', [$req, $q2]));

        $response->assertRedirect();
        $req->refresh();
        $this->assertSame('accepted', $req->status->value);
        $this->assertSame($q2->id, $req->accepted_quote_id);
        $this->assertSame('rejected', $q1->fresh()->status->value);
        $this->assertSame('accepted', $q2->fresh()->status->value);
    }

    public function test_customer_cannot_accept_a_quote_on_someone_elses_request(): void
    {
        $owner = $this->customer();
        $attacker = $this->customer();
        $agent = $this->agent();
        $svc = app(ShippingRequestService::class);
        $req = $svc->submit($svc->createDraft($owner, $this->baseData()), $owner);
        $quote = $svc->submitQuote($req->fresh(), $agent, 50000, 7, null);

        $this->actingAs($attacker)->post(route('shipping-requests.quotes.accept', [$req, $quote]))->assertForbidden();
    }

    public function test_only_the_winning_agent_can_advance_the_shipment(): void
    {
        $customer = $this->customer();
        $winner = $this->agent();
        $loser = $this->agent();
        $svc = app(ShippingRequestService::class);
        $req = $svc->submit($svc->createDraft($customer, $this->baseData()), $customer);
        $q1 = $svc->submitQuote($req->fresh(), $winner, 50000, 7, null);
        $svc->submitQuote($req->fresh(), $loser, 45000, 10, null);
        $svc->acceptQuote($req->fresh(), $q1->fresh(), $customer);

        $this->actingAs($loser->user)->post(route('agent.shipping-requests.advance', $req), ['status' => 'awaiting_pickup'])
            ->assertForbidden();

        $this->actingAs($winner->user)->post(route('agent.shipping-requests.advance', $req), ['status' => 'awaiting_pickup'])
            ->assertRedirect();

        $this->assertSame('awaiting_pickup', $req->fresh()->status->value);
    }

    public function test_shipment_cannot_skip_lifecycle_stages(): void
    {
        $customer = $this->customer();
        $agent = $this->agent();
        $svc = app(ShippingRequestService::class);
        $req = $svc->submit($svc->createDraft($customer, $this->baseData()), $customer);
        $quote = $svc->submitQuote($req->fresh(), $agent, 50000, 7, null);
        $svc->acceptQuote($req->fresh(), $quote->fresh(), $customer);

        $this->actingAs($agent->user)->post(route('agent.shipping-requests.advance', $req), ['status' => 'delivered'])
            ->assertSessionHasErrors('status');

        $this->assertSame('accepted', $req->fresh()->status->value);
    }

    public function test_customer_can_cancel_before_a_quote_is_accepted(): void
    {
        $customer = $this->customer();
        $svc = app(ShippingRequestService::class);
        $req = $svc->submit($svc->createDraft($customer, $this->baseData()), $customer);

        $this->actingAs($customer)->post(route('shipping-requests.cancel', $req))->assertRedirect();

        $this->assertSame('cancelled', $req->fresh()->status->value);
    }

    public function test_customer_cannot_cancel_after_a_quote_is_accepted(): void
    {
        $customer = $this->customer();
        $agent = $this->agent();
        $svc = app(ShippingRequestService::class);
        $req = $svc->submit($svc->createDraft($customer, $this->baseData()), $customer);
        $quote = $svc->submitQuote($req->fresh(), $agent, 50000, 7, null);
        $svc->acceptQuote($req->fresh(), $quote->fresh(), $customer);

        $this->actingAs($customer)->post(route('shipping-requests.cancel', $req))->assertStatus(422);

        $this->assertSame('accepted', $req->fresh()->status->value);
    }

    public function test_agent_can_withdraw_a_pending_quote(): void
    {
        $customer = $this->customer();
        $agent = $this->agent();
        $svc = app(ShippingRequestService::class);
        $req = $svc->submit($svc->createDraft($customer, $this->baseData()), $customer);
        $quote = $svc->submitQuote($req->fresh(), $agent, 50000, 7, null);

        $this->actingAs($agent->user)->post(route('agent.shipping-quotes.withdraw', $quote))->assertRedirect();

        $this->assertSame('withdrawn', $quote->fresh()->status->value);
    }
}
