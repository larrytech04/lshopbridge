<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Agent::approved()->with(['warehouseCountry', 'countries', 'shippingRates', 'user']);

        if ($search = $request->query('q')) {
            $query->where('business_name', 'like', "%{$search}%");
        }

        if ($country = $request->query('country')) {
            $query->whereHas('countries', fn ($q) => $q->where('countries.id', $country));
        }

        if ($method = $request->query('method')) {
            $query->whereJsonContains('shipping_methods', $method);
        }

        return view('public.agents.index', [
            'agents' => $query->orderByDesc('is_featured')->orderByDesc('rating')->paginate(9)->withQueryString(),
            'countries' => Country::active()->get(),
            'filters' => $request->only('q', 'country', 'method'),
        ]);
    }

    public function show(Agent $agent): View
    {
        abort_unless($agent->status->value === 'approved', 404);

        return view('public.agents.show', [
            'agent' => $agent->load(['warehouseCountry', 'countries', 'shippingRates.destinationCountry', 'user']),
            'reviews' => $agent->reviews()->where('status', 'approved')->with('user')->latest()->take(10)->get(),
        ]);
    }
}
