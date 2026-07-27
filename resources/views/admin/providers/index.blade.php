@extends('layouts.admin')
@section('page-title', 'Payment Providers')

@php
    $summaryCards = [
        ['Total', $summary['total'], 'list', 'slate'],
        ['Active', $summary['active'], 'check-circle', 'emerald'],
        ['Connected', $summary['connected'], 'webhook', 'emerald'],
        ['Not configured', $summary['not_configured'], 'alert', 'amber'],
        ['Failing', $summary['failing'], 'ban', 'rose'],
    ];

    $providersJson = [];
    foreach ($providers as $p) {
        $providersJson[] = [
            'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'description' => $p->description,
            'mode' => $p->mode, 'is_active' => $p->is_active, 'priority' => $p->priority,
            'countries' => $p->countries ?? [], 'currencies' => $p->currencies ?? [],
            'credentialFields' => array_keys($schema[$p->code] ?? []),
        ];
    }
@endphp

@section('content')
<div x-data="providersPage()" x-init="init()" class="space-y-5">

    <div>
        <h1 class="text-2xl font-bold text-strong">Payment Providers</h1>
        <p class="text-sm text-muted">Manage collection and funding providers, their credentials, and connection health. Credentials are encrypted at rest and never sent to the browser. See <a href="{{ route('admin.webhooks.index') }}" class="text-brand-400 hover:underline">Webhook logs</a> for delivery history.</p>
    </div>

    <div class="rounded-2xl border border-sky-400/30 bg-sky-500/10 p-4 text-sm text-sky-700">
        <x-icon name="lock" class="mr-1 inline h-4 w-4" /> Credential changes and connection tests require confirming your password. Leave a credential field blank to keep its existing encrypted value.
    </div>

    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-5">
        @foreach ($summaryCards as [$label, $value, $icon, $tint])
            <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-{{ $tint }}-500/15 text-{{ $tint }}-600"><x-icon :name="$icon" class="h-4 w-4" /></span>
                    <p class="truncate text-[11px] text-faint">{{ $label }}</p>
                </div>
                <p class="mt-2 text-lg font-bold text-strong">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($providers as $p)
            @php $status = $p->connectionStatus(); @endphp
            <x-glass-card solid class="{{ $p->trashed() ? 'opacity-60' : '' }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="font-semibold text-strong">{{ $p->name }}</h3>
                        <p class="text-xs text-faint">{{ $p->code }} · {{ ucfirst($p->kind) }} · priority {{ $p->priority }}</p>
                    </div>
                    <span class="pill {{ $status->color() }} text-[10px]">{{ $status->label() }}</span>
                </div>
                @if($p->description)<p class="mt-2 text-xs text-muted">{{ $p->description }}</p>@endif
                <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-faint">
                    <span class="pill {{ $p->mode === 'live' ? 'bg-rose-500/15 text-rose-600' : 'bg-amber-500/15 text-amber-600' }}">{{ ucfirst($p->mode) }}</span>
                    @if($p->last_tested_at)<span>Last tested {{ $p->last_tested_at->diffForHumans() }}</span>@endif
                </div>
                @if($p->last_test_message)
                    <p class="mt-2 text-xs {{ $p->last_test_ok ? 'text-emerald-600' : 'text-rose-500' }}">{{ $p->last_test_message }}</p>
                @endif

                @unless($p->trashed())
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-app pt-3">
                        <button type="button" class="qa-btn" @click="openEdit({{ $p->id }})"><x-icon name="cog" class="h-3.5 w-3.5" /> Configure</button>
                        <form method="POST" action="{{ route('admin.providers.test-connection', $p) }}">@csrf<button class="qa-btn"><x-icon name="webhook" class="h-3.5 w-3.5" /> Test connection</button></form>
                        <form method="POST" action="{{ route('admin.providers.active', $p) }}">@csrf<input type="hidden" name="is_active" value="{{ $p->is_active ? 0 : 1 }}"><button class="qa-btn">{{ $p->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                        <form method="POST" action="{{ route('admin.providers.destroy', $p) }}" onsubmit="return confirm('Archive this provider? Historical transactions keep their own snapshot and are unaffected.')">@csrf @method('DELETE')<button class="qa-btn qa-btn-danger"><x-icon name="trash" class="h-3.5 w-3.5" /> Archive</button></form>
                    </div>
                @else
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-app pt-3">
                        <form method="POST" action="{{ route('admin.providers.restore', $p) }}">@csrf<button class="qa-btn"><x-icon name="refresh" class="h-3.5 w-3.5" /> Restore</button></form>
                    </div>
                @endunless
            </x-glass-card>
        @endforeach
    </div>

    {{-- ============ CONFIGURE DRAWER ============ --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-40 flex justify-end" style="background: rgba(0,0,0,.4);" @keydown.escape.window="drawerOpen=false">
        <div class="w-full max-w-lg overflow-y-auto card-solid h-full border-l border-app p-6 shadow-2xl" @click.outside="drawerOpen=false" x-show="drawerOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-bold text-strong">Configure provider</h2>
                <button type="button" @click="drawerOpen=false" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="x" class="h-5 w-5" /></button>
            </div>
            <form method="POST" :action="action" class="space-y-4">
                @csrf @method('PUT')

                <div>
                    <label class="label">Name</label>
                    <input name="name" x-model="form.name" required class="field">
                </div>
                <div>
                    <label class="label">Description</label>
                    <textarea name="description" x-model="form.description" rows="2" class="field"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Mode</label>
                        <select name="mode" x-model="form.mode" class="field">
                            <option value="sandbox">Sandbox</option>
                            <option value="live">Live</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Priority</label>
                        <input type="number" min="0" name="priority" x-model="form.priority" class="field">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded"> Active</label>

                <div>
                    <label class="label">Available countries (blank = all)</label>
                    <select name="countries[]" multiple x-model="form.countries" class="field h-24">
                        @foreach (\App\Models\Country::orderBy('name')->get() as $c)<option value="{{ $c->iso2 }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Available currencies (blank = all)</label>
                    <select name="currencies[]" multiple x-model="form.currencies" class="field h-24">
                        @foreach (\App\Models\Currency::orderBy('code')->get() as $cu)<option value="{{ $cu->code }}">{{ $cu->code }} — {{ $cu->name }}</option>@endforeach
                    </select>
                </div>

                <div class="border-t border-app pt-4">
                    <p class="label mb-2">Credentials</p>
                    <p class="mb-3 text-xs text-faint">Leave a field blank to keep its current encrypted value. These are never shown in plain text once saved.</p>
                    <div class="space-y-3">
                        <template x-for="field in form.credentialFields" :key="field">
                            <div>
                                <label class="label" x-text="fieldLabels[field] ?? field"></label>
                                <input :name="`credentials[${field}]`" type="password" autocomplete="new-password" class="field" placeholder="•••••••• (set, leave blank to keep)">
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-app pt-4">
                    <button type="button" class="qa-btn" @click="drawerOpen=false">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function providersPage() {
    return {
        drawerOpen: false,
        editingId: null,
        form: { name: '', description: '', mode: 'sandbox', priority: 0, is_active: true, countries: [], currencies: [], credentialFields: [] },
        baseUrl: '{{ url('/admin/providers') }}',
        providers: @json($providersJson),
        fieldLabels: {
            base_url: 'Base URL', subscription_key: 'Subscription key', api_user: 'API user', api_key: 'API key',
            webhook_secret: 'Webhook secret', client_id: 'Client ID', client_secret: 'Client secret',
            public_key: 'Public key', secret_key: 'Secret key', encryption_key: 'Encryption key',
            partner_id: 'Partner ID', api_secret: 'API secret',
        },
        init() {
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; });
        },
        get action() { return `${this.baseUrl}/${this.editingId}`; },
        openEdit(id) {
            const p = this.providers.find((x) => x.id === id);
            if (!p) return;
            this.editingId = id;
            this.form = { name: p.name, description: p.description, mode: p.mode, priority: p.priority, is_active: p.is_active, countries: p.countries, currencies: p.currencies, credentialFields: p.credentialFields };
            this.drawerOpen = true;
        },
    };
}
</script>
@endpush
