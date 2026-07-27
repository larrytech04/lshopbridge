<?php

namespace App\Http\Controllers;

use App\Models\ShippingRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackShipmentController extends Controller
{
    /**
     * Looked up only within the signed-in user's own shipments — a reference
     * or tracking number is not a secret, so scoping to user_id (rather than
     * making the lookup global) is what keeps this from leaking anyone else's
     * shipment details.
     */
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $shipment = null;

        if ($q !== '') {
            $shipment = $request->user()->shippingRequests()
                ->where(fn ($query) => $query->where('reference', $q)->orWhere('tracking_number', $q))
                ->with(['acceptedQuote.agent'])
                ->first();
        }

        return view('shipments.track', ['q' => $q, 'shipment' => $shipment]);
    }
}
