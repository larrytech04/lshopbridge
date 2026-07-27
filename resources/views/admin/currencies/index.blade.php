@extends('layouts.admin')
@section('page-title', 'Currencies')

@php
    $summaryCards = [
        ['Total', $summary['total'], 'list', 'slate'],
        ['Active', $summary['active'], 'check-circle', 'emerald'],
        ['Wallet-enabled', $summary['wallet_enabled'], 'wallet', 'sky'],
        ['Reporting', $summary['reporting'], 'chart', 'amber'],
    ];

    $currenciesJson = [];
    foreach ($currencies as $c) {
        $currenciesJson[] = [
            'id' => $c->id, 'code' => $c->code, 'name' => $c->name, 'symbol' => $c->symbol,
            'decimals' => $c->decimals, 'thousands_separator' => $c->thousands_separator,
            'decimal_separator' => $c->decimal_separator, 'is_active' => $c->is_active,
            'wallet_enabled' => $c->wallet_enabled, 'deposit_enabled' => $c->deposit_enabled,
            'marketplace_enabled' => $c->marketplace_enabled, 'reporting_currency_enabled' => $c->reporting_currency_enabled,
            'admin_notes' => $c->admin_notes,
        ];
    }
@endphp

@section('content')
<div x-data="currenciesPage()" x-init="init()" class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Currencies</h1>
            <p class="text-sm text-muted">Currency metadata and availability only. Conversion rates between currencies live on <a href="{{ route('admin.rates.index') }}" class="text-brand-400 hover:underline">Exchange Rates</a>.</p>
        </div>
        <button type="button" class="qa-btn qa-btn-good" @click="openCreate()"><x-icon name="plus" class="h-3.5 w-3.5" /> Add currency</button>
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

    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-4 py-3 font-medium">Currency</th>
                    <th class="px-4 py-3 font-medium">Symbol</th>
                    <th class="px-4 py-3 font-medium">Decimals</th>
                    <th class="px-4 py-3 font-medium">Wallet</th>
                    <th class="px-4 py-3 font-medium">Deposits</th>
                    <th class="px-4 py-3 font-medium">Marketplace</th>
                    <th class="px-4 py-3 font-medium">Reporting</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @forelse ($currencies as $cu)
                    <tr class="hover:surface">
                        <td class="px-4 py-3"><p class="font-semibold text-strong">{{ $cu->code }}</p><p class="text-[11px] text-faint">{{ $cu->name }}</p></td>
                        <td class="px-4 py-3 text-body">{{ $cu->symbol }}</td>
                        <td class="px-4 py-3 text-body">{{ $cu->decimals }}</td>
                        <td class="px-4 py-3">@if($cu->wallet_enabled)<x-icon name="check-circle" class="h-4 w-4 text-emerald-500" />@else<x-icon name="x" class="h-4 w-4 text-faint" />@endif</td>
                        <td class="px-4 py-3">@if($cu->deposit_enabled)<x-icon name="check-circle" class="h-4 w-4 text-emerald-500" />@else<x-icon name="x" class="h-4 w-4 text-faint" />@endif</td>
                        <td class="px-4 py-3">@if($cu->marketplace_enabled)<x-icon name="check-circle" class="h-4 w-4 text-emerald-500" />@else<x-icon name="x" class="h-4 w-4 text-faint" />@endif</td>
                        <td class="px-4 py-3">@if($cu->reporting_currency_enabled)<x-icon name="check-circle" class="h-4 w-4 text-emerald-500" />@else<x-icon name="x" class="h-4 w-4 text-faint" />@endif</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.currencies.active', $cu) }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="is_active" value="{{ $cu->is_active ? 0 : 1 }}"><button class="pill {{ $cu->is_active ? 'bg-emerald-500/15 text-emerald-600' : 'bg-slate-400/15 text-body' }} text-[10px]">{{ $cu->is_active ? 'Active' : 'Inactive' }}</button></form>
                        </td>
                        <td class="px-4 py-3 text-right"><button type="button" class="qa-btn" @click="openEdit({{ $cu->id }})">Edit</button></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-0">
                        <x-empty icon="wallet" title="No currencies yet" message="Add a currency to control its display metadata and availability.">
                            <x-slot:action><button type="button" class="qa-btn qa-btn-good" @click="openCreate()">Add currency</button></x-slot:action>
                        </x-empty>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ ADD/EDIT DRAWER ============ --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-40 flex justify-end" style="background: rgba(0,0,0,.4);" @keydown.escape.window="drawerOpen=false">
        <div class="w-full max-w-md overflow-y-auto card-solid h-full border-l border-app p-6 shadow-2xl" @click.outside="drawerOpen=false" x-show="drawerOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-bold text-strong" x-text="editingId ? 'Edit currency' : 'Add currency'"></h2>
                <button type="button" @click="drawerOpen=false" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="x" class="h-5 w-5" /></button>
            </div>
            <form method="POST" :action="action" class="space-y-4">
                @csrf
                <template x-if="editingId"><input type="hidden" name="_method" value="PUT"></template>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="label">Code</label><input name="code" x-model="form.code" :disabled="editingId !== null" maxlength="3" class="field uppercase" placeholder="XAF"></div>
                    <div><label class="label">Symbol</label><input name="symbol" x-model="form.symbol" class="field" placeholder="FCFA"></div>
                </div>
                <div><label class="label">Name</label><input name="name" x-model="form.name" required class="field" placeholder="CFA Franc"></div>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="label">Decimals</label><input type="number" min="0" max="6" name="decimals" x-model="form.decimals" class="field"></div>
                    <div><label class="label">Thousands sep.</label><input name="thousands_separator" x-model="form.thousands_separator" maxlength="4" class="field"></div>
                    <div><label class="label">Decimal sep.</label><input name="decimal_separator" x-model="form.decimal_separator" maxlength="4" class="field"></div>
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-app pt-4">
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded"> Active</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="wallet_enabled" value="1" x-model="form.wallet_enabled" class="rounded"> Wallet enabled</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="deposit_enabled" value="1" x-model="form.deposit_enabled" class="rounded"> Deposit enabled</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="marketplace_enabled" value="1" x-model="form.marketplace_enabled" class="rounded"> Marketplace enabled</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="reporting_currency_enabled" value="1" x-model="form.reporting_currency_enabled" class="rounded"> Reporting currency</label>
                </div>

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
function currenciesPage() {
    const defaults = {
        code: '', name: '', symbol: '', decimals: 2, thousands_separator: ',', decimal_separator: '.',
        is_active: true, wallet_enabled: true, deposit_enabled: true, marketplace_enabled: true,
        reporting_currency_enabled: false, admin_notes: '',
    };
    return {
        drawerOpen: false,
        editingId: null,
        form: { ...defaults },
        baseUrl: '{{ url('/admin/currencies') }}',
        currencies: @json($currenciesJson),
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('currencies-add', () => this.openCreate());
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; });
        },
        get action() { return this.editingId ? `${this.baseUrl}/${this.editingId}` : this.baseUrl; },
        openCreate() { this.editingId = null; this.form = { ...defaults }; this.drawerOpen = true; },
        openEdit(id) {
            const c = this.currencies.find((x) => x.id === id);
            if (!c) return;
            this.editingId = id;
            this.form = { ...defaults, ...c };
            this.drawerOpen = true;
        },
    };
}
</script>
@endpush
