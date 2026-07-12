<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Agent::approved()->with('warehouseCountry');

        if ($search = $request->query('q')) {
            $query->where('business_name', 'like', "%{$search}%");
        }
        if ($country = $request->query('country')) {
            $query->whereHas('countries', fn ($q) => $q->where('countries.id', $country));
        }
        if ($method = $request->query('method')) {
            $query->whereJsonContains('shipping_methods', $method);
        }

        return view('dashboard.marketplace.index', [
            'agents' => $query->orderByDesc('is_featured')->orderByDesc('rating')->paginate(9)->withQueryString(),
            'countries' => Country::active()->get(),
            'filters' => $request->only('q', 'country', 'method'),
        ]);
    }

    public function show(Agent $agent): View
    {
        abort_unless($agent->status->value === 'approved', 404);

        return view('dashboard.marketplace.show', [
            'agent' => $agent->load('warehouseCountry', 'countries', 'shippingRates.destinationCountry'),
            'reviews' => $agent->reviews()->where('status', 'approved')->with('user')->latest()->take(15)->get(),
        ]);
    }

    public function contact(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'shipping_method' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:1500'],
        ]);

        $agent->leads()->create([
            'reference' => reference('PB-LEAD'),
            'user_id' => $request->user()->id,
            'shipping_method' => $data['shipping_method'] ?? null,
            'message' => $data['message'],
            'status' => 'new',
        ]);

        return back()->with('success', 'Your request was sent to '.$agent->business_name.'.');
    }

    public function review(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $agent->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Thanks! Your review will appear after moderation.');
    }
}
