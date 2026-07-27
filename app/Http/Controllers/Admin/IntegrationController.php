<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * General Integrations only (Google login, Cloudflare Turnstile, SMS
 * on/off switches — all .env-backed, never stored in the database). Payment
 * provider credential editing moved to the Payment Providers page as part of
 * the Platform Configuration redesign; see ProviderAdminService.
 */
class IntegrationController extends Controller
{
    public function index(): View
    {
        return view('admin.integrations.index');
    }

    public function updateGeneral(Request $request, SettingsService $settings)
    {
        // Credential VALUES (client secrets, API keys) are .env-only by design —
        // never stored in the database. Only the on/off switches live here.
        $settings->set('google_login_enabled', $request->boolean('google_login_enabled') ? '1' : '0', 'bool', 'integrations');
        $settings->set('turnstile_enabled', $request->boolean('turnstile_enabled') ? '1' : '0', 'bool', 'integrations');

        return back()->with('success', 'Integration settings saved.');
    }
}
