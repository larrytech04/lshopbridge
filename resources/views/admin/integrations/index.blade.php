@extends('layouts.admin')
@section('page-title', 'Integrations / API keys')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-sky-400/30 bg-sky-500/10 p-4 text-sm text-sky-200">
        <x-icon name="lock" class="mr-1 inline h-4 w-4" /> Keys entered here are stored <strong>encrypted</strong> and override your <code class="rounded surface px-1">.env</code> at runtime. Leave a field blank to keep its existing value. Switch a provider to <strong>live</strong> only after entering real keys.
    </div>

    {{-- Payment & funding providers --}}
    @foreach ($schema as $code => $fields)
        @php $p = $providers[$code] ?? null; @endphp
        <x-glass-card>
            <form method="POST" action="{{ route('admin.integrations.provider', $code) }}">
                @csrf @method('PUT')
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-strong">{{ $p->name ?? ucfirst(str_replace('_',' ',$code)) }}</h3>
                        <p class="text-xs text-faint">{{ $code }} · {{ ucfirst($p->kind ?? 'collection') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <select name="mode" class="field max-w-[140px]">
                            <option value="sandbox" @selected(($p->mode ?? 'sandbox')==='sandbox')>Sandbox</option>
                            <option value="live" @selected(($p->mode ?? '')==='live')>Live</option>
                        </select>
                        <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" @checked($p->is_active ?? true) class="rounded"> Active</label>
                    </div>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @php $has = $p ? ($p->credentials ?? []) : []; @endphp
                    @foreach ($fields as $field => $label)
                        <div>
                            <label class="label">{{ $label }}</label>
                            <input name="fields[{{ $field }}]" class="field" autocomplete="off"
                                   placeholder="{{ !empty($has[$field]) ? '•••••••• (set, leave blank to keep)' : 'Not set' }}">
                        </div>
                    @endforeach
                </div>
                <div class="mt-4"><button class="btn btn-primary">Save {{ $p->name ?? $code }}</button></div>
            </form>
        </x-glass-card>
    @endforeach

    {{-- General integrations --}}
    <x-glass-card>
        <form method="POST" action="{{ route('admin.integrations.general') }}" class="space-y-6">
            @csrf @method('PUT')

            <div>
                <h3 class="font-semibold text-strong">Google sign-in (OAuth)</h3>
                <p class="text-xs text-faint">Create credentials at console.cloud.google.com → APIs &amp; Services → Credentials. Redirect URI: <code class="surface px-1">{{ url('/auth/google/callback') }}</code></p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div><label class="label">Client ID</label><input name="google_client_id" class="field" autocomplete="off" placeholder="{{ setting('google_client_id') ? '•••• set' : 'Not set' }}"></div>
                    <div><label class="label">Client secret</label><input name="google_client_secret" class="field" autocomplete="off" placeholder="{{ setting('google_client_secret') ? '•••• set' : 'Not set' }}"></div>
                </div>
                <label class="mt-2 flex items-center gap-2 text-sm text-body"><input type="checkbox" name="google_login_enabled" value="1" @checked(setting('google_login_enabled')) class="rounded"> Enable "Continue with Google"</label>
            </div>

            <div class="border-t border-app pt-5">
                <h3 class="font-semibold text-strong">Cloudflare Turnstile (bot protection)</h3>
                <p class="text-xs text-faint">Get keys at dash.cloudflare.com → Turnstile. Adds a captcha to login &amp; registration.</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div><label class="label">Site key</label><input name="turnstile_site_key" class="field" autocomplete="off" placeholder="{{ setting('turnstile_site_key') ? '•••• set' : 'Not set' }}"></div>
                    <div><label class="label">Secret key</label><input name="turnstile_secret_key" class="field" autocomplete="off" placeholder="{{ setting('turnstile_secret_key') ? '•••• set' : 'Not set' }}"></div>
                </div>
                <label class="mt-2 flex items-center gap-2 text-sm text-body"><input type="checkbox" name="turnstile_enabled" value="1" @checked(setting('turnstile_enabled')) class="rounded"> Enable Turnstile on auth forms</label>
            </div>

            <div class="border-t border-app pt-5">
                <h3 class="font-semibold text-strong">SMS / OTP provider</h3>
                <p class="text-xs text-faint">Used to deliver phone verification codes (Twilio, Termii, Africa's Talking…).</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    <div><label class="label">Provider</label><input name="sms_provider" value="{{ setting('sms_provider') }}" class="field" placeholder="twilio"></div>
                    <div><label class="label">API key</label><input name="sms_api_key" class="field" autocomplete="off" placeholder="{{ setting('sms_api_key') ? '•••• set' : 'Not set' }}"></div>
                    <div><label class="label">Sender ID</label><input name="sms_sender" value="{{ setting('sms_sender') }}" class="field" placeholder="LSHOPBRIDGE"></div>
                </div>
            </div>

            <button class="btn btn-primary">Save integration settings</button>
        </form>
    </x-glass-card>
</div>
@endsection
