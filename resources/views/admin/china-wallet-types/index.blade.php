@extends('layouts.admin')
@section('page-title', 'China Wallet Types')

@php
    $summaryCards = [
        ['Total', $summary['total'], 'wallet', 'slate'],
        ['Active', $summary['active'], 'check-circle', 'emerald'],
        ['Automated', $summary['automated'], 'refresh', 'sky'],
        ['Manual only', $summary['manual_only'], 'clock', 'amber'],
    ];

    $walletsJson = [];
    foreach ($wallets as $w) {
        $walletsJson[] = [
            'id' => $w->id, 'code' => $w->code, 'name' => $w->name, 'description' => $w->description,
            'account_identifier_type' => $w->account_identifier_type->value,
            'qr_required' => $w->qr_required, 'account_name_required' => $w->account_name_required,
            'phone_required' => $w->phone_required, 'country_restrictions' => $w->country_restrictions ?? [],
            'min_kyc_level' => $w->min_kyc_level, 'min_funding_amount' => $w->min_funding_amount ? (float) $w->min_funding_amount : null,
            'max_funding_amount' => $w->max_funding_amount ? (float) $w->max_funding_amount : null,
            'daily_limit' => $w->daily_limit ? (float) $w->daily_limit : null, 'monthly_limit' => $w->monthly_limit ? (float) $w->monthly_limit : null,
            'automated_funding' => $w->automated_funding, 'manual_funding' => $w->manual_funding,
            'provider_code' => $w->provider_code, 'processing_time_estimate' => $w->processing_time_estimate,
            'customer_instructions' => $w->customer_instructions, 'is_active' => $w->is_active, 'admin_notes' => $w->admin_notes,
        ];
    }
@endphp

@section('content')
<div x-data="chinaWalletTypesPage()" x-init="init()" class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">China Wallet Types</h1>
            <p class="text-sm text-muted">Configure limits, identifier requirements, and instructions for the wallet types this platform delivers to (Alipay, WeChat Pay, other).</p>
        </div>
        <button type="button" class="qa-btn qa-btn-good" @click="openCreate()"><x-icon name="plus" class="h-3.5 w-3.5" /> Add wallet type</button>
    </div>

    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-4">
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
        @forelse ($wallets as $w)
            <x-glass-card solid>
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="font-semibold text-strong">{{ $w->name }}</h3>
                        <p class="text-xs text-faint">{{ $w->code }} · {{ $w->account_identifier_type->label() }}</p>
                    </div>
                    <span class="pill {{ $w->is_active ? 'bg-emerald-500/15 text-emerald-600' : 'bg-slate-400/15 text-body' }} text-[10px]">{{ $w->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                @if($w->description)<p class="mt-2 text-xs text-muted">{{ $w->description }}</p>@endif
                <dl class="mt-3 grid grid-cols-2 gap-2 text-[11px] text-faint">
                    <div><dt class="text-faint">Min / Max</dt><dd class="text-body">{{ $w->min_funding_amount ? money($w->min_funding_amount) : '—' }} – {{ $w->max_funding_amount ? money($w->max_funding_amount) : '—' }}</dd></div>
                    <div><dt class="text-faint">Daily limit</dt><dd class="text-body">{{ $w->daily_limit ? money($w->daily_limit) : 'None' }}</dd></div>
                    <div><dt class="text-faint">Min KYC level</dt><dd class="text-body">{{ $w->min_kyc_level ?? 'None' }}</dd></div>
                    <div><dt class="text-faint">Funding</dt><dd class="text-body">{{ $w->automated_funding ? 'Automated' : 'Manual only' }}</dd></div>
                </dl>
                <div class="mt-4 flex flex-wrap gap-2 border-t border-app pt-3">
                    <button type="button" class="qa-btn" @click="openEdit({{ $w->id }})"><x-icon name="cog" class="h-3.5 w-3.5" /> Edit</button>
                    <form method="POST" action="{{ route('admin.china-wallet-types.active', $w) }}">@csrf<input type="hidden" name="is_active" value="{{ $w->is_active ? 0 : 1 }}"><button class="qa-btn">{{ $w->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                </div>
            </x-glass-card>
        @empty
            <div class="sm:col-span-2 xl:col-span-3">
                <x-empty icon="wallet" title="No wallet types configured" message="Add a wallet type to set limits and requirements for Alipay, WeChat Pay, or other China wallets.">
                    <x-slot:action><button type="button" class="qa-btn qa-btn-good" @click="openCreate()">Add wallet type</button></x-slot:action>
                </x-empty>
            </div>
        @endforelse
    </div>

    {{-- ============ ADD/EDIT DRAWER ============ --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-40 flex justify-end" style="background: rgba(0,0,0,.4);" @keydown.escape.window="drawerOpen=false">
        <div class="w-full max-w-lg overflow-y-auto card-solid h-full border-l border-app p-6 shadow-2xl" @click.outside="drawerOpen=false" x-show="drawerOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-bold text-strong" x-text="editingId ? 'Edit wallet type' : 'Add wallet type'"></h2>
                <button type="button" @click="drawerOpen=false" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="x" class="h-5 w-5" /></button>
            </div>
            <form method="POST" :action="action" class="space-y-4">
                @csrf
                <template x-if="editingId"><input type="hidden" name="_method" value="PUT"></template>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="label">Code</label><input name="code" x-model="form.code" :disabled="editingId !== null" class="field" placeholder="alipay"></div>
                    <div><label class="label">Name</label><input name="name" x-model="form.name" required class="field" placeholder="Alipay"></div>
                </div>
                <div><label class="label">Description</label><textarea name="description" x-model="form.description" rows="2" class="field"></textarea></div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Account identifier type</label>
                        <select name="account_identifier_type" x-model="form.account_identifier_type" class="field">
                            @foreach ($identifierTypes as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Linked provider</label>
                        <select name="provider_code" x-model="form.provider_code" class="field">
                            <option value="">None</option>
                            @foreach ($providers as $p)<option value="{{ $p->code }}">{{ $p->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3 border-t border-app pt-4">
                    <label class="flex items-center gap-2 text-xs text-body"><input type="checkbox" name="qr_required" value="1" x-model="form.qr_required" class="rounded"> QR required</label>
                    <label class="flex items-center gap-2 text-xs text-body"><input type="checkbox" name="account_name_required" value="1" x-model="form.account_name_required" class="rounded"> Name required</label>
                    <label class="flex items-center gap-2 text-xs text-body"><input type="checkbox" name="phone_required" value="1" x-model="form.phone_required" class="rounded"> Phone required</label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="label">Min KYC level</label><input type="number" min="0" max="3" name="min_kyc_level" x-model="form.min_kyc_level" class="field"></div>
                    <div><label class="label">Processing time estimate</label><input name="processing_time_estimate" x-model="form.processing_time_estimate" class="field" placeholder="Instant"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="label">Min funding amount</label><input type="number" step="0.01" name="min_funding_amount" x-model="form.min_funding_amount" class="field"></div>
                    <div><label class="label">Max funding amount</label><input type="number" step="0.01" name="max_funding_amount" x-model="form.max_funding_amount" class="field"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="label">Daily limit</label><input type="number" step="0.01" name="daily_limit" x-model="form.daily_limit" class="field"></div>
                    <div><label class="label">Monthly limit</label><input type="number" step="0.01" name="monthly_limit" x-model="form.monthly_limit" class="field"></div>
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-app pt-4">
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="automated_funding" value="1" x-model="form.automated_funding" class="rounded"> Automated funding</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="manual_funding" value="1" x-model="form.manual_funding" class="rounded"> Manual funding</label>
                    <label class="flex items-center gap-2 text-sm text-body col-span-2"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded"> Active</label>
                </div>

                <div><label class="label">Customer instructions</label><textarea name="customer_instructions" x-model="form.customer_instructions" rows="2" class="field"></textarea></div>
                <div><label class="label">Admin notes</label><textarea name="admin_notes" x-model="form.admin_notes" rows="2" class="field"></textarea></div>

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
function chinaWalletTypesPage() {
    const defaults = {
        code: '', name: '', description: '', account_identifier_type: 'custom', provider_code: '',
        qr_required: false, account_name_required: true, phone_required: false,
        min_kyc_level: '', processing_time_estimate: '', min_funding_amount: '', max_funding_amount: '',
        daily_limit: '', monthly_limit: '', automated_funding: false, manual_funding: true,
        is_active: true, customer_instructions: '', admin_notes: '',
    };
    return {
        drawerOpen: false,
        editingId: null,
        form: { ...defaults },
        baseUrl: '{{ url('/admin/china-wallet-types') }}',
        wallets: @json($walletsJson),
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('wallet-types-add', () => this.openCreate());
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; });
        },
        get action() { return this.editingId ? `${this.baseUrl}/${this.editingId}` : this.baseUrl; },
        openCreate() { this.editingId = null; this.form = { ...defaults }; this.drawerOpen = true; },
        openEdit(id) {
            const w = this.wallets.find((x) => x.id === id);
            if (!w) return;
            this.editingId = id;
            this.form = { ...defaults, ...w };
            this.drawerOpen = true;
        },
    };
}
</script>
@endpush
