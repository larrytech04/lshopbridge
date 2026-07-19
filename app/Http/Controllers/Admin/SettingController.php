<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(SettingsService $settings): View
    {
        return view('admin.settings.index', ['settings' => $settings->all()]);
    }

    public function update(Request $request, SettingsService $settings, AuditLogger $audit)
    {
        // Known settings with their types. Toggles default to false when unchecked.
        $schema = [
            // Branding & contact
            'site_name' => 'string',
            'tagline' => 'string',
            'support_email' => 'string',
            'support_phone' => 'string',
            // Social links
            'social_whatsapp' => 'string',
            'social_x' => 'string',
            'social_instagram' => 'string',
            'social_facebook' => 'string',
            'social_tiktok' => 'string',
            'social_discord' => 'string',
            'social_email' => 'string',
            'social_phone' => 'string',
            'whatsapp_link' => 'string',
            'telegram_link' => 'string',
            // Email / SMTP
            'mail_host' => 'string',
            'mail_port' => 'string',
            'mail_username' => 'string',
            'mail_encryption' => 'string',
            'mail_from_address' => 'string',
            'mail_from_name' => 'string',
            // Integrations
            'google_analytics_id' => 'string',
            'google_site_verification' => 'string',
            'bing_site_verification' => 'string',
            'livechat_embed' => 'string',
            // Automation & controls
            'payments_automation_enabled' => 'bool',
            'funding_automation_enabled' => 'bool',
            'require_proof_of_payment' => 'bool',
            'maintenance_mode' => 'bool',
        ];

        foreach ($schema as $key => $type) {
            $value = $type === 'bool' ? $request->boolean($key) : $request->input($key, '');
            $settings->set($key, $value, $type, 'general');
        }

        // SMTP password is a secret, store encrypted, and only update when a new
        // value is provided (blank means "keep the existing one").
        if (filled($request->input('mail_password'))) {
            $settings->set('mail_password', Crypt::encryptString($request->input('mail_password')), 'string', 'general');
        }

        // Logo / favicon uploads → public/branding.
        foreach (['site_logo' => 'site_logo_path', 'site_favicon' => 'site_favicon_path'] as $field => $key) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $request->validate([$field => 'image|max:2048']);
                $name = $field.'-'.now()->timestamp.'.'.$file->getClientOriginalExtension();
                $file->move(public_path('branding'), $name);
                $settings->set($key, 'branding/'.$name, 'string', 'general');
            }
        }

        $audit->log('admin.settings.updated', 'Platform settings updated');

        return back()->with('success', 'Settings saved.');
    }
}
