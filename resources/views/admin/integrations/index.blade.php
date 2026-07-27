@extends('layouts.admin')
@section('page-title', 'Integrations hub')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Integrations hub</h1>
            <p class="text-sm text-muted">Google sign-in, bot protection, SMS, and security-alert integrations. Payment provider credentials live on <a href="{{ route('admin.providers.index') }}" class="text-brand-400 hover:underline">Payment Providers</a>.</p>
        </div>
    </div>

    <x-glass-card>
        <form method="POST" action="{{ route('admin.integrations.general') }}" class="space-y-6">
            @csrf @method('PUT')

            <div>
                <h3 class="font-semibold text-strong">Google sign-in (OAuth)</h3>
                <p class="text-xs text-faint">
                    Credentials are set on the server via <code class="surface px-1">GOOGLE_CLIENT_ID</code> / <code class="surface px-1">GOOGLE_CLIENT_SECRET</code> in <code class="surface px-1">.env</code>, not here. Keys are never stored in the database.
                    Status:
                    <span class="font-semibold {{ config('services.google.client_id') ? 'text-emerald-600' : 'text-rose-600' }}">{{ config('services.google.client_id') ? 'Configured' : 'Not configured' }}</span>.
                    Redirect URI: <code class="surface px-1">{{ url('/auth/google/callback') }}</code>
                </p>
                <label class="mt-2 flex items-center gap-2 text-sm text-body"><input type="checkbox" name="google_login_enabled" value="1" @checked(setting('google_login_enabled')) class="rounded"> Enable "Continue with Google"</label>
            </div>

            <div class="border-t border-app pt-5">
                <h3 class="font-semibold text-strong">Cloudflare Turnstile (bot protection)</h3>
                <p class="text-xs text-faint">
                    Credentials are set on the server via <code class="surface px-1">TURNSTILE_SITE_KEY</code> / <code class="surface px-1">TURNSTILE_SECRET_KEY</code> in <code class="surface px-1">.env</code>, not here.
                    Status:
                    <span class="font-semibold {{ config('services.turnstile.site_key') ? 'text-emerald-600' : 'text-rose-600' }}">{{ config('services.turnstile.site_key') ? 'Configured' : 'Not configured' }}</span>.
                </p>
                <label class="mt-2 flex items-center gap-2 text-sm text-body"><input type="checkbox" name="turnstile_enabled" value="1" @checked(setting('turnstile_enabled')) class="rounded"> Enable Turnstile on auth forms</label>
            </div>

            <div class="border-t border-app pt-5">
                <h3 class="font-semibold text-strong">SMS / OTP provider</h3>
                <p class="text-xs text-faint">
                    Used to deliver phone verification codes. Set via <code class="surface px-1">SMS_PROVIDER</code> / <code class="surface px-1">SMS_API_KEY</code> / <code class="surface px-1">SMS_SENDER</code> in <code class="surface px-1">.env</code>, not here.
                    Status:
                    <span class="font-semibold {{ config('services.sms.api_key') ? 'text-emerald-600' : 'text-rose-600' }}">{{ config('services.sms.api_key') ? 'Configured' : 'Not configured' }}</span>.
                </p>
            </div>

            <button class="btn btn-primary">Save integration settings</button>
        </form>
    </x-glass-card>

    <x-glass-card>
        <h3 class="font-semibold text-strong">Security alerting & geo-IP</h3>
        <p class="text-xs text-faint">Read-only status. Configure via the listed .env variables; there is no admin UI for credential values.</p>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-body">Discord critical alerts</dt>
                <dd class="text-xs text-faint">
                    <code class="surface px-1">DISCORD_WEBHOOK_URL</code> ·
                    <span class="font-semibold {{ config('services.discord.webhook_url') ? 'text-emerald-600' : 'text-rose-600' }}">{{ config('services.discord.webhook_url') ? 'Configured' : 'Not configured' }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-body">Slack critical alerts</dt>
                <dd class="text-xs text-faint">
                    <code class="surface px-1">SLACK_WEBHOOK_URL</code> ·
                    <span class="font-semibold {{ config('services.slack_alerts.webhook_url') ? 'text-emerald-600' : 'text-rose-600' }}">{{ config('services.slack_alerts.webhook_url') ? 'Configured' : 'Not configured' }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-body">SMS critical alerts</dt>
                <dd class="text-xs text-faint">
                    <code class="surface px-1">SMS_ACCOUNT_SID</code> ·
                    <span class="font-semibold {{ config('services.sms.account_sid') ? 'text-emerald-600' : 'text-rose-600' }}">{{ config('services.sms.account_sid') ? 'Configured' : 'Not configured' }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-body">Geo-IP / VPN detection</dt>
                <dd class="text-xs text-faint">
                    <code class="surface px-1">IPINFO_API_KEY</code> ·
                    <span class="font-semibold {{ config('services.ipinfo.api_key') ? 'text-emerald-600' : 'text-rose-600' }}">{{ config('services.ipinfo.api_key') ? 'Configured' : 'Not configured' }}</span>
                    @unless (config('services.ipinfo.api_key'))
                        <span class="block">Without this, new-country detection still works from login history, but VPN/proxy flags are reported as unknown, not clean.</span>
                    @endunless
                </dd>
            </div>
        </dl>
    </x-glass-card>
</div>
@endsection
