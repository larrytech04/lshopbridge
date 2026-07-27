@extends('layouts.admin')
@section('page-title', 'Product Categories')

@php
    $flatCategories = $categories->map(fn ($c) => ['id' => $c->id, 'slug' => $c->slug, 'name' => $c->name, 'parent_id' => $c->parent_id, 'sort' => $c->sort])->values();
@endphp

@section('content')
<div x-data="categoryManager()" x-init="init()" class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Product Categories</h1>
            <p class="text-sm text-muted">Organize the LshopBridge catalogue, storefront navigation, imports, fees, and product discovery.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="qa-btn qa-btn-good" @click="openAdd(null)"><x-icon name="plus" class="h-3.5 w-3.5" /> Add category</button>
            <a href="{{ route('admin.shop.imports.index') }}" class="qa-btn"><x-icon name="upload" class="h-3.5 w-3.5" /> Import categories</a>
            <a href="{{ route('admin.settings.index') }}" class="qa-btn"><x-icon name="cog" class="h-3.5 w-3.5" /> Category settings</a>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-5">
        {{-- ============ LEFT: TREE ============ --}}
        <div class="card-solid space-y-3 rounded-3xl border border-app p-4 lg:col-span-2">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                <input x-ref="searchInput" x-model="search" placeholder="Search categories…" class="field !rounded-full pl-10">
            </div>
            <div class="max-h-[70vh] space-y-1.5 overflow-y-auto">
                <template x-if="search.trim() !== ''">
                    <div class="space-y-1.5">
                        <template x-for="c in filteredFlat()" :key="c.id">
                            <div class="flex items-center gap-2 rounded-xl border border-app px-3 py-2 hover:surface cursor-pointer" @click="select(c.id, c.slug)">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg surface-2 text-brand-400"><x-icon name="sparkles" class="h-3.5 w-3.5" /></span>
                                <p class="truncate text-sm font-medium text-strong" x-text="c.name"></p>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="search.trim() === ''">
                    <div class="space-y-1.5">
                        @foreach ($tree as $node)
                            @include('admin.shop.partials.category-node', ['node' => $node, 'depth' => 0])
                        @endforeach
                        @if (count($tree) === 0)
                            <x-empty icon="list" title="No categories yet" message="Add your first category to start organizing products.">
                                <x-slot:action><button type="button" class="qa-btn qa-btn-good" @click="openAdd(null)">Add category</button></x-slot:action>
                            </x-empty>
                        @endif
                    </div>
                </template>
            </div>
        </div>

        {{-- ============ RIGHT: EDITOR ============ --}}
        <div class="card-solid rounded-3xl border border-app p-5 lg:col-span-3">
            <template x-if="mode === null">
                <div class="grid h-full place-items-center py-16 text-center text-sm text-faint">Select a category to view or edit it, or add a new one.</div>
            </template>

            <template x-if="mode !== null">
                <form method="POST" :action="mode === 'edit' ? `/admin/shop/categories/${form.slug}` : '{{ route('admin.shop.categories.store') }}'" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-strong" x-text="mode === 'edit' ? 'Edit category' : (form.parent_id ? 'Add subcategory' : 'Add category')"></h3>
                        <div class="flex gap-2" x-show="mode === 'edit'">
                            <button type="button" class="qa-btn" @click="openAdd(form.id)">Add subcategory</button>
                            <button type="button" class="qa-btn" @click="toggleActive()" x-text="form.is_active ? 'Deactivate' : 'Activate'"></button>
                            <button type="button" class="qa-btn qa-btn-danger" @click="archive()">Archive</button>
                        </div>
                    </div>

                    <template x-if="formErrors">
                        <ul class="space-y-1 rounded-lg bg-rose-500/10 px-3 py-2 text-xs text-rose-600">
                            <template x-for="(msg, key) in formErrors" :key="key"><li x-text="msg"></li></template>
                        </ul>
                    </template>
                    <template x-if="archiveMessage">
                        <p class="rounded-lg bg-amber-500/10 px-3 py-2 text-xs text-amber-700" x-text="archiveMessage"></p>
                    </template>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2"><label class="label">Category name</label><input name="name" x-model="form.name" required class="field"></div>
                        <div class="col-span-2"><label class="label">Parent category</label>
                            <select name="parent_id" x-model="form.parent_id" class="field">
                                <option value="">None (top-level)</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2"><label class="label">Description</label><textarea name="description" x-model="form.description" rows="2" class="field"></textarea></div>
                        <div><label class="label">Tagline</label><input name="tagline" x-model="form.tagline" class="field"></div>
                        <div><label class="label">Icon</label><input name="icon" x-model="form.icon" class="field" placeholder="sparkles"></div>
                        <div><label class="label">Accent</label><input name="accent" x-model="form.accent" class="field" placeholder="brand"></div>
                        <div><label class="label">Product type hint</label><input name="product_type" x-model="form.product_type" class="field" placeholder="e.g. esim"></div>
                        <div><label class="label">Default fee rule</label>
                            <select name="default_fee_id" x-model="form.default_fee_id" class="field">
                                <option value="">None</option>
                                @foreach ($fees as $fee)<option value="{{ $fee->id }}">{{ $fee->name }}</option>@endforeach
                            </select>
                        </div>
                        <div><label class="label">Display order</label><input type="number" name="sort" x-model.number="form.sort" class="field"></div>

                        <div class="col-span-2 flex flex-wrap gap-4 border-t border-app pt-3">
                            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded"> Active</label>
                            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="featured" value="1" x-model="form.featured" class="rounded"> Featured</label>
                            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="menu_visible" value="1" x-model="form.menu_visible" class="rounded"> Show in storefront menu</label>
                        </div>

                        <div class="col-span-2 border-t border-app pt-3"><p class="text-xs font-semibold uppercase text-faint">Marketplace navigation</p></div>
                        <div>
                            <label class="label">Badge</label>
                            <select name="navigation_badge" x-model="form.navigation_badge" class="field">
                                <option value="">None</option>
                                @foreach (['New','Popular','Sale','Limited','Coming Soon'] as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Badge colour</label>
                            <select name="navigation_badge_style" x-model="form.navigation_badge_style" class="field">
                                @foreach (['brand','emerald','amber','rose','slate'] as $style)<option value="{{ $style }}">{{ ucfirst($style) }}</option>@endforeach
                            </select>
                        </div>
                        <div><label class="label">Available from</label><input type="datetime-local" name="available_from" x-model="form.available_from" class="field"></div>
                        <div><label class="label">Available until</label><input type="datetime-local" name="available_until" x-model="form.available_until" class="field"></div>
                        <div class="col-span-2">
                            <label class="label">Restricted countries (blank = available everywhere)</label>
                            <select name="restricted_countries[]" multiple x-model="form.restricted_countries" class="field h-28">
                                @foreach ($countries as $c)<option value="{{ $c->iso2 }}">{{ $c->name }}</option>@endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-faint">Selected countries will NOT see this category. Hold Ctrl/Cmd to select multiple.</p>
                        </div>

                        <div class="col-span-2 border-t border-app pt-3"><p class="text-xs font-semibold uppercase text-faint">SEO</p></div>
                        <div class="col-span-2"><label class="label">SEO title</label><input name="seo_title" x-model="form.seo_title" class="field"></div>
                        <div class="col-span-2"><label class="label">Meta description</label><textarea name="meta_description" x-model="form.meta_description" rows="2" class="field"></textarea></div>
                        <div class="col-span-2"><label class="label">Canonical URL</label><input name="canonical_url" x-model="form.canonical_url" class="field"></div>

                        <div class="col-span-2"><label class="label">Internal note</label><textarea name="notes" x-model="form.notes" rows="2" class="field" placeholder="Never shown on the storefront"></textarea></div>
                    </div>

                    <template x-if="mode === 'edit'">
                        <div class="grid grid-cols-3 gap-3 border-t border-app pt-3 text-center text-xs">
                            <div class="rounded-lg surface-2 p-2"><p class="text-lg font-bold text-strong" x-text="form.products_count"></p><p class="text-faint">Products</p></div>
                            <div class="rounded-lg surface-2 p-2"><p class="text-lg font-bold text-strong" x-text="form.active_products_count"></p><p class="text-faint">Active products</p></div>
                            <div class="rounded-lg surface-2 p-2"><p class="text-lg font-bold text-strong" x-text="form.children_count"></p><p class="text-faint">Subcategories</p></div>
                        </div>
                    </template>

                    <div class="flex gap-2 border-t border-app pt-3">
                        <button type="button" class="btn btn-ghost flex-1" @click="mode = null">Cancel</button>
                        <button class="btn btn-primary flex-1" x-text="mode === 'edit' ? 'Save changes' : 'Create category'"></button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function categoryManager() {
    return {
        search: '',
        expanded: [],
        selectedId: null,
        mode: null,
        form: {},
        formErrors: @json($errors->any() ? $errors->toArray() : null),
        archiveMessage: null,
        flat: @json($flatCategories),
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('categories-add', () => this.openAdd(null));
                window.ShortcutManager.registerAction('categories-add-sub', () => this.openAdd(this.selectedId));
            }
            window.addEventListener('close-overlays', () => { this.mode = null; });
        },
        toggle(id) {
            this.expanded = this.expanded.includes(id) ? this.expanded.filter((x) => x !== id) : [...this.expanded, id];
        },
        filteredFlat() {
            const term = this.search.trim().toLowerCase();
            return this.flat.filter((c) => c.name.toLowerCase().includes(term));
        },
        async select(id, slug) {
            this.selectedId = id;
            this.mode = 'edit';
            this.archiveMessage = null;
            this.formErrors = null;
            const res = await fetch(`/admin/shop/categories/${slug}/row-detail`);
            this.form = await res.json();
        },
        openAdd(parentId) {
            this.selectedId = null;
            this.mode = 'add';
            this.formErrors = null;
            this.archiveMessage = null;
            this.form = { parent_id: parentId ?? '', is_active: true, menu_visible: true, featured: false, sort: 0, icon: 'sparkles', accent: 'brand', navigation_badge_style: 'brand', restricted_countries: [] };
        },
        toggleActive() {
            const f = document.createElement('form');
            f.method = 'POST'; f.action = `/admin/shop/categories/${this.form.slug}/toggle-active`;
            f.innerHTML = '@csrf';
            document.body.appendChild(f); f.submit();
        },
        archive() {
            if (! confirm('Archive this category? It must have no products or subcategories.')) return;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = `/admin/shop/categories/${this.form.slug}`;
            f.innerHTML = '@csrf<input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(f); f.submit();
        },
        siblings(id) {
            const node = this.flat.find((c) => c.id === id);
            return this.flat.filter((c) => c.parent_id === node.parent_id).sort((a, b) => a.sort - b.sort);
        },
        async swapAndSave(id, delta) {
            const group = this.siblings(id);
            const index = group.findIndex((c) => c.id === id);
            const target = index + delta;
            if (target < 0 || target >= group.length) return;
            [group[index], group[target]] = [group[target], group[index]];
            const ids = group.map((c) => c.id);
            await fetch('{{ route('admin.shop.categories.reorder') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ ids }),
            });
            window.location.reload();
        },
        moveUp(id) { this.swapAndSave(id, -1); },
        moveDown(id) { this.swapAndSave(id, 1); },
    };
}
</script>
@endpush
