<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsimProvisioning;
use App\Models\ImportSource;
use App\Services\Audit\AuditLogger;
use App\Services\Esim\EsimOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The manual-review queue this platform routes to instead of fabricating
 * activation codes (see EsimOrderService), plus the one place Airalo Partner
 * API credentials get entered and tested — sandbox by default, production is
 * just a config flip once the user has real access.
 */
class EsimController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'pending');

        $query = EsimProvisioning::with(['orderItem.order.user', 'orderItem.product', 'orderItem.variant']);

        match ($tab) {
            'ready' => $query->where('status', 'ready'),
            'failed' => $query->where('status', 'failed'),
            'all' => null,
            default => $query->where('status', 'pending_provisioning'),
        };

        ImportSource::ensureSeeded();
        $provider = ImportSource::where('code', 'esim_providers')->first();

        return view('admin.esim.index', [
            'provisionings' => $query->latest()->paginate(20)->withQueryString(),
            'activeTab' => $tab,
            'counts' => [
                'pending' => EsimProvisioning::where('status', 'pending_provisioning')->count(),
                'ready' => EsimProvisioning::where('status', 'ready')->count(),
                'failed' => EsimProvisioning::where('status', 'failed')->count(),
                'all' => EsimProvisioning::count(),
            ],
            'provider' => $provider,
            'providerEnvironment' => $provider?->credentials['environment'] ?? 'sandbox',
            'providerHasCredentials' => filled($provider?->credentials['client_id'] ?? null),
        ]);
    }

    /**
     * Save (or update) real Airalo credentials and immediately test the
     * connection for real, through EsimProviderConnector directly, not the
     * generic ProductSourceConnector-typed Import Center flow (see
     * ProductImportService::resolveConnector's fallback for why that path
     * would crash for this connector).
     */
    public function updateProvider(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string', 'max:255'],
            'environment' => ['required', 'in:sandbox,production'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        ImportSource::ensureSeeded();
        $source = ImportSource::where('code', 'esim_providers')->firstOrFail();
        $source->update(['credentials' => array_filter(array_merge($source->credentials ?? [], $data))]);

        $connector = app($source->connector_class);
        $result = $connector->testConnection($source);
        $connected = (bool) ($result['connected'] ?? false);

        $source->update([
            'status' => $connected ? 'connected' : 'connection_failed',
            'is_active' => $connected,
        ]);

        $audit->log('esim.provider.credentials_updated', 'Updated Airalo Partner API credentials', $source, ['environment' => $data['environment'], 'connected' => $connected]);

        return back()->with($connected ? 'success' : 'error', $result['message'] ?? 'Credentials saved.');
    }

    public function disconnectProvider(AuditLogger $audit)
    {
        ImportSource::ensureSeeded();
        $source = ImportSource::where('code', 'esim_providers')->firstOrFail();
        $source->update(['credentials' => null, 'status' => 'not_connected', 'is_active' => false]);

        $audit->log('esim.provider.disconnected', 'Disconnected the Airalo Partner API connection', $source);

        return back()->with('success', 'Airalo disconnected. New eSIM orders will route to manual review.');
    }

    public function rowDetail(EsimProvisioning $provisioning)
    {
        $provisioning->load(['orderItem.order.user', 'orderItem.product', 'orderItem.variant']);
        $item = $provisioning->orderItem;

        return response()->json([
            'provisioning' => [
                'id' => $provisioning->id,
                'status' => $provisioning->status,
                'provider' => $provisioning->provider,
                'provider_error' => $provisioning->provider_error,
                'admin_notes' => $provisioning->admin_notes,
                'activation_policy' => $provisioning->activation_policy,
                'created' => $provisioning->created_at->format('M j, Y g:ia'),
            ],
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
            ],
            'order' => [
                'reference' => $item->order->reference,
                'customer' => $item->order->user->name,
                'email' => $item->order->user->email,
                'total' => (float) $item->order->total,
                'currency' => $item->order->currency,
                'paid_at' => $item->order->paid_at?->format('M j, Y g:ia'),
            ],
            'variant' => $item->variant ? [
                'name' => $item->variant->name,
                'external_id' => $item->variant->external_id,
                'data_amount' => $item->variant->data_amount,
                'validity_days' => $item->variant->validity_days,
            ] : null,
        ]);
    }

    /** Staff has obtained a real activation code (provider dashboard, direct issue) and is entering it here. */
    public function complete(Request $request, EsimProvisioning $provisioning, EsimOrderService $esimOrders)
    {
        if ($provisioning->status !== 'pending_provisioning') {
            return back()->withErrors(['status' => 'This eSIM is no longer awaiting manual fulfilment.']);
        }

        $data = $request->validate([
            'provider' => ['nullable', 'string', 'max:80'],
            'iccid' => ['nullable', 'string', 'max:40'],
            'lpa_string' => ['nullable', 'string', 'max:500'],
            'sm_dp_address' => ['nullable', 'string', 'max:255'],
            'activation_code' => ['nullable', 'string', 'max:255'],
            'confirmation_code' => ['nullable', 'string', 'max:255'],
            'direct_install_url' => ['nullable', 'url', 'max:500'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $hasLpa = filled($data['lpa_string'] ?? null);
        $hasAddressPair = filled($data['sm_dp_address'] ?? null) && filled($data['activation_code'] ?? null);
        $hasInstallUrl = filled($data['direct_install_url'] ?? null);

        if (! $hasLpa && ! $hasAddressPair && ! $hasInstallUrl) {
            throw ValidationException::withMessages([
                'lpa_string' => 'Enter an LPA string, an SM-DP+ address with activation code, or a direct install URL.',
            ]);
        }

        $esimOrders->completeManualProvisioning($provisioning, $data, $request->user());

        return back()->with('success', 'eSIM marked ready, the customer has been notified.');
    }

    public function addNote(Request $request, EsimProvisioning $provisioning)
    {
        $data = $request->validate(['admin_notes' => ['required', 'string', 'max:2000']]);
        $provisioning->update(['admin_notes' => $data['admin_notes']]);

        return back()->with('success', 'Note saved.');
    }

    /** No provider can fulfil this one at all — flag it so staff can refund/cancel the order item instead of leaving it stuck. */
    public function fail(Request $request, EsimProvisioning $provisioning)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $provisioning->update([
            'status' => 'failed',
            'provider_error' => $data['reason'],
        ]);

        return back()->with('success', 'Marked as failed. Refund or cancel the order from the order page.');
    }
}
