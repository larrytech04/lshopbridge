<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentProvider;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentProviderController extends Controller
{
    public function index(): View
    {
        return view('admin.providers.index', [
            'providers' => PaymentProvider::all(),
            // Config-level view so admins see which env keys back each provider.
            'config' => config('payments.providers'),
            'fundingConfig' => config('funding.providers'),
        ]);
    }

    public function update(Request $request, PaymentProvider $provider, AuditLogger $audit)
    {
        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'mode' => ['required', 'in:sandbox,live'],
        ]);

        $provider->update([
            'is_active' => $request->boolean('is_active'),
            'mode' => $data['mode'],
        ]);

        $audit->log('admin.provider.updated', "Provider {$provider->code} → {$data['mode']}", $provider, $data);

        return back()->with('success', 'Provider updated. (API secrets remain in your .env file.)');
    }
}
