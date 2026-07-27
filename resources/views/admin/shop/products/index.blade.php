@extends('layouts.admin')
@section('page-title', 'Products')

@php
    $tabs = [
        'all' => 'All', 'active' => 'Active', 'draft' => 'Draft', 'scheduled' => 'Scheduled',
        'out_of_stock' => 'Out of Stock', 'low_stock' => 'Low Stock', 'on_sale' => 'On Sale',
        'disabled' => 'Disabled', 'archived' => 'Archived', 'sync_errors' => 'Sync Errors',
    ];
    $summaryCards = [
        ['Total products', $summary['total'], 'list', 'slate'],
        ['Active', $summary['active'], 'check-circle', 'emerald'],
        ['Draft', $summary['draft'], 'doc', 'slate'],
        ['Out of stock', $summary['out_of_stock'], 'ban', 'rose'],
        ['Low stock', $summary['low_stock'], 'alert', 'amber'],
        ['On sale', $summary['on_sale'], 'giftcard', 'sky'],
        ['Imported', $summary['imported'], 'upload', 'sky'],
        ['Provider-synced', $summary['provider_synced'], 'refresh', 'sky'],
        ['With errors', $summary['with_errors'], 'alert', 'rose'],
        ['Units sold', $summary['units_sold'], 'chart', 'emerald'],
    ];
@endphp

@section('content')
<div x-data="productsConsole()" x-init="init()" class="space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Products</h1>
            <p class="text-sm text-muted">Create, import, synchronize, price, publish, and manage every product sold through LshopBridge.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative" x-data="{ open: false }" @click.outside="open=false">
                <button type="button" class="qa-btn qa-btn-good" @click="open=!open"><x-icon name="plus" class="h-3.5 w-3.5" /> Add product <x-icon name="chevron-down" class="h-3 w-3" /></button>
                <div x-show="open" x-cloak x-transition class="card-solid absolute right-0 z-20 mt-1 w-56 rounded-xl border border-app p-1.5 text-left shadow-lg">
                    @foreach (\App\Enums\ShopProductType::addProductOptions() as $t)
                        <a href="{{ route('admin.shop.products.create', ['type' => $t->value]) }}" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface">{{ $t->label() }}</a>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('admin.shop.imports.index') }}" class="qa-btn"><x-icon name="upload" class="h-3.5 w-3.5" /> Import Products</a>
            <a href="{{ route('admin.shop.imports.index') }}" class="qa-btn"><x-icon name="webhook" class="h-3.5 w-3.5" /> Connect Source</a>
            <a href="{{ route('admin.shop.products.export') }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export</a>
            <button type="button" class="qa-btn" @click="window.location.reload()"><x-icon name="refresh" class="h-3.5 w-3.5" /> Refresh</button>
        </div>
    </div>

    {{-- ============ SUMMARY CARDS ============ --}}
    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-5 xl:grid-cols-10">
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

    {{-- ============ STATUS TABS ============ --}}
    <div class="no-scrollbar flex gap-1.5 overflow-x-auto rounded-2xl border border-app p-1.5">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.shop.products.index', array_filter(['tab' => $key === 'all' ? null : $key, 'q' => $q])) }}"
               class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-medium {{ $activeTab === $key ? 'bg-brand-500 text-white' : 'text-muted hover:surface-2' }}">
                {{ $label }} <span class="opacity-70">({{ $tabCounts[$key] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    {{-- ============ SEARCH + FILTERS ============ --}}
    <div class="card-solid space-y-4 rounded-3xl border border-app p-5 shadow-sm">
        <form method="GET" class="space-y-4">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                    <input x-ref="searchInput" name="q" value="{{ $q }}" placeholder="Search name, SKU, barcode, brand, supplier…" class="field !rounded-full pl-11 pr-4" @input.debounce.500ms="$el.form.requestSubmit()">
                </div>
                <button type="button" class="qa-btn" @click="filtersOpen = !filtersOpen"><x-icon name="filter" class="h-3.5 w-3.5" /> Filters</button>
                <a href="{{ route('admin.shop.products.index') }}" class="qa-btn">Clear filters</a>
            </div>
            <div x-show="filtersOpen" x-collapse x-cloak class="grid gap-3 border-t border-app pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <select name="category" class="field"><option value="">Any category</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
                <select name="type" class="field"><option value="">Any type</option>@foreach ($productTypes as $t)<option value="{{ $t->value }}" @selected(request('type')===$t->value)>{{ $t->label() }}</option>@endforeach</select>
                <select name="supplier" class="field"><option value="">Any supplier</option>@foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected(request('supplier') == $s->id)>{{ $s->name }}</option>@endforeach</select>
                <select name="source" class="field"><option value="">Any source</option><option value="native" @selected(request('source')==='native')>Native</option><option value="csv" @selected(request('source')==='csv')>CSV import</option><option value="json" @selected(request('source')==='json')>JSON import</option></select>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="has_errors" value="1" @checked(request('has_errors')==='1') class="rounded"> Has import errors only</label>
                <div class="sm:col-span-2 lg:col-span-4"><button class="btn btn-primary text-sm">Apply filters</button></div>
            </div>
        </form>

        <div x-show="selected.length > 0" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
            <span class="text-xs font-semibold text-strong" x-text="selected.length + ' selected'"></span>
            <button type="button" class="qa-btn qa-btn-good" @click="runBulk('activate')">Activate</button>
            <button type="button" class="qa-btn qa-btn-warn" @click="runBulk('disable')">Disable</button>
            <button type="button" class="qa-btn qa-btn-danger" @click="runBulk('archive')">Archive</button>
        </div>
    </div>

    {{-- ============ TABLE ============ --}}
    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[1400px] text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded"></th>
                    <th class="sticky left-0 z-10 px-3 py-3 font-medium" style="background: var(--surface-1);">Product</th>
                    <th class="px-3 py-3 font-medium">Category</th>
                    <th class="px-3 py-3 font-medium">Type</th>
                    <th class="px-3 py-3 font-medium">Source</th>
                    <th class="px-3 py-3 font-medium">Variants</th>
                    <th class="px-3 py-3 font-medium">Cost</th>
                    <th class="px-3 py-3 font-medium">Price</th>
                    <th class="px-3 py-3 font-medium">Margin</th>
                    <th class="px-3 py-3 font-medium">Stock</th>
                    <th class="px-3 py-3 font-medium">Sold</th>
                    <th class="px-3 py-3 font-medium">Status</th>
                    <th class="px-3 py-3 font-medium">Updated</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @forelse ($products as $product)
                    @php
                        $lowest = $product->variants->sortBy('price')->first();
                        $margin = $lowest?->profitMarginPercent();
                        $status = $statusOf($product);
                        $statusColors = ['active' => 'bg-emerald-500/15 text-emerald-600', 'draft' => 'bg-gray-400/15 text-gray-600', 'scheduled' => 'bg-sky-500/15 text-sky-600', 'disabled' => 'bg-amber-500/15 text-amber-600', 'archived' => 'bg-gray-400/15 text-gray-600'];
                    @endphp
                    <tr class="hover:surface cursor-pointer" :class="{ 'surface-2': highlighted === {{ $loop->index }} }" @click="highlighted = {{ $loop->index }}; openDrawer('{{ $product->slug }}')">
                        <td class="px-3 py-3" @click.stop><input type="checkbox" value="{{ $product->id }}" x-model="selected" class="rounded"></td>
                        <td class="sticky left-0 z-10 px-3 py-3" style="background: var(--surface-1);">
                            <div class="flex items-center gap-2">
                                <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-lg surface-2 text-brand-400">
                                    @if ($product->image_path)<img src="{{ $product->image_path }}" class="h-full w-full object-cover" alt="">@else<x-icon name="giftcard" class="h-4 w-4" />@endif
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-strong">{{ $product->name }}</p>
                                    <p class="truncate text-[11px] text-faint">{{ $product->brand ?? '—' }} · {{ $lowest?->sku ?? 'no SKU' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-xs text-body">{{ $product->category?->name ?? '—' }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $product->type->label() }}</td>
                        <td class="px-3 py-3"><span class="pill bg-slate-400/15 text-body text-[10px]">{{ ucfirst($product->source) }}</span></td>
                        <td class="px-3 py-3 text-xs text-body">{{ $product->variants->count() }}</td>
                        <td class="px-3 py-3 font-mono text-xs text-faint">{{ $lowest?->cost_price !== null ? money($lowest->cost_price, $lowest->currency) : '—' }}</td>
                        <td class="px-3 py-3 font-mono text-xs text-body">{{ $lowest ? money($lowest->effectivePrice(), $lowest->currency) : '—' }}</td>
                        <td class="px-3 py-3 text-xs {{ $margin !== null && $margin < 10 ? 'text-rose-500' : 'text-body' }}">{{ $margin !== null ? number_format($margin, 1).'%' : '—' }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $lowest?->stock ?? 'Unlimited' }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $product->sales_count }}</td>
                        <td class="px-3 py-3"><span class="pill {{ $statusColors[$status] ?? 'bg-slate-400/15 text-body' }} text-[10px]">{{ ucfirst(str_replace('_',' ',$status)) }}</span></td>
                        <td class="px-3 py-3 text-xs text-faint">{{ $product->updated_at->diffForHumans() }}</td>
                        <td class="px-3 py-3 text-right" @click.stop>
                            <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="chevron-down" class="h-4 w-4" /></button>
                                <div x-show="open" x-cloak x-transition class="card-solid absolute right-0 z-20 mt-1 w-52 rounded-xl border border-app p-1.5 text-left shadow-lg">
                                    <button type="button" @click="openDrawer('{{ $product->slug }}'); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="eye" class="h-4 w-4" /> View product</button>
                                    <a href="{{ route('admin.shop.products.edit', $product) }}" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="cog" class="h-4 w-4" /> Edit product</a>
                                    <form method="POST" action="{{ route('admin.shop.products.duplicate', $product) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="copy" class="h-4 w-4" /> Duplicate</button></form>
                                    <a href="{{ route('shop.show', $product) }}" target="_blank" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="globe" class="h-4 w-4" /> Preview storefront</a>
                                    <button type="button" @click="scheduleTarget='{{ $product->slug }}'; scheduleModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="clock" class="h-4 w-4" /> Schedule publish</button>
                                    <form method="POST" action="{{ route('admin.shop.products.toggle-active', $product) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="{{ $product->status->value === 'active' ? 'ban' : 'check' }}" class="h-4 w-4" /> {{ $product->status->value === 'active' ? 'Disable' : 'Activate' }}</button></form>
                                    <form method="POST" action="{{ route('admin.shop.products.destroy', $product) }}" onsubmit="return confirm('Archive this product? Completed orders keep their own snapshot and are unaffected.')">@csrf @method('DELETE')<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="trash" class="h-4 w-4" /> Archive</button></form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14" class="p-0">
                        <x-empty icon="bag" title="No products found" message="Create a product or import from a connected source to begin selling.">
                            <x-slot:action>
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('admin.shop.products.create') }}" class="qa-btn qa-btn-good">Add product</a>
                                    <a href="{{ route('admin.shop.products.index') }}" class="qa-btn">Clear filters</a>
                                    <a href="{{ route('admin.shop.imports.index') }}" class="qa-btn">Import Center</a>
                                </div>
                            </x-slot:action>
                        </x-empty>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.shop.products.partials.modals')
</div>
@endsection

@push('scripts')
<script>
function productsConsole() {
    return {
        filtersOpen: false,
        selected: [],
        drawerOpen: false, drawer: null,
        scheduleModal: false, scheduleTarget: null,
        productIds: @json($products->pluck('slug')),
        highlighted: -1,
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('products-search', () => this.$refs.searchInput?.focus());
                window.ShortcutManager.registerAction('products-add', () => { window.location.href = '{{ route('admin.shop.products.create') }}'; });
                window.ShortcutManager.registerAction('products-import', () => { window.location.href = '{{ route('admin.shop.imports.index') }}'; });
                window.ShortcutManager.registerAction('products-filters', () => { this.filtersOpen = !this.filtersOpen; });
                window.ShortcutManager.registerAction('products-next', () => this.moveHighlight(1));
                window.ShortcutManager.registerAction('products-prev', () => this.moveHighlight(-1));
                window.ShortcutManager.registerAction('products-open', () => { if (this.highlighted >= 0) this.openDrawer(this.productIds[this.highlighted]); });
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; this.scheduleModal = false; });
        },
        moveHighlight(delta) {
            if (this.productIds.length === 0) return;
            this.highlighted = (this.highlighted + delta + this.productIds.length) % this.productIds.length;
        },
        toggleAll(checked) { this.selected = checked ? @json($products->pluck('id')) : []; },
        runBulk(action) {
            if (this.selected.length === 0) return;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = '{{ route('admin.shop.products.bulk-action') }}';
            let html = '@csrf' + `<input type="hidden" name="action" value="${action}">`;
            this.selected.forEach((id) => { html += `<input type="hidden" name="ids[]" value="${id}">`; });
            f.innerHTML = html;
            document.body.appendChild(f); f.submit();
        },
        async openDrawer(id) {
            this.drawerOpen = true;
            this.drawer = null;
            const res = await fetch(`/admin/shop/products/${id}/row-detail`);
            this.drawer = await res.json();
        },
    };
}
</script>
@endpush
