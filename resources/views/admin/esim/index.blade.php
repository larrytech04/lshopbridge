@extends('layouts.admin')
@section('page-title', 'eSIM Operations')

@php
    $tabs = ['pending' => 'Awaiting fulfilment', 'ready' => 'Ready', 'failed' => 'Failed', 'all' => 'All'];
@endphp

@section('content')
<div x-data="esimOpsConsole()" class="space-y-5">

    <div>
        <h1 class="text-2xl font-bold text-strong">eSIM Operations</h1>
        <p class="text-sm text-muted">Every eSIM order here is paid but has no live provider connection to auto-fulfil it. Enter the real activation details once you've obtained them, rather than the platform ever fabricating one.</p>
    </div>

    {{-- ============ PROVIDER CONNECTION ============ --}}
    <div x-data="{ open: {{ $providerHasCredentials ? 'false' : 'true' }} }" class="card-solid rounded-3xl border border-app p-5">
        <button type="button" class="flex w-full items-center justify-between text-left" @click="open = !open">
            <div>
                <h2 class="font-semibold text-strong">Airalo Partner API connection</h2>
                <p class="text-xs text-faint">No credentials exist yet in this environment. Paste in sandbox or production keys the moment you have them, nothing here is simulated.</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span class="pill {{ $provider?->status->color() }} text-[10px]">{{ $provider?->status->label() ?? 'Not connected' }}</span>
                <x-icon name="chevron-down" class="h-4 w-4 text-faint transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
            </div>
        </button>
        <div x-show="open" x-collapse class="mt-4">
            <form method="POST" action="{{ route('admin.esim.provider.update') }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label class="label">Environment</label>
                    <select name="environment" class="field">
                        <option value="sandbox" {{ $providerEnvironment === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                        <option value="production" {{ $providerEnvironment === 'production' ? 'selected' : '' }}>Production</option>
                    </select>
                </div>
                <div></div>
                <div>
                    <label class="label">Client ID</label>
                    <input name="client_id" class="field" placeholder="Airalo client_id" required>
                </div>
                <div>
                    <label class="label">Client secret</label>
                    <input name="client_secret" type="password" class="field" placeholder="Airalo client_secret" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Webhook secret (optional)</label>
                    <input name="webhook_secret" type="password" class="field" placeholder="Only if Airalo gives you a webhook signing secret">
                </div>
                @error('client_id')<p class="text-xs text-rose-600 sm:col-span-2">{{ $message }}</p>@enderror
                <div class="flex gap-2 sm:col-span-2">
                    <button type="submit" class="btn btn-primary">Save &amp; test connection</button>
                    @if ($providerHasCredentials)
                        <form method="POST" action="{{ route('admin.esim.provider.disconnect') }}" onsubmit="return confirm('Disconnect Airalo? New eSIM orders will route to manual review.')">
                            @csrf
                            <button type="submit" class="qa-btn qa-btn-warn">Disconnect</button>
                        </form>
                    @endif
                </div>
            </form>
            <p class="mt-3 text-[11px] text-faint">Credentials are encrypted at rest and never shown again after saving. Test connection calls Airalo's own token endpoint for real, it never simulates success.</p>
        </div>
    </div>

    <div class="no-scrollbar flex gap-1.5 overflow-x-auto rounded-2xl border border-app p-1.5">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.esim.provisioning.index', ['tab' => $key]) }}"
               class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-medium {{ $activeTab === $key ? 'bg-brand-500 text-white' : 'text-muted hover:surface-2' }}">
                {{ $label }} <span class="opacity-70">({{ $counts[$key] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-3">Order</th>
                    <th class="px-3 py-3 font-medium">Customer</th>
                    <th class="px-3 py-3 font-medium">Item</th>
                    <th class="px-3 py-3 font-medium">Provider</th>
                    <th class="px-3 py-3 font-medium">Status</th>
                    <th class="px-3 py-3 font-medium">Waiting since</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @forelse ($provisionings as $p)
                    <tr class="hover:surface cursor-pointer" @click="openDrawer({{ $p->id }})">
                        <td class="px-3 py-3 font-mono text-xs text-muted">{{ $p->orderItem->order->reference }}</td>
                        <td class="px-3 py-3 text-body">{{ $p->orderItem->order->user->name }}</td>
                        <td class="px-3 py-3 text-body">{{ $p->orderItem->name }}</td>
                        <td class="px-3 py-3 text-xs capitalize text-body">{{ str_replace('_', ' ', $p->provider) }}</td>
                        <td class="px-3 py-3"><x-status-badge :status="$p->status" /></td>
                        <td class="px-3 py-3 text-xs text-faint">{{ $p->created_at->diffForHumans() }}</td>
                        <td class="px-3 py-3 text-right" @click.stop>
                            <button type="button" class="text-brand-500 text-sm" @click="openDrawer({{ $p->id }})">Review →</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-0">
                        <x-empty icon="sim" title="Nothing waiting on staff" message="Paid eSIM orders that a connected provider can't fulfil automatically will show up here." />
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $provisionings->links() }}</div>

    {{-- ============ REVIEW DRAWER ============ --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 flex justify-end" style="background: rgba(0,0,0,.4)" @click.self="drawerOpen = false">
        <div class="h-full w-full max-w-lg overflow-y-auto p-5" style="background: var(--surface-0);" @click.stop>
            <template x-if="!drawer">
                <p class="py-10 text-center text-sm text-muted">Loading…</p>
            </template>
            <template x-if="drawer">
                <div class="space-y-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-strong" x-text="drawer.order.reference"></h2>
                            <p class="text-sm text-muted" x-text="drawer.order.customer + ' · ' + drawer.order.email"></p>
                        </div>
                        <button type="button" class="text-muted" @click="drawerOpen = false"><x-icon name="x" class="h-5 w-5" /></button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><p class="text-xs text-faint">Item</p><p class="text-body" x-text="drawer.item.name"></p></div>
                        <div><p class="text-xs text-faint">Paid</p><p class="text-body" x-text="drawer.order.paid_at || 'Not paid yet'"></p></div>
                        <div><p class="text-xs text-faint">Total</p><p class="text-body" x-text="drawer.order.total + ' ' + drawer.order.currency"></p></div>
                        <div><p class="text-xs text-faint">Status</p><p class="text-body" x-text="drawer.provisioning.status"></p></div>
                    </div>

                    <template x-if="drawer.variant">
                        <div class="rounded-2xl border border-app p-3 text-xs text-muted">
                            <p><span class="text-faint">Plan:</span> <span x-text="drawer.variant.name"></span></p>
                            <p x-show="drawer.variant.external_id"><span class="text-faint">Provider package ID:</span> <span class="font-mono" x-text="drawer.variant.external_id"></span></p>
                            <p x-show="drawer.variant.data_amount"><span class="text-faint">Data:</span> <span x-text="drawer.variant.data_amount"></span> · <span class="text-faint">Validity:</span> <span x-text="drawer.variant.validity_days"></span> days</p>
                        </div>
                    </template>

                    <p x-show="drawer.provisioning.provider_error" class="rounded-xl bg-rose-500/10 p-3 text-xs text-rose-600" x-text="drawer.provisioning.provider_error"></p>

                    <template x-if="drawer.provisioning.status === 'pending_provisioning'">
                        <form :action="`/admin/esim/provisioning/${drawer.provisioning.id}/complete`" method="POST" class="space-y-3 rounded-2xl border border-app p-4">
                            @csrf
                            <p class="text-xs font-semibold uppercase tracking-wider text-faint">Enter real activation details</p>
                            <input name="provider" placeholder="Provider (e.g. airalo, manual)" class="field text-sm">
                            <input name="lpa_string" placeholder="Full LPA string (LPA:1$...$...), or fill the two fields below instead" class="field text-sm">
                            <div class="grid grid-cols-2 gap-2">
                                <input name="sm_dp_address" placeholder="SM-DP+ address" class="field text-sm">
                                <input name="activation_code" placeholder="Activation code" class="field text-sm">
                            </div>
                            <input name="confirmation_code" placeholder="Confirmation code (if provided)" class="field text-sm">
                            <input name="iccid" placeholder="ICCID (if known)" class="field text-sm">
                            <input name="direct_install_url" placeholder="Direct install URL (Apple universal link, if provided)" class="field text-sm">
                            <textarea name="admin_notes" rows="2" placeholder="Internal notes (optional)" class="field text-sm"></textarea>
                            @error('lpa_string')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                            <button type="submit" class="btn btn-primary w-full">Mark ready &amp; notify customer</button>
                        </form>
                    </template>

                    <template x-if="drawer.provisioning.status === 'pending_provisioning'">
                        <form :action="`/admin/esim/provisioning/${drawer.provisioning.id}/fail`" method="POST" class="space-y-2">
                            @csrf
                            <input name="reason" placeholder="Reason no provider can fulfil this (for the refund/cancel decision)" class="field text-sm" required>
                            <button type="submit" class="qa-btn w-full text-rose-600">Mark as failed</button>
                        </form>
                    </template>

                    <form :action="`/admin/esim/provisioning/${drawer.provisioning.id}/notes`" method="POST" class="space-y-2">
                        @csrf
                        <textarea name="admin_notes" rows="2" placeholder="Add a note" class="field text-sm"></textarea>
                        <button type="submit" class="qa-btn w-full">Save note</button>
                    </form>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function esimOpsConsole() {
    return {
        drawerOpen: false, drawer: null,
        async openDrawer(id) {
            this.drawerOpen = true;
            this.drawer = null;
            const res = await fetch(`/admin/esim/provisioning/${id}/row-detail`);
            this.drawer = await res.json();
        },
    };
}
</script>
@endpush
