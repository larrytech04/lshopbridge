<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSettingRevision;
use App\Services\Audit\AuditLogger;
use App\Services\Security\BotSecurityEventService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(SettingsService $settings): View
    {
        return view('admin.settings.index', [
            'settings' => $settings->all(),
            'revisions' => SystemSettingRevision::with('changedBy')->latest('created_at')->limit(30)->get(),
        ]);
    }

    public function update(Request $request, SettingsService $settings, AuditLogger $audit, BotSecurityEventService $botEvents)
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
            'social_discord_support' => 'string',
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
            'refund_window_days' => 'int',
            // Security
            'require_admin_mfa' => 'bool',
            // Forms & bot protection
            'bot_protection_enabled' => 'bool',
            'turnstile_enabled' => 'bool',
            'turnstile_appearance_mode' => 'string',
            'honeypot_enabled' => 'bool',
            'form_timing_protection_enabled' => 'bool',
            'duplicate_detection_enabled' => 'bool',
            'rate_limiting_enabled' => 'bool',
            'spam_link_detection' => 'bool',
            'suspicious_keyword_detection' => 'bool',
            'temporary_ip_restriction_enabled' => 'bool',
            'silent_bot_discard_enabled' => 'bool',
            'bot_protection_log_only_mode' => 'bool',
            'admin_alert_threshold' => 'int',
            'security_event_retention_days' => 'int',
            'contact_form_protection' => 'bool',
            'registration_protection' => 'bool',
            'agent_registration_protection' => 'bool',
            'login_protection' => 'bool',
            'password_reset_protection' => 'bool',
            'newsletter_protection' => 'bool',
            'reviews_protection' => 'bool',
            'guest_support_protection' => 'bool',
            'referral_protection' => 'bool',
            'guide_feedback_protection' => 'bool',
            // Legal & company info — used as placeholders on Legal Center
            // pages until verified with real registration documents. See
            // the "Requires verification" notice on the Legal tab.
            'company_founded_year' => 'string',
            'company_legal_name' => 'string',
            'company_trading_name' => 'string',
            'company_registration_number' => 'string',
            'company_registered_address' => 'string',
            'company_jurisdiction' => 'string',
            'legal_email' => 'string',
            'privacy_email' => 'string',
            'compliance_email' => 'string',
        ];

        // A critical protection layer being switched off deserves an alert —
        // "The bot-protection configuration changes" / "rate-limiting
        // protection is disabled" requirements.
        $watchedKeys = ['bot_protection_enabled', 'rate_limiting_enabled', 'turnstile_enabled', 'honeypot_enabled'];
        $wasEnabled = collect($watchedKeys)->mapWithKeys(fn ($key) => [$key => (bool) setting($key, true)]);

        foreach ($schema as $key => $type) {
            $value = match ($type) {
                'bool' => $request->boolean($key),
                'int' => (int) $request->input($key, 0),
                default => $request->input($key, ''),
            };
            $settings->set($key, $value, $type, 'general');
        }

        foreach ($watchedKeys as $key) {
            $nowEnabled = $request->boolean($key);
            if ($wasEnabled[$key] && ! $nowEnabled) {
                $botEvents->alertConfigurationChanged("'{$key}' was just turned OFF by ".auth()->user()->name.'.');
            }
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
