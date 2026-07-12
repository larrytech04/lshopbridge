@extends('layouts.admin')
@section('page-title', 'Settings')

@section('content')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">@csrf @method('PUT')

        @if (session('success'))
            <div class="rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-500">{{ session('success') }}</div>
        @endif

        {{-- Branding & contact --}}
        <x-glass-card>
            <h3 class="font-semibold text-strong">Branding & contact</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label">Site name</label><input name="site_name" value="{{ setting('site_name', config('platform.name')) }}" class="field"></div>
                <div class="sm:col-span-2"><label class="label">Tagline</label><input name="tagline" value="{{ setting('tagline', config('platform.tagline')) }}" class="field"></div>
                <div>
                    <label class="label">Logo</label>
                    <div class="flex items-center gap-3">
                        <img src="{{ site_logo() }}" alt="logo" class="h-10 w-auto rounded-lg bg-white p-1 ring-1 ring-app">
                        <input type="file" name="site_logo" accept="image/*" class="field !py-2 text-sm">
                    </div>
                    <p class="mt-1 text-[11px] text-faint">PNG/SVG, transparent background recommended.</p>
                </div>
                <div>
                    <label class="label">Favicon</label>
                    <div class="flex items-center gap-3">
                        <img src="{{ site_favicon() }}" alt="favicon" class="h-8 w-8 rounded-lg bg-white p-1 ring-1 ring-app">
                        <input type="file" name="site_favicon" accept="image/*" class="field !py-2 text-sm">
                    </div>
                </div>
                <div><label class="label">Support email</label><input name="support_email" value="{{ setting('support_email', config('platform.support_email')) }}" class="field"></div>
                <div><label class="label">Support phone</label><input name="support_phone" value="{{ setting('support_phone') }}" class="field"></div>
            </div>
        </x-glass-card>

        {{-- Social links --}}
        <x-glass-card>
            <h3 class="font-semibold text-strong">Social & contact links</h3>
            <p class="text-xs text-muted">Shown in the floating social dock and footer. Leave blank to hide.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="label">WhatsApp</label><input name="social_whatsapp" value="{{ setting('social_whatsapp') }}" placeholder="https://wa.me/237..." class="field"></div>
                <div><label class="label">X (Twitter)</label><input name="social_x" value="{{ setting('social_x') }}" placeholder="https://x.com/..." class="field"></div>
                <div><label class="label">Instagram</label><input name="social_instagram" value="{{ setting('social_instagram') }}" placeholder="https://instagram.com/..." class="field"></div>
                <div><label class="label">Facebook</label><input name="social_facebook" value="{{ setting('social_facebook') }}" placeholder="https://facebook.com/..." class="field"></div>
                <div><label class="label">TikTok</label><input name="social_tiktok" value="{{ setting('social_tiktok') }}" placeholder="https://tiktok.com/@..." class="field"></div>
                <div><label class="label">Discord</label><input name="social_discord" value="{{ setting('social_discord') }}" placeholder="https://discord.gg/..." class="field"></div>
                <div><label class="label">Contact email link</label><input name="social_email" value="{{ setting('social_email') }}" placeholder="mailto:you@domain.com" class="field"></div>
                <div><label class="label">Contact phone link</label><input name="social_phone" value="{{ setting('social_phone') }}" placeholder="tel:+237..." class="field"></div>
                <div><label class="label">Telegram</label><input name="telegram_link" value="{{ setting('telegram_link') }}" class="field"></div>
            </div>
        </x-glass-card>

        {{-- Email / SMTP --}}
        <x-glass-card>
            <h3 class="font-semibold text-strong">Email (SMTP)</h3>
            <p class="text-xs text-muted">Outgoing mail server. Password is stored encrypted.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="label">SMTP host</label><input name="mail_host" value="{{ setting('mail_host') }}" placeholder="smtp.example.com" class="field"></div>
                <div><label class="label">Port</label><input name="mail_port" value="{{ setting('mail_port') }}" placeholder="587" class="field"></div>
                <div><label class="label">Username</label><input name="mail_username" value="{{ setting('mail_username') }}" class="field"></div>
                <div><label class="label">Password</label><input type="password" name="mail_password" value="" placeholder="{{ setting('mail_password') ? '•••••••• (leave blank to keep)' : '' }}" class="field" autocomplete="new-password"></div>
                <div><label class="label">Encryption</label>
                    <select name="mail_encryption" class="field">
                        @foreach (['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'] as $v => $lbl)
                            <option value="{{ $v }}" @selected(setting('mail_encryption', 'tls') === $v)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="label">From name</label><input name="mail_from_name" value="{{ setting('mail_from_name', setting('site_name', config('platform.name'))) }}" class="field"></div>
                <div class="sm:col-span-2"><label class="label">From address</label><input name="mail_from_address" value="{{ setting('mail_from_address') }}" placeholder="no-reply@domain.com" class="field"></div>
            </div>
        </x-glass-card>

        {{-- Integrations --}}
        <x-glass-card>
            <h3 class="font-semibold text-strong">Analytics, search & live chat</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="label">Google Analytics ID</label><input name="google_analytics_id" value="{{ setting('google_analytics_id') }}" placeholder="G-XXXXXXX" class="field"></div>
                <div><label class="label">Google site verification</label><input name="google_site_verification" value="{{ setting('google_site_verification') }}" class="field"></div>
                <div><label class="label">Bing site verification</label><input name="bing_site_verification" value="{{ setting('bing_site_verification') }}" class="field"></div>
                <div class="sm:col-span-2"><label class="label">Live chat / custom head code</label><textarea name="livechat_embed" rows="3" class="field font-mono text-xs" placeholder="Paste Tawk.to / Crisp / Intercom embed snippet">{{ setting('livechat_embed') }}</textarea><p class="mt-1 text-[11px] text-faint">Raw HTML/JS injected into every page. For Google sign-in keys use Integrations / API keys.</p></div>
            </div>
        </x-glass-card>

        {{-- Automation & controls --}}
        <x-glass-card>
            <h3 class="font-semibold text-strong">Automation & controls</h3>
            <div class="mt-4 space-y-3">
                <label class="flex items-center justify-between rounded-xl border border-app surface p-4">
                    <span><span class="font-medium text-strong">Payment automation</span><br><span class="text-xs text-muted">Auto-collect via provider APIs + webhooks.</span></span>
                    <input type="checkbox" name="payments_automation_enabled" value="1" @checked(setting('payments_automation_enabled', true)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                </label>
                <label class="flex items-center justify-between rounded-xl border border-app surface p-4">
                    <span><span class="font-medium text-strong">Funding automation</span><br><span class="text-xs text-muted">Auto-fund China wallets after payment.</span></span>
                    <input type="checkbox" name="funding_automation_enabled" value="1" @checked(setting('funding_automation_enabled', true)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                </label>
                <label class="flex items-center justify-between rounded-xl border border-app surface p-4">
                    <span><span class="font-medium text-strong">Require proof of payment</span><br><span class="text-xs text-muted">For manual deposit methods.</span></span>
                    <input type="checkbox" name="require_proof_of_payment" value="1" @checked(setting('require_proof_of_payment', true)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                </label>
                <label class="flex items-center justify-between rounded-xl border border-app surface p-4">
                    <span><span class="font-medium text-strong">Maintenance mode</span><br><span class="text-xs text-muted">Display a maintenance notice.</span></span>
                    <input type="checkbox" name="maintenance_mode" value="1" @checked(setting('maintenance_mode', false)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                </label>
            </div>
        </x-glass-card>

        <button class="btn btn-primary px-6">Save settings</button>
    </form>
</div>
@endsection
