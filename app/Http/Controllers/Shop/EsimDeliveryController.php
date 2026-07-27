<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\EsimProvisioning;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Owner-gated eSIM install pages. Deliberately its own controller rather than
 * an extension of SecureFileController: there is no file on disk here, the QR
 * image is generated on the fly from encrypted DB fields and never written to
 * a public path or given a shareable URL (spec section 17, QR code security).
 */
class EsimDeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $provisionings = EsimProvisioning::whereHas('orderItem.order', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('orderItem.product', 'orderItem.order')
            ->latest()
            ->paginate(12);

        return view('shop.esim.index', ['provisionings' => $provisionings]);
    }

    public function show(Request $request, EsimProvisioning $provisioning): View
    {
        $this->authorizeOwner($request, $provisioning);
        $provisioning->load('orderItem.product', 'orderItem.order');

        if ($provisioning->status === 'ready' && $provisioning->hasWorkingActivationData()) {
            $provisioning->recordQrReveal();
        }

        return view('shop.esim.show', ['provisioning' => $provisioning]);
    }

    public function qr(Request $request, EsimProvisioning $provisioning): Response
    {
        $this->authorizeOwner($request, $provisioning);
        abort_unless($provisioning->hasWorkingActivationData(), 404);

        $data = $provisioning->lpa_string ?: $provisioning->direct_install_url;
        $result = (new PngWriter())->write(new QrCode(data: $data, size: 320, margin: 12));

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function authorizeOwner(Request $request, EsimProvisioning $provisioning): void
    {
        $ownerId = $provisioning->orderItem->order->user_id;
        abort_unless($request->user()->id === $ownerId || $request->user()->isAdmin(), 403);
    }
}
