<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentProvider;
use App\Services\Admin\ProviderAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentProviderController extends Controller
{
    public function __construct(private ProviderAdminService $service) {}

    public function index(): View
    {
        return view('admin.providers.index', [
            'providers' => PaymentProvider::withTrashed()->orderBy('priority')->orderBy('name')->get(),
            'schema' => ProviderAdminService::CREDENTIAL_SCHEMA,
            'summary' => $this->service->summary(),
        ]);
    }

    /** Sensitive: credential changes require a recently-confirmed password (see routes/web.php). */
    public function update(Request $request, PaymentProvider $provider)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'mode' => ['required', 'in:sandbox,live'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'countries' => ['nullable', 'array'],
            'countries.*' => ['string', 'size:2'],
            'currencies' => ['nullable', 'array'],
            'currencies.*' => ['string', 'size:3'],
            'credentials' => ['nullable', 'array'],
        ]);

        $this->service->update($provider, $data, $request->user());

        return back()->with('success', 'Provider updated.');
    }

    /** Sensitive: same password-confirmation gate as update(). Real, non-money-moving check — never simulated. */
    public function testConnection(Request $request, PaymentProvider $provider)
    {
        $result = $this->service->testConnection($provider, $request->user());

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function setActive(Request $request, PaymentProvider $provider)
    {
        $this->service->setActive($provider, $request->boolean('is_active'), $request->user());

        return back()->with('success', 'Provider status updated.');
    }

    /** Archive-not-delete: soft-deletes so providers referenced by historical transactions are never actually removed. */
    public function destroy(Request $request, PaymentProvider $provider)
    {
        $this->service->archive($provider, $request->user());

        return back()->with('success', 'Provider archived.');
    }

    public function restore(Request $request, PaymentProvider $provider)
    {
        $this->service->restore($provider, $request->user());

        return back()->with('success', 'Provider restored.');
    }
}
