@extends('layouts.admin')
@section('page-title', 'Product Import Center')

@section('content')
<div x-data="importCenter()" x-init="init()" class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-strong">Product Import Center</h1>
        <p class="text-sm text-muted">Connect authorized sources, import product files, and track synchronization — every source below is honestly labeled by real connection status.</p>
        <p class="mt-1 text-xs text-faint">Only Native/Manual, CSV, and JSON have a real, working connector today. Every other source is a genuine slot waiting on real API credentials — connecting it here saves credentials but does not simulate a working integration.</p>
    </div>

    @foreach ($groupLabels as $groupKey => $groupLabel)
        @continue (! isset($grouped[$groupKey]))
        <div class="card-solid rounded-3xl border border-app p-5">
            <button type="button" class="flex w-full items-center justify-between text-left" @click="toggleGroup('{{ $groupKey }}')">
                <h2 class="font-semibold text-strong">{{ $groupLabel }} <span class="text-xs font-normal text-faint">({{ $grouped[$groupKey]->count() }})</span></h2>
                <x-icon name="chevron-down" class="h-4 w-4 text-faint transition-transform" x-bind:class="openGroups.includes('{{ $groupKey }}') ? 'rotate-180' : ''" />
            </button>
            <div x-show="openGroups.includes('{{ $groupKey }}')" x-collapse class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($grouped[$groupKey] as $source)
                    <div class="rounded-2xl border border-app p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0"><p class="truncate font-semibold text-strong">{{ $source->name }}</p><p class="text-[11px] text-faint">{{ $source->products_count }} product(s) imported</p></div>
                            <span class="pill {{ $source->status->color() }} shrink-0 text-[10px]">{{ $source->status->label() }}</span>
                        </div>
                        <div class="mt-2 space-y-0.5 text-[11px] text-faint">
                            <p>Last import: {{ $source->last_import_at?->diffForHumans() ?? 'never' }}</p>
                            <p>Auto-sync: {{ ucfirst(str_replace('_',' ',$source->auto_sync)) }}</p>
                            @if ($source->error_count > 0)<p class="text-rose-500">{{ $source->error_count }} error(s)</p>@endif
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @if ($source->code === 'esim_providers')
                                {{-- eSIM providers speak a different connector contract (real ongoing
                                     lifecycle: provisioning/usage/top-ups, not one-shot catalog import) —
                                     credentials are managed on their own dedicated screen, not this
                                     generic modal (whose fields don't even match Airalo's). --}}
                                <a href="{{ route('admin.esim.provisioning.index') }}" class="qa-btn">Manage in eSIM Operations →</a>
                            @elseif ($source->isUsableWithoutCredentials())
                                <button type="button" class="qa-btn" @click="importTarget = {{ $source->id }}; importModal = true">Import file</button>
                            @else
                                <button type="button" class="qa-btn" @click="connectTarget = {{ $source->id }}; connectModal = true">Connect</button>
                            @endif
                            @unless ($source->code === 'esim_providers')
                                <form method="POST" action="{{ route('admin.shop.imports.test-connection', $source) }}"><button class="qa-btn">Test</button></form>
                                @if ($source->status->value !== 'not_connected')
                                    <form method="POST" action="{{ route('admin.shop.imports.disconnect', $source) }}" onsubmit="return confirm('Disconnect this source?')">@csrf<button class="qa-btn qa-btn-warn">Disconnect</button></form>
                                @endif
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- ============ RECENT IMPORTS ============ --}}
    <div class="card-solid rounded-3xl border border-app p-5">
        <h2 class="font-semibold text-strong">Recent Imports</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">
                <thead class="border-b border-app text-muted"><tr><th class="px-2 py-2 font-medium">Source</th><th class="px-2 py-2 font-medium">Started</th><th class="px-2 py-2 font-medium">Status</th><th class="px-2 py-2 font-medium">Created / Updated / Skipped / Failed</th><th class="px-2 py-2"></th></tr></thead>
                <tbody class="divide-y divide-app">
                    @forelse ($recentImports as $import)
                        <tr>
                            <td class="px-2 py-2 text-body">{{ $import->importSource->name }}</td>
                            <td class="px-2 py-2 text-xs text-faint">{{ $import->startedBy?->name ?? 'System' }} · {{ $import->created_at->diffForHumans() }}</td>
                            <td class="px-2 py-2"><span class="pill {{ $import->status->color() }} text-[10px]">{{ $import->status->label() }}</span></td>
                            <td class="px-2 py-2 text-xs text-body">{{ $import->products_created }} / {{ $import->products_updated }} / {{ $import->products_skipped }} / {{ $import->products_failed }}</td>
                            <td class="px-2 py-2 text-right">
                                <button type="button" class="text-brand-500 text-xs" @click="viewRun({{ $import->id }})">Details</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-0"><x-empty icon="upload" title="No imports yet" message="Start a CSV or JSON import above to see its history here." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============ CONNECT CREDENTIALS MODAL ============ --}}
    <form method="POST" :action="`/admin/shop/imports/${connectTarget}/connect`" x-show="connectModal" x-cloak @click.self="connectModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-sm rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-strong">Connect source</h3>
            <p class="mt-1 text-xs text-muted">Credentials are encrypted at rest and never shown again after saving.</p>
            <div class="mt-3 space-y-2">
                <input name="credentials[api_key]" class="field" placeholder="API key">
                <input name="credentials[api_secret]" type="password" class="field" placeholder="API secret">
                <input name="credentials[store_url]" class="field" placeholder="Store URL (if applicable)">
                <input name="credentials[access_token]" type="password" class="field" placeholder="Access token (if applicable)">
            </div>
            <p class="mt-2 text-[11px] text-amber-600">No official API integration exists for this source yet — saving credentials here will not make it start working. This just stores them securely for when a real connector is built.</p>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="connectModal=false">Cancel</button><button class="btn btn-primary flex-1">Save &amp; test</button></div>
        </div>
    </form>

    {{-- ============ IMPORT FILE MODAL ============ --}}
    <form method="POST" :action="`/admin/shop/imports/${importTarget}/start`" enctype="multipart/form-data" x-show="importModal" x-cloak @click.self="importModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-sm rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-strong">Import products from file</h3>
            <p class="mt-1 text-xs text-muted">Header row: name, category, type, sku, price, cost_price, currency, stock, description, image_url, brand. Only name and price are required. Imported products are always created as drafts for review.</p>
            <input type="file" name="file" accept=".csv,.txt,.json" required class="field mt-3">
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="importModal=false">Cancel</button><button class="btn btn-primary flex-1">Queue import</button></div>
        </div>
    </form>

    {{-- ============ IMPORT RUN DETAIL MODAL ============ --}}
    <div x-show="runModal" x-cloak @click.self="runModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
        <div class="card-solid max-h-[80vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-app p-6" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-strong">Import #<span x-text="runDetail?.id"></span></h3>
                <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="runModal=false"><x-icon name="x" class="h-4 w-4" /></button>
            </div>
            <template x-if="runDetail">
                <div class="mt-3 space-y-3">
                    <div class="grid grid-cols-4 gap-2 text-center text-xs">
                        <div class="rounded-lg surface-2 p-2"><p class="text-lg font-bold text-emerald-600" x-text="runDetail.products_created"></p><p class="text-faint">Created</p></div>
                        <div class="rounded-lg surface-2 p-2"><p class="text-lg font-bold text-sky-600" x-text="runDetail.products_updated"></p><p class="text-faint">Updated</p></div>
                        <div class="rounded-lg surface-2 p-2"><p class="text-lg font-bold text-amber-600" x-text="runDetail.products_skipped"></p><p class="text-faint">Skipped</p></div>
                        <div class="rounded-lg surface-2 p-2"><p class="text-lg font-bold text-rose-600" x-text="runDetail.products_failed"></p><p class="text-faint">Failed</p></div>
                    </div>
                    <div class="space-y-1">
                        <template x-for="(entry, i) in (runDetail.log || [])" :key="i">
                            <p class="rounded-lg surface-2 px-2 py-1.5 text-xs" :class="entry.level === 'error' ? 'text-rose-600' : 'text-amber-600'" x-text="'Row ' + entry.row + ': ' + entry.message"></p>
                        </template>
                    </div>
                    <template x-if="runDetail.rollback_eligible > 0">
                        <form method="POST" :action="`/admin/shop/imports/runs/${runDetail.id}/rollback`" onsubmit="return confirm('Roll back this import? Draft products never ordered will be removed.')">
                            @csrf
                            <button class="qa-btn qa-btn-danger w-full" x-text="`Roll back ${runDetail.rollback_eligible} draft product(s)`"></button>
                        </form>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function importCenter() {
    return {
        openGroups: @json(array_keys($groupLabels)),
        connectModal: false, connectTarget: null,
        importModal: false, importTarget: null,
        runModal: false, runDetail: null,
        init() {
            window.addEventListener('close-overlays', () => { this.connectModal = false; this.importModal = false; this.runModal = false; });
        },
        toggleGroup(key) {
            this.openGroups = this.openGroups.includes(key) ? this.openGroups.filter((g) => g !== key) : [...this.openGroups, key];
        },
        async viewRun(id) {
            this.runModal = true;
            this.runDetail = null;
            const res = await fetch(`/admin/shop/imports/runs/${id}`);
            this.runDetail = await res.json();
        },
    };
}
</script>
@endpush
