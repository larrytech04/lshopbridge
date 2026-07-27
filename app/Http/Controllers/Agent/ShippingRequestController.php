<?php

namespace App\Http\Controllers\Agent;

use App\Enums\ShippingRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ShippingQuote;
use App\Models\ShippingRequest;
use App\Services\Shipping\ShippingRequestService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingRequestController extends Controller
{
    public function index(Request $request): View
    {
        $agent = $request->user()->agent;

        return view('agent.shipping-requests.index', [
            'openRequests' => ShippingRequest::whereIn('status', ['awaiting_quotes', 'quote_received'])
                ->whereDoesntHave('quotes', fn ($q) => $q->where('agent_id', $agent->id)->where('status', '!=', 'withdrawn'))
                ->with('user')->latest()->paginate(10, ['*'], 'open'),
            'myQuotes' => $agent->shippingQuotes()->with('shippingRequest')->latest()->paginate(10, ['*'], 'mine'),
            'assigned' => ShippingRequest::whereHas('acceptedQuote', fn ($q) => $q->where('agent_id', $agent->id))
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->with('user')->latest()->paginate(10, ['*'], 'assigned'),
        ]);
    }

    public function show(Request $request, ShippingRequest $shippingRequest): View
    {
        return view('agent.shipping-requests.show', [
            'shippingRequest' => $shippingRequest->load(['quotes.agent', 'user']),
            'myQuote' => $shippingRequest->quotes()->where('agent_id', $request->user()->agent->id)->first(),
        ]);
    }

    public function quote(Request $request, ShippingRequest $shippingRequest, ShippingRequestService $svc)
    {
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0.01'],
            'eta_days' => ['required', 'integer', 'min:1', 'max:365'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $svc->submitQuote($shippingRequest, $request->user()->agent, (float) $data['price'], (int) $data['eta_days'], $data['notes'] ?? null);

        return back()->with('success', 'Quote submitted.');
    }

    public function withdrawQuote(Request $request, ShippingQuote $quote, ShippingRequestService $svc)
    {
        $svc->withdrawQuote($quote, $request->user()->agent);

        return back()->with('success', 'Quote withdrawn.');
    }

    public function advance(Request $request, ShippingRequest $shippingRequest, ShippingRequestService $svc)
    {
        $data = $request->validate([
            'status' => ['required', 'in:awaiting_pickup,in_transit,delivered'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        $svc->advance($shippingRequest, $request->user()->agent, ShippingRequestStatus::from($data['status']), $data['tracking_number'] ?? null);

        return back()->with('success', 'Shipment updated.');
    }
}
