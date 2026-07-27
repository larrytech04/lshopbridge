@extends('layouts.admin')
@section('page-title', 'Payment Methods')

@php
    $summaryCards = [
        ['Total', $summary['total'], 'list', 'slate'],
        ['Active', $summary['active'], 'check-circle', 'emerald'],
        ['Draft', $summary['draft'], 'doc', 'sky'],
        ['Disabled', $summary['disabled'], 'ban', 'amber'],
        ['Archived', $summary['archived'], 'trash', 'slate'],
        ['Automated', $summary['automated'], 'refresh', 'emerald'],
        ['Manual', $summary['manual'], 'clock', 'amber'],
    ];

    $methodsJson = [];
    foreach ($methods as $m) {
        $methodsJson[] = [
            'id' => $m->id, 'code' => $m->code, 'name' => $m->name, 'type' => $m->type,
            'provider_code' => $m->provider_code, 'description' => $m->description, 'instructions' => $m->instructions,
            'currency' => $m->currency, 'min_amount' => (float) $m->min_amount, 'max_amount' => (float) $m->max_amount,
            'countries' => $m->countries ?? [], 'currencies' => $m->currencies ?? [], 'status' => $m->status->value,
            'is_automated' => $m->is_automated, 'requires_proof' => $m->requires_proof, 'deposit_enabled' => $m->deposit_enabled,
            'marketplace_enabled' => $m->marketplace_enabled, 'refund_support' => $m->refund_support,
            'partial_refund_support' => $m->partial_refund_support, 'requires_manual_review' => $m->requires_manual_review,
            'kyc_level_required' => $m->kyc_level_required, 'processing_time_estimate' => $m->processing_time_estimate,
            'admin_notes' => $m->admin_notes,
        ];
    }
@endphp

@section('content')
<div x-data="paymentMethodsPage()" x-init="init()" class="space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Payment Methods</h1>
            <p class="text-sm text-muted">Configure which payment channels customers see for deposits and marketplace checkout.</p>
        </div>
        <button type="button" class="qa-btn qa-btn-good" @click="openCreate()"><x-icon name="plus" class="h-3.5 w-3.5" /> Add payment method</button>
    </div>

    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-4 xl:grid-cols-7">
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
        <table class="w-full min-w-[1100px] text-left text-sm">
            <thead class="border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-4 py-3 font-medium">Method</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Provider</th>
                    <th class="px-4 py-3 font-medium">Currency</th>
                    <th class="px-4 py-3 font-medium">Min / Max</th>
                    <th class="px-4 py-3 font-medium">Deposit</th>
                    <th class="px-4 py-3 font-medium">Marketplace</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Updated</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @forelse ($methods as $m)
                    <tr class="hover:surface {{ $m->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-strong">{{ $m->name }}</p>
                            <p class="text-[11px] text-faint">{{ $m->code }} @if($m->is_automated) · Automated @else · Manual @endif</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-body">{{ ucfirst($m->type) }}</td>
                        <td class="px-4 py-3 text-xs text-body">{{ $m->provider_code ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-body">{{ $m->currency ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-faint">{{ money($m->min_amount, $m->currency ?? 'XAF') }} – {{ money($m->max_amount, $m->currency ?? 'XAF') }}</td>
                        <td class="px-4 py-3">@if($m->deposit_enabled)<x-icon name="check-circle" class="h-4 w-4 text-emerald-500" />@else<x-icon name="x" class="h-4 w-4 text-faint" />@endif</td>
                        <td class="px-4 py-3">@if($m->marketplace_enabled)<x-icon name="check-circle" class="h-4 w-4 text-emerald-500" />@else<x-icon name="x" class="h-4 w-4 text-faint" />@endif</td>
                        <td class="px-4 py-3"><span class="pill {{ $m->status->color() }} text-[10px]">{{ $m->status->label() }}</span></td>
                        <td class="px-4 py-3 text-xs text-faint">{{ $m->updated_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="chevron-down" class="h-4 w-4" /></button>
                                <div x-show="open" x-cloak x-transition class="card-solid absolute right-0 z-20 mt-1 w-56 rounded-xl border border-app p-1.5 text-left shadow-lg">
                                    @if(!$m->trashed())
                                        <button type="button" @click="openEdit({{ $m->id }}); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="cog" class="h-4 w-4" /> Edit</button>
                                        @foreach ($statuses as $s)
                                            @if($s !== $m->status)
                                                <form method="POST" action="{{ route('admin.methods.status', $m) }}">@csrf<input type="hidden" name="status" value="{{ $s->value }}"><button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface">Set {{ $s->label() }}</button></form>
                                            @endif
                                        @endforeach
                                        <form method="POST" action="{{ route('admin.methods.destroy', $m) }}" onsubmit="return confirm('Archive this payment method? Historical deposits keep their own snapshot and are unaffected.')">@csrf @method('DELETE')<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="trash" class="h-4 w-4" /> Archive</button></form>
                                    @else
                                        <form method="POST" action="{{ route('admin.methods.restore', $m) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="refresh" class="h-4 w-4" /> Restore</button></form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="p-0">
                        <x-empty icon="card" title="No payment methods yet" message="Add a payment method to start accepting deposits or marketplace payments.">
                            <x-slot:action><button type="button" class="qa-btn qa-btn-good" @click="openCreate()">Add payment method</button></x-slot:action>
                        </x-empty>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ============ ADD/EDIT DRAWER ============ --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-40 flex justify-end" style="background: rgba(0,0,0,.4);" @keydown.escape.window="drawerOpen=false">
        <div class="w-full max-w-lg overflow-y-auto card-solid h-full border-l border-app p-6 shadow-2xl" @click.outside="drawerOpen=false" x-show="drawerOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-bold text-strong" x-text="editingId ? 'Edit payment method' : 'Add payment method'"></h2>
                <button type="button" @click="drawerOpen=false" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="x" class="h-5 w-5" /></button>
            </div>
            <form method="POST" :action="action" class="space-y-4">
                @csrf
                <template x-if="editingId"><input type="hidden" name="_method" value="PUT"></template>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Code</label>
                        <input name="code" x-model="form.code" :disabled="editingId !== null" class="field" placeholder="mtn_momo">
                    </div>
                    <div>
                        <label class="label">Name</label>
                        <input name="name" x-model="form.name" required class="field" placeholder="MTN Mobile Money">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Type</label>
                        <select name="type" x-model="form.type" class="field">
                            <option value="momo">Mobile money</option>
                            <option value="bank">Bank transfer</option>
                            <option value="crypto">Crypto</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Provider</label>
                        <select name="provider_code" x-model="form.provider_code" class="field">
                            <option value="">None</option>
                            @foreach ($providers as $p)<option value="{{ $p->code }}">{{ $p->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label">Description</label>
                    <input name="description" x-model="form.description" class="field" placeholder="Shown to customers">
                </div>
                <div>
                    <label class="label">Instructions (manual methods)</label>
                    <textarea name="instructions" x-model="form.instructions" rows="2" class="field"></textarea>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="label">Currency</label>
                        <input name="currency" x-model="form.currency" maxlength="3" class="field uppercase" placeholder="XAF">
                    </div>
                    <div>
                        <label class="label">Min amount</label>
                        <input type="number" step="0.01" name="min_amount" x-model="form.min_amount" class="field">
                    </div>
                    <div>
                        <label class="label">Max amount</label>
                        <input type="number" step="0.01" name="max_amount" x-model="form.max_amount" class="field">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Available countries (blank = all)</label>
                        <select name="countries[]" multiple x-model="form.countries" class="field h-28">
                            @foreach ($countries as $c)<option value="{{ $c->iso2 }}">{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Available currencies (blank = all)</label>
                        <select name="currencies[]" multiple x-model="form.currencies" class="field h-28">
                            @foreach ($currencies as $cu)<option value="{{ $cu->code }}">{{ $cu->code }} — {{ $cu->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label">Status</label>
                    <select name="status" x-model="form.status" class="field">
                        @foreach ($statuses as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-app pt-4">
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_automated" value="1" x-model="form.is_automated" class="rounded"> Automated (API charge)</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="requires_proof" value="1" x-model="form.requires_proof" class="rounded"> Requires proof upload</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="deposit_enabled" value="1" x-model="form.deposit_enabled" class="rounded"> Enabled for deposits</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="marketplace_enabled" value="1" x-model="form.marketplace_enabled" class="rounded"> Enabled for marketplace</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="refund_support" value="1" x-model="form.refund_support" class="rounded"> Supports refunds</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="partial_refund_support" value="1" x-model="form.partial_refund_support" class="rounded"> Supports partial refunds</label>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="requires_manual_review" value="1" x-model="form.requires_manual_review" class="rounded"> Always require manual review</label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Min KYC level</label>
                        <input type="number" min="0" max="3" name="kyc_level_required" x-model="form.kyc_level_required" class="field">
                    </div>
                    <div>
                        <label class="label">Processing time estimate</label>
                        <input name="processing_time_estimate" x-model="form.processing_time_estimate" class="field" placeholder="Instant, 1-2 hours…">
                    </div>
                </div>

                <div>
                    <label class="label">Admin notes</label>
                    <textarea name="admin_notes" x-model="form.admin_notes" rows="2" class="field"></textarea>
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
function paymentMethodsPage() {
    const defaults = {
        code: '', name: '', type: 'momo', provider_code: '', description: '', instructions: '',
        currency: 'XAF', min_amount: 0, max_amount: 0, countries: [], currencies: [],
        status: 'active', is_automated: false, requires_proof: false, deposit_enabled: true,
        marketplace_enabled: true, refund_support: true, partial_refund_support: false,
        requires_manual_review: false, kyc_level_required: '', processing_time_estimate: '', admin_notes: '',
    };
    return {
        drawerOpen: false,
        editingId: null,
        form: { ...defaults },
        baseUrl: '{{ url('/admin/payment-methods') }}',
        methods: @json($methodsJson),
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('methods-add', () => this.openCreate());
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; });
        },
        get action() { return this.editingId ? `${this.baseUrl}/${this.editingId}` : this.baseUrl; },
        openCreate() { this.editingId = null; this.form = { ...defaults }; this.drawerOpen = true; },
        openEdit(id) {
            const m = this.methods.find((x) => x.id === id);
            if (!m) return;
            this.editingId = id;
            this.form = { ...defaults, ...m };
            this.drawerOpen = true;
        },
    };
}
</script>
@endpush
