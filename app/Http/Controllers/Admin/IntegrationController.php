<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentProvider;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Central place to manage ALL third-party API credentials from the admin panel.
 * Provider keys are stored ENCRYPTED on payment_providers.credentials and
 * override the .env config at runtime (see PaymentManager / FundingManager).
 * Google / Cloudflare / SMS keys live in settings.
 */
class IntegrationController extends Controller
{
    /** Editable credential fields per provider code. */
    public const SCHEMA = [
        'mtn_momo' => ['base_url' => 'Base URL', 'subscription_key' => 'Subscription key', 'api_user' => 'API user', 'api_key' => 'API key', 'webhook_secret' => 'Webhook secret'],
        'orange_money' => ['base_url' => 'Base URL', 'client_id' => 'Client ID', 'client_secret' => 'Client secret', 'webhook_secret' => 'Webhook secret'],
        'flutterwave' => ['base_url' => 'Base URL', 'public_key' => 'Public key', 'secret_key' => 'Secret key', 'encryption_key' => 'Encryption key', 'webhook_secret' => 'Webhook secret'],
        'crypto' => ['base_url' => 'Base URL', 'api_key' => 'API key', 'webhook_secret' => 'Webhook secret'],
        'card' => ['base_url' => 'Base URL', 'api_key' => 'API key', 'webhook_secret' => 'Webhook secret'],
        'alipay' => ['base_url' => 'Base URL', 'partner_id' => 'Partner ID', 'api_key' => 'API key', 'api_secret' => 'API secret', 'webhook_secret' => 'Webhook secret'],
    ];

    public function index(): View
    {
        return view('admin.integrations.index', [
            'providers' => PaymentProvider::orderBy('kind')->orderBy('name')->get()->keyBy('code'),
            'schema' => self::SCHEMA,
        ]);
    }

    public function updateProvider(Request $request, string $code, AuditLogger $audit)
    {
        abort_unless(isset(self::SCHEMA[$code]), 404);

        $provider = PaymentProvider::where('code', $code)->firstOrFail();
        $creds = $provider->credentials ?? [];

        // Only overwrite fields the admin actually filled in (blank = keep existing secret).
        foreach (array_keys(self::SCHEMA[$code]) as $field) {
            $val = $request->input("fields.$field");
            if ($val !== null && $val !== '') {
                $creds[$field] = $val;
            }
        }

        $provider->update([
            'credentials' => $creds,
            'mode' => $request->input('mode', $provider->mode),
            'is_active' => $request->boolean('is_active'),
        ]);

        $audit->log('admin.integration.updated', "Updated {$code} API credentials", $provider);

        return back()->with('success', ucfirst(str_replace('_', ' ', $code)).' credentials saved.');
    }

    public function updateGeneral(Request $request, SettingsService $settings)
    {
        $keys = [
            'google_client_id', 'google_client_secret',
            'turnstile_site_key', 'turnstile_secret_key',
            'sms_provider', 'sms_api_key', 'sms_sender',
        ];
        foreach ($keys as $key) {
            if ($request->filled($key)) {
                $settings->set($key, $request->input($key), 'string', 'integrations');
            }
        }
        $settings->set('google_login_enabled', $request->boolean('google_login_enabled') ? '1' : '0', 'bool', 'integrations');
        $settings->set('turnstile_enabled', $request->boolean('turnstile_enabled') ? '1' : '0', 'bool', 'integrations');

        return back()->with('success', 'Integration settings saved.');
    }
}
