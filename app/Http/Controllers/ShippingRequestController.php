<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\ShippingQuote;
use App\Models\ShippingRequest;
use App\Services\Shipping\ShippingRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ShippingRequestController extends Controller
{
    public function index(Request $request): View
    {
        return view('shipping-requests.index', [
            'requests' => $request->user()->shippingRequests()->withCount('quotes')->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('shipping-requests.create', [
            'countries' => Country::active()->orderBy('name')->get(['id', 'name', 'iso2', 'flag_emoji']),
        ]);
    }

    public function store(Request $request, ShippingRequestService $svc): RedirectResponse
    {
        $data = $request->validate([
            'origin_country' => ['required', 'string', 'size:2'],
            'origin_city' => ['required', 'string', 'max:100'],
            'origin_address' => ['nullable', 'string', 'max:500'],
            'destination_country' => ['required', 'string', 'size:2'],
            'destination_city' => ['required', 'string', 'max:100'],
            'destination_address' => ['nullable', 'string', 'max:500'],
            'package_description' => ['required', 'string', 'max:500'],
            'package_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'package_value' => ['nullable', 'numeric', 'min:0'],
            'package_currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'documents.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if ($request->hasFile('documents')) {
            $data['documents'] = collect($request->file('documents'))
                ->map(fn ($file) => $file->store('shipping-requests', 'private'))
                ->values()->all();
        }

        $shippingRequest = $svc->createDraft($request->user(), $data);
        $svc->submit($shippingRequest, $request->user());

        return redirect()->route('shipping-requests.show', $shippingRequest)->with('success', __('Shipping request submitted. Agents can now send you quotes.'));
    }

    public function show(Request $request, ShippingRequest $shippingRequest): View
    {
        abort_unless($shippingRequest->user_id === $request->user()->id, 403);

        $shippingRequest->update(['customer_viewed_at' => now()]);

        return view('shipping-requests.show', [
            'shippingRequest' => $shippingRequest->load(['quotes' => fn ($q) => $q->with('agent')->orderBy('price')]),
        ]);
    }

    public function acceptQuote(Request $request, ShippingRequest $shippingRequest, ShippingQuote $quote, ShippingRequestService $svc): RedirectResponse
    {
        abort_unless($shippingRequest->user_id === $request->user()->id, 403);

        $svc->acceptQuote($shippingRequest, $quote, $request->user());

        return back()->with('success', __('Quote accepted. The agent will be in touch to arrange pickup.'));
    }

    public function cancel(Request $request, ShippingRequest $shippingRequest, ShippingRequestService $svc): RedirectResponse
    {
        $svc->cancel($shippingRequest, $request->user());

        return back()->with('success', __('Shipping request cancelled.'));
    }

    public function downloadDocument(Request $request, ShippingRequest $shippingRequest, int $index)
    {
        $isOwner = $shippingRequest->user_id === $request->user()->id;
        $isAssignedAgent = $shippingRequest->acceptedQuote?->agent?->user_id === $request->user()->id;
        abort_unless($isOwner || $isAssignedAgent, 403);

        $path = ($shippingRequest->documents ?? [])[$index] ?? null;
        abort_unless($path && Storage::disk('private')->exists($path), 404);

        return Storage::disk('private')->download($path);
    }
}
