@extends('layouts.admin')
@section('page-title', 'Platform settings')

@section('content')
<div class="mx-auto max-w-4xl pb-24" x-data="settingsPage()">

    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Platform settings</h1>
            <p class="text-sm text-muted">Everything an admin can toggle without touching code.</p>
        </div>
        <button type="button" @click="historyOpen = true" class="btn btn-ghost text-sm"><x-icon name="clock" class="h-4 w-4" /> Change history</button>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-500">{{ session('success') }}</div>
    @endif

    <div class="relative mb-5">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
        <input x-model="q" type="text" placeholder="Search settings across every tab…" class="field pl-9">
    </div>

    <nav class="mb-5 flex flex-wrap gap-2" x-show="!q.trim()">
        <template x-for="t in tabs" :key="t.key">
            <button type="button" @click="tab = t.key" class="pill" :class="tab === t.key ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10'" x-text="t.label"></button>
        </template>
    </nav>
    <p class="mb-5 text-xs text-faint" x-show="q.trim()" x-cloak>Showing every tab's matches for "<span x-text="q"></span>".</p>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">@csrf @method('PUT')

        {{-- Branding & Identity --}}
        <div data-settings-tab="branding" x-show="sectionVisible('branding')" x-cloak>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Branding & identity</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2" data-settings-label="Site name" x-show="fieldVisible('Site name')"><label class="label">Site name</label><input name="site_name" value="{{ setting('site_name', config('platform.name')) }}" class="field"></div>
                <div class="sm:col-span-2" data-settings-label="Tagline" x-show="fieldVisible('Tagline')"><label class="label">Tagline</label><input name="tagline" value="{{ setting('tagline', config('platform.tagline')) }}" class="field"></div>
                <div data-settings-label="Logo" x-show="fieldVisible('Logo')">
                    <label class="label">Logo</label>
                    <div class="flex items-center gap-3">
                        <img src="{{ site_logo() }}" alt="logo" class="h-10 w-auto rounded-lg bg-white p-1 ring-1 ring-app">
                        <input type="file" name="site_logo" accept="image/*" class="field !py-2 text-sm">
                    </div>
                    <p class="mt-1 text-[11px] text-faint">PNG/SVG, transparent background recommended.</p>
                </div>
                <div data-settings-label="Favicon" x-show="fieldVisible('Favicon')">
                    <label class="label">Favicon</label>
                    <div class="flex items-center gap-3">
                        <img src="{{ site_favicon() }}" alt="favicon" class="h-8 w-8 rounded-lg bg-white p-1 ring-1 ring-app">
                        <input type="file" name="site_favicon" accept="image/*" class="field !py-2 text-sm">
                    </div>
                </div>
                <div data-settings-label="Support email" x-show="fieldVisible('Support email')"><label class="label">Support email</label><input name="support_email" value="{{ setting('support_email', config('platform.support_email')) }}" class="field"></div>
                <div data-settings-label="Support phone" x-show="fieldVisible('Support phone')"><label class="label">Support phone</label><input name="support_phone" value="{{ setting('support_phone') }}" class="field"></div>
                <div data-settings-label="Founded year" x-show="fieldVisible('Founded year')"><label class="label">Founded year</label><input name="company_founded_year" value="{{ setting('company_founded_year') }}" placeholder="e.g. 2025" class="field"></div>
            </div>
        </x-glass-card>
        </div>

        {{-- Contact & Social --}}
        <div data-settings-tab="social" x-show="sectionVisible('social')" x-cloak>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Contact & social links</h3>
            <p class="text-xs text-muted">Shown in the floating social dock and footer. Leave blank to hide.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div data-settings-label="WhatsApp" x-show="fieldVisible('WhatsApp')"><label class="label">WhatsApp</label><input name="social_whatsapp" value="{{ setting('social_whatsapp') }}" placeholder="https://wa.me/237..." class="field"></div>
                <div data-settings-label="X (Twitter)" x-show="fieldVisible('X (Twitter)')"><label class="label">X (Twitter)</label><input name="social_x" value="{{ setting('social_x') }}" placeholder="https://x.com/..." class="field"></div>
                <div data-settings-label="Instagram" x-show="fieldVisible('Instagram')"><label class="label">Instagram</label><input name="social_instagram" value="{{ setting('social_instagram') }}" placeholder="https://instagram.com/..." class="field"></div>
                <div data-settings-label="Facebook" x-show="fieldVisible('Facebook')"><label class="label">Facebook</label><input name="social_facebook" value="{{ setting('social_facebook') }}" placeholder="https://facebook.com/..." class="field"></div>
                <div data-settings-label="TikTok" x-show="fieldVisible('TikTok')"><label class="label">TikTok</label><input name="social_tiktok" value="{{ setting('social_tiktok') }}" placeholder="https://tiktok.com/@..." class="field"></div>
                <div data-settings-label="Discord" x-show="fieldVisible('Discord')"><label class="label">Discord</label><input name="social_discord" value="{{ setting('social_discord') }}" placeholder="https://discord.gg/..." class="field"></div>
                <div data-settings-label="Discord (support pages)" x-show="fieldVisible('Discord (support pages)')">
                    <label class="label">Discord (support pages)</label>
                    <input name="social_discord_support" value="{{ setting('social_discord_support') }}" placeholder="https://discord.gg/... (optional, e.g. an operations/support channel invite)" class="field">
                    <p class="mt-1 text-[11px] text-faint">Used by the "Join Discord" card on the contact and support pages. Leave blank to reuse the Discord link above.</p>
                </div>
                <div data-settings-label="Contact email link" x-show="fieldVisible('Contact email link')"><label class="label">Contact email link</label><input name="social_email" value="{{ setting('social_email') }}" placeholder="mailto:you@domain.com" class="field"></div>
                <div data-settings-label="Contact phone link" x-show="fieldVisible('Contact phone link')"><label class="label">Contact phone link</label><input name="social_phone" value="{{ setting('social_phone') }}" placeholder="tel:+237..." class="field"></div>
                <div data-settings-label="Telegram" x-show="fieldVisible('Telegram')"><label class="label">Telegram</label><input name="telegram_link" value="{{ setting('telegram_link') }}" class="field"></div>
            </div>
        </x-glass-card>
        </div>

        {{-- Email Configuration --}}
        <div data-settings-tab="email" x-show="sectionVisible('email')" x-cloak>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Email configuration</h3>
            <p class="text-xs text-muted">Outgoing mail server. Password is stored encrypted.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div data-settings-label="SMTP host" x-show="fieldVisible('SMTP host')"><label class="label">SMTP host</label><input name="mail_host" value="{{ setting('mail_host') }}" placeholder="smtp.example.com" class="field"></div>
                <div data-settings-label="Port" x-show="fieldVisible('Port')"><label class="label">Port</label><input name="mail_port" value="{{ setting('mail_port') }}" placeholder="587" class="field"></div>
                <div data-settings-label="Username" x-show="fieldVisible('Username')"><label class="label">Username</label><input name="mail_username" value="{{ setting('mail_username') }}" class="field"></div>
                <div data-settings-label="Password" x-show="fieldVisible('Password')"><label class="label">Password</label><input type="password" name="mail_password" value="" placeholder="{{ setting('mail_password') ? '•••••••• (leave blank to keep)' : '' }}" class="field" autocomplete="new-password"></div>
                <div data-settings-label="Encryption" x-show="fieldVisible('Encryption')"><label class="label">Encryption</label>
                    <select name="mail_encryption" class="field">
                        @foreach (['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'] as $v => $lbl)
                            <option value="{{ $v }}" @selected(setting('mail_encryption', 'tls') === $v)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-settings-label="From name" x-show="fieldVisible('From name')"><label class="label">From name</label><input name="mail_from_name" value="{{ setting('mail_from_name', setting('site_name', config('platform.name'))) }}" class="field"></div>
                <div class="sm:col-span-2" data-settings-label="From address" x-show="fieldVisible('From address')"><label class="label">From address</label><input name="mail_from_address" value="{{ setting('mail_from_address') }}" placeholder="no-reply@domain.com" class="field"></div>
            </div>
        </x-glass-card>
        </div>

        {{-- Integrations & Analytics --}}
        <div data-settings-tab="integrations" x-show="sectionVisible('integrations')" x-cloak>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Integrations & analytics</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div data-settings-label="Google Analytics ID" x-show="fieldVisible('Google Analytics ID')"><label class="label">Google Analytics ID</label><input name="google_analytics_id" value="{{ setting('google_analytics_id') }}" placeholder="G-XXXXXXX" class="field"></div>
                <div data-settings-label="Google site verification" x-show="fieldVisible('Google site verification')"><label class="label">Google site verification</label><input name="google_site_verification" value="{{ setting('google_site_verification') }}" class="field"></div>
                <div data-settings-label="Bing site verification" x-show="fieldVisible('Bing site verification')"><label class="label">Bing site verification</label><input name="bing_site_verification" value="{{ setting('bing_site_verification') }}" class="field"></div>
                <div class="sm:col-span-2" data-settings-label="Live chat embed" x-show="fieldVisible('Live chat embed')"><label class="label">Live chat / custom head code</label><textarea name="livechat_embed" rows="3" class="field font-mono text-xs" placeholder="Paste Tawk.to / Crisp / Intercom embed snippet">{{ setting('livechat_embed') }}</textarea><p class="mt-1 text-[11px] text-faint">Raw HTML/JS injected into every page. For Google sign-in keys use the Integrations Hub / API keys.</p></div>
            </div>
        </x-glass-card>
        </div>

        {{-- Automation & Behaviour --}}
        <div data-settings-tab="automation" x-show="sectionVisible('automation')" x-cloak>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Automation & behaviour</h3>
            <div class="mt-4 space-y-3">
                <label class="flex items-center justify-between rounded-xl border border-app surface p-4" data-settings-label="Payment automation" x-show="fieldVisible('Payment automation')">
                    <span><span class="font-medium text-strong">Payment automation</span><br><span class="text-xs text-muted">Auto-collect via provider APIs + webhooks.</span></span>
                    <input type="checkbox" name="payments_automation_enabled" value="1" @checked(setting('payments_automation_enabled', true)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                </label>
                <label class="flex items-center justify-between rounded-xl border border-app surface p-4" data-settings-label="Funding automation" x-show="fieldVisible('Funding automation')">
                    <span><span class="font-medium text-strong">Funding automation</span><br><span class="text-xs text-muted">Auto-fund China wallets after payment.</span></span>
                    <input type="checkbox" name="funding_automation_enabled" value="1" @checked(setting('funding_automation_enabled', true)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                </label>
                <label class="flex items-center justify-between rounded-xl border border-app surface p-4" data-settings-label="Require proof of payment" x-show="fieldVisible('Require proof of payment')">
                    <span><span class="font-medium text-strong">Require proof of payment</span><br><span class="text-xs text-muted">For manual deposit methods.</span></span>
                    <input type="checkbox" name="require_proof_of_payment" value="1" @checked(setting('require_proof_of_payment', true)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                </label>
                <label class="flex items-center justify-between rounded-xl border border-app surface p-4" data-settings-label="Maintenance mode" x-show="fieldVisible('Maintenance mode')">
                    <span><span class="font-medium text-strong">Maintenance mode</span><br><span class="text-xs text-muted">Display a maintenance notice.</span></span>
                    <input type="checkbox" name="maintenance_mode" value="1" @checked(setting('maintenance_mode', false)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                </label>
                <label class="flex items-center justify-between rounded-xl border border-app surface p-4" data-settings-label="Refund eligibility window" x-show="fieldVisible('Refund eligibility window')">
                    <span><span class="font-medium text-strong">Refund eligibility window</span><br><span class="text-xs text-muted">Days after payment a customer can request a refund.</span></span>
                    <input type="number" name="refund_window_days" min="0" max="365" value="{{ setting('refund_window_days', 14) }}" class="field w-24 text-center">
                </label>
            </div>
        </x-glass-card>
        </div>

        {{-- Security & Administration --}}
        <div data-settings-tab="security" x-show="sectionVisible('security')" x-cloak>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Security & administration</h3>
            <div class="mt-4 space-y-3">
                <label class="flex items-center justify-between rounded-xl border border-app surface p-4" data-settings-label="Require two-factor authentication for admins" x-show="fieldVisible('Require two-factor authentication for admins')">
                    <span><span class="font-medium text-strong">Require two-factor authentication for admins</span><br><span class="text-xs text-muted">Blocks admin/staff sign-in until they enroll an authenticator app. Enable this only after at least one admin has 2FA set up, or you may lock every admin out.</span></span>
                    <input type="checkbox" name="require_admin_mfa" value="1" @checked(setting('require_admin_mfa', false)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                </label>
            </div>
        </x-glass-card>
        </div>

        {{-- Forms & Bot Protection --}}
        <div data-settings-tab="forms_bot_protection" x-show="sectionVisible('forms_bot_protection')" x-cloak>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Forms & bot protection</h3>
            <p class="text-xs text-muted">Layered spam and bot defenses across every public form. Detection rules themselves are never shown to visitors.</p>
            <div class="mt-4 space-y-3">
                @foreach ([
                    'bot_protection_enabled' => ['Bot protection enabled', 'Master switch — turning this off disables every layer below.', true],
                    'honeypot_enabled' => ['Honeypot enabled', 'Invisible trap field with a rotating name.', true],
                    'form_timing_protection_enabled' => ['Form timing protection', 'Signed, expiring render-to-submit timing token.', true],
                    'duplicate_detection_enabled' => ['Duplicate detection', 'Fingerprints repeated or replayed payloads.', true],
                    'rate_limiting_enabled' => ['Rate limiting', 'Per-form limits keyed by IP, session, and email.', true],
                    'spam_link_detection' => ['Spam link detection', 'Flags excessive links and known spam-link domains.', true],
                    'suspicious_keyword_detection' => ['Suspicious keyword detection', 'Flags known spam phrases in message content.', true],
                    'temporary_ip_restriction_enabled' => ['Temporary IP restriction', 'Honors active temporary restrictions from confirmed abuse.', true],
                    'silent_bot_discard_enabled' => ['Silent bot discard', 'When off, high-confidence bots are held for review instead of discarded — nothing is ever thrown away unseen.', true],
                    'bot_protection_log_only_mode' => ['Log-only mode', 'Records what each layer would have done without actually blocking anything — for safely testing new rules.', false],
                ] as $key => [$label, $help, $default])
                    <label class="flex items-center justify-between rounded-xl border border-app surface p-4" data-settings-label="{{ $label }}" x-show="fieldVisible('{{ $label }}')">
                        <span><span class="font-medium text-strong">{{ $label }}</span><br><span class="text-xs text-muted">{{ $help }}</span></span>
                        <input type="checkbox" name="{{ $key }}" value="1" @checked(setting($key, $default)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                    </label>
                @endforeach
            </div>

            <div class="mt-5 grid gap-4 border-t border-app pt-4 sm:grid-cols-2">
                <div data-settings-label="Turnstile appearance mode" x-show="fieldVisible('Turnstile appearance mode')">
                    <label class="label">Turnstile appearance mode</label>
                    <select name="turnstile_appearance_mode" class="field">
                        @foreach (['managed' => 'Managed (always visible)', 'invisible' => 'Invisible (only when needed)', 'conditional' => 'Conditional (only after suspicious behaviour)'] as $v => $lbl)
                            <option value="{{ $v }}" @selected(setting('turnstile_appearance_mode', 'managed') === $v)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-settings-label="Administrator alert threshold" x-show="fieldVisible('Administrator alert threshold')">
                    <label class="label">Administrator alert threshold</label>
                    <input type="number" min="1" max="1000" name="admin_alert_threshold" value="{{ setting('admin_alert_threshold', 5) }}" class="field">
                    <p class="mt-1 text-[11px] text-faint">Matching events within 15 minutes before an ops alert fires.</p>
                </div>
                <div data-settings-label="Security event retention period" x-show="fieldVisible('Security event retention period')">
                    <label class="label">Security event retention (days)</label>
                    <input type="number" min="7" max="730" name="security_event_retention_days" value="{{ setting('security_event_retention_days', 90) }}" class="field">
                </div>
            </div>

            <div class="mt-5 border-t border-app pt-4">
                <p class="text-sm font-semibold text-strong">Cloudflare Turnstile keys</p>
                <p class="mt-1 text-xs text-faint">Keys live in <code class="surface px-1">.env</code>, never in the database — the secret is never shown once set. Turn it on or off from <a href="{{ route('admin.integrations.index') }}" class="text-brand-400 hover:underline">Integrations</a>.</p>
                <dl class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
                    <div><dt class="text-xs text-faint">Site key (public)</dt><dd class="font-mono text-xs text-body">{{ config('services.turnstile.site_key') ?: 'Not set' }}</dd></div>
                    <div>
                        <dt class="text-xs text-faint">Secret key</dt>
                        <dd class="font-semibold {{ config('services.turnstile.secret_key') ? 'text-emerald-600' : 'text-rose-600' }}">{{ config('services.turnstile.secret_key') ? 'Configured' : 'Not configured' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-5 border-t border-app pt-4">
                <p class="text-sm font-semibold text-strong">Protected forms</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        'contact_form_protection' => 'Contact form',
                        'registration_protection' => 'Registration',
                        'agent_registration_protection' => 'Agent registration',
                        'login_protection' => 'Login',
                        'password_reset_protection' => 'Password reset',
                        'newsletter_protection' => 'Newsletter signup',
                        'reviews_protection' => 'Guest review feedback',
                        'guest_support_protection' => 'Guest support form',
                        'referral_protection' => 'Referral / agent interest form',
                        'guide_feedback_protection' => 'Guide feedback',
                    ] as $key => $label)
                        <label class="flex items-center justify-between rounded-xl border border-app surface p-3" data-settings-label="{{ $label }}" x-show="fieldVisible('{{ $label }}')">
                            <span class="text-sm text-body">{{ $label }}</span>
                            <input type="checkbox" name="{{ $key }}" value="1" @checked(setting($key, true)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
                        </label>
                    @endforeach
                </div>
            </div>
        </x-glass-card>
        </div>

        {{-- Legal & Company --}}
        <div data-settings-tab="legal" x-show="sectionVisible('legal')" x-cloak>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Legal & company information</h3>
            <p class="text-xs text-muted">Feeds the configurable placeholders shown across the Legal & Policy Center (company name, registered address, contact addresses). Every field below is unverified until confirmed with real registration documents — do not rely on it in a published policy without legal review.</p>
            <div class="mt-3 rounded-xl border border-amber-400/30 bg-amber-500/10 px-3 py-2 text-xs font-medium text-amber-600">Requires legal review before production use. Leaving a field blank shows a clearly-labelled placeholder on public policy pages, never a fabricated value.</div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2" data-settings-label="Legal company name" x-show="fieldVisible('Legal company name')"><label class="label">Legal company name</label><input name="company_legal_name" value="{{ setting('company_legal_name') }}" placeholder="Not yet configured" class="field"></div>
                <div data-settings-label="Trading name" x-show="fieldVisible('Trading name')"><label class="label">Trading name</label><input name="company_trading_name" value="{{ setting('company_trading_name') }}" placeholder="{{ setting('site_name', config('platform.name')) }}" class="field"></div>
                <div data-settings-label="Registration number" x-show="fieldVisible('Registration number')"><label class="label">Registration number</label><input name="company_registration_number" value="{{ setting('company_registration_number') }}" placeholder="Not yet configured" class="field"></div>
                <div class="sm:col-span-2" data-settings-label="Registered address" x-show="fieldVisible('Registered address')"><label class="label">Registered address</label><input name="company_registered_address" value="{{ setting('company_registered_address') }}" placeholder="Not yet configured" class="field"></div>
                <div data-settings-label="Governing jurisdiction" x-show="fieldVisible('Governing jurisdiction')"><label class="label">Governing jurisdiction</label><input name="company_jurisdiction" value="{{ setting('company_jurisdiction') }}" placeholder="Not yet configured" class="field"></div>
                <div data-settings-label="Legal notices email" x-show="fieldVisible('Legal notices email')"><label class="label">Legal notices email</label><input name="legal_email" value="{{ setting('legal_email') }}" placeholder="{{ setting('support_email', config('platform.support_email')) }}" class="field"></div>
                <div data-settings-label="Privacy requests email" x-show="fieldVisible('Privacy requests email')"><label class="label">Privacy requests email</label><input name="privacy_email" value="{{ setting('privacy_email') }}" placeholder="{{ setting('support_email', config('platform.support_email')) }}" class="field"></div>
                <div data-settings-label="Compliance email" x-show="fieldVisible('Compliance email')"><label class="label">Compliance email</label><input name="compliance_email" value="{{ setting('compliance_email') }}" placeholder="{{ setting('support_email', config('platform.support_email')) }}" class="field"></div>
            </div>
        </x-glass-card>
        </div>

        <div class="sticky bottom-4 z-10 flex justify-end">
            <button class="btn btn-primary px-6 shadow-lg">Save settings</button>
        </div>
    </form>

    {{-- Change history drawer --}}
    <div x-show="historyOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-end bg-black/50 p-4" @click.self="historyOpen = false">
        <div class="glass h-full w-full max-w-lg overflow-y-auto rounded-2xl p-5" @click.stop>
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-semibold text-strong">Change history</h3>
                <button type="button" @click="historyOpen = false" class="rounded-lg p-1.5 text-muted hover:surface-2"><x-icon name="x" class="h-5 w-5" /></button>
            </div>
            <div class="space-y-3">
                @forelse ($revisions as $rev)
                    <div class="rounded-xl border border-app p-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold text-strong">{{ $rev->key }}</span>
                            <span class="text-xs text-faint">{{ $rev->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-muted">{{ $rev->changedBy->name ?? 'System' }}</p>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                            <div class="truncate rounded-lg bg-rose-500/10 px-2 py-1 text-rose-600">{{ \Illuminate\Support\Str::limit($rev->old_value ?? '(empty)', 40) }}</div>
                            <div class="truncate rounded-lg bg-emerald-500/10 px-2 py-1 text-emerald-600">{{ \Illuminate\Support\Str::limit($rev->new_value ?? '(empty)', 40) }}</div>
                        </div>
                    </div>
                @empty
                    <x-empty icon="clock" title="No changes recorded yet" message="Every settings change is recorded here from now on." />
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function settingsPage() {
    return {
        tab: 'branding',
        q: '',
        historyOpen: false,
        tabs: [
            { key: 'branding', label: 'Branding & Identity' },
            { key: 'social', label: 'Contact & Social' },
            { key: 'email', label: 'Email Configuration' },
            { key: 'integrations', label: 'Integrations & Analytics' },
            { key: 'automation', label: 'Automation & Behaviour' },
            { key: 'security', label: 'Security & Administration' },
            { key: 'forms_bot_protection', label: 'Forms & Bot Protection' },
            { key: 'legal', label: 'Legal & Company' },
        ],
        fieldVisible(label) {
            const q = this.q.trim().toLowerCase();
            if (!q) return true;
            return label.toLowerCase().includes(q);
        },
        sectionVisible(key) {
            if (!this.q.trim()) return this.tab === key;
            const section = document.querySelector(`[data-settings-tab="${key}"]`);
            if (!section) return false;

            return [...section.querySelectorAll('[data-settings-label]')]
                .some((el) => el.dataset.settingsLabel.toLowerCase().includes(this.q.trim().toLowerCase()));
        },
    };
}
</script>
@endpush
