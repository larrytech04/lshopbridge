<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\ShippingRate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingRateController extends Controller
{
    public function index(Request $request): View
    {
        $agent = $request->user()->agent;

        return view('agent.rates', [
            'agent' => $agent,
            'rates' => $agent->shippingRates()->with('destinationCountry')->latest()->get(),
            'countries' => Country::active()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $request->user()->agent->shippingRates()->create($data);

        return back()->with('success', 'Shipping rate added.');
    }

    public function update(Request $request, ShippingRate $rate)
    {
        abort_unless($rate->agent_id === $request->user()->agent->id, 403);
        $rate->update($this->validated($request));

        return back()->with('success', 'Shipping rate updated.');
    }

    public function destroy(Request $request, ShippingRate $rate)
    {
        abort_unless($rate->agent_id === $request->user()->agent->id, 403);
        $rate->delete();

        return back()->with('success', 'Shipping rate removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'method' => ['required', 'in:air,sea,express'],
            'destination_country_id' => ['nullable', 'exists:countries,id'],
            'price_per_kg' => ['nullable', 'numeric', 'min:0'],
            'price_per_cbm' => ['nullable', 'numeric', 'min:0'],
            'min_charge' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'estimated_days_min' => ['nullable', 'integer', 'min:1'],
            'estimated_days_max' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
