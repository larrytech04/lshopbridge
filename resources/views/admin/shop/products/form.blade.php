@extends('layouts.admin')
@section('page-title', $product->exists ? 'Edit product' : 'New product')

@php
    $existingVariants = $product->exists ? $product->variants->map(fn($v) => [
        'id' => $v->id, 'name' => $v->name, 'sku' => $v->sku, 'barcode' => $v->barcode,
        'price' => (float) $v->price, 'cost_price' => $v->cost_price !== null ? (float) $v->cost_price : null,
        'compare_at_price' => $v->compare_at_price !== null ? (float) $v->compare_at_price : null,
        'currency' => $v->currency, 'data_amount' => $v->data_amount, 'validity_days' => $v->validity_days,
        'denomination' => $v->denomination !== null ? (float) $v->denomination : null,
        'stock' => $v->stock, 'low_stock_threshold' => $v->low_stock_threshold, 'is_active' => (bool) $v->is_active,
    ])->values() : collect([[
        'name' => '', 'sku' => null, 'barcode' => null, 'price' => '', 'cost_price' => null, 'compare_at_price' => null,
        'currency' => config('platform.base_currency', 'XAF'), 'data_amount' => null, 'validity_days' => null,
        'denomination' => null, 'stock' => null, 'low_stock_threshold' => null, 'is_active' => true,
    ]]);
@endphp

@section('content')
<div class="mx-auto max-w-4xl" x-data="{ variants: {{ \Illuminate\Support\Js::from($existingVariants) }} }">
    <a href="{{ route('admin.shop.products.index') }}" class="text-sm text-brand-400 hover:text-brand-600">← Products</a>
    <x-glass-card class="mt-4">
        <form method="POST" action="{{ $product->exists ? route('admin.shop.products.update', $product) : route('admin.shop.products.store') }}" class="space-y-4">
            @csrf @if($product->exists)@method('PUT')@endif

            @if ($product->exists && $product->isImported())
                <p class="rounded-lg bg-sky-500/10 px-3 py-2 text-xs text-sky-700">Imported from <strong>{{ $product->source }}</strong>@if($product->last_synced_at) · last synced {{ $product->last_synced_at->diffForHumans() }}@endif. Fields you edit here are not automatically overwritten by future syncs.</p>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label">Name</label><input name="name" value="{{ old('name', $product->name) }}" required class="field"></div>
                <div><label class="label">Category</label><select name="shop_category_id" class="field" required>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('shop_category_id', $product->shop_category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                <div><label class="label">Product type</label>
                    <select name="type" class="field">
                        @foreach (\App\Enums\ShopProductType::cases() as $t)<option value="{{ $t->value }}" @selected(old('type', $product->type?->value ?? 'giftcard')===$t->value)>{{ $t->label() }}</option>@endforeach
                    </select>
                </div>
                <div><label class="label">Brand</label><input name="brand" value="{{ old('brand', $product->brand) }}" class="field"></div>
                <div><label class="label">Region / country</label><input name="region" value="{{ old('region', $product->region) }}" class="field" placeholder="For eSIM / data products"></div>
                <div class="sm:col-span-2"><label class="label">Summary</label><input name="summary" value="{{ old('summary', $product->summary) }}" class="field"></div>
                <div class="sm:col-span-2"><label class="label">Description</label><textarea name="description" rows="3" class="field">{{ old('description', $product->description) }}</textarea></div>
                <div class="sm:col-span-2"><label class="label">Redeem / activation instructions</label><textarea name="redeem_instructions" rows="2" class="field">{{ old('redeem_instructions', $product->redeem_instructions) }}</textarea></div>
                <div class="sm:col-span-2"><label class="label">Internal notes</label><textarea name="admin_notes" rows="2" class="field" placeholder="Never shown to customers">{{ old('admin_notes', $product->admin_notes) }}</textarea></div>
            </div>

            <div class="flex flex-wrap items-center gap-5 border-t border-app pt-4">
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="rounded"> Active</label>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false)) class="rounded"> Featured</label>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_best_deal" value="1" @checked(old('is_best_deal', $product->is_best_deal ?? false)) class="rounded"> Best deal</label>
                <div class="flex items-center gap-2">
                    <label class="label mb-0 whitespace-nowrap" for="sort">Popularity rank</label>
                    <input id="sort" name="sort" type="number" value="{{ old('sort', $product->sort ?? 0) }}" class="field !w-24">
                </div>
            </div>
            <p class="-mt-2 text-xs text-faint">Unchecking "Active" saves this product as a draft — it won't appear on the storefront until activated.</p>

            {{-- Variants --}}
            <div class="border-t border-app pt-4">
                <div class="mb-2 flex items-center justify-between">
                    <label class="label mb-0">Variants / plans</label>
                    <button type="button" @click="variants.push({name:'',sku:null,barcode:null,price:'',cost_price:null,compare_at_price:null,currency:'{{ config('platform.base_currency', 'XAF') }}',data_amount:null,validity_days:null,denomination:null,stock:null,low_stock_threshold:null,is_active:true})" class="text-xs text-brand-400">+ Add variant</button>
                </div>
                <template x-for="(v, i) in variants" :key="i">
                    <div class="mb-3 rounded-xl surface-2 p-3">
                        <input :name="`variants[${i}][id]`" :value="v.id" type="hidden">
                        <div class="grid grid-cols-12 gap-2">
                            <input :name="`variants[${i}][name]`" x-model="v.name" placeholder="e.g. 5GB / 30 days" class="field col-span-4">
                            <input :name="`variants[${i}][sku]`" x-model="v.sku" placeholder="SKU" class="field col-span-3">
                            <input :name="`variants[${i}][barcode]`" x-model="v.barcode" placeholder="Barcode" class="field col-span-3">
                            <button type="button" @click="variants.splice(i,1)" class="col-span-2 rounded-lg text-rose-400 hover:surface">Remove</button>
                        </div>
                        <div class="mt-2 grid grid-cols-12 gap-2">
                            <input :name="`variants[${i}][cost_price]`" x-model="v.cost_price" type="number" step="0.01" placeholder="Cost price" class="field col-span-2">
                            <input :name="`variants[${i}][price]`" x-model="v.price" type="number" step="0.01" placeholder="Price" required class="field col-span-2">
                            <input :name="`variants[${i}][compare_at_price]`" x-model="v.compare_at_price" type="number" step="0.01" placeholder="Compare-at" class="field col-span-2">
                            <input :name="`variants[${i}][currency]`" x-model="v.currency" placeholder="XAF" class="field col-span-1 uppercase">
                            <input :name="`variants[${i}][stock]`" x-model="v.stock" type="number" placeholder="Stock (blank=unlimited)" class="field col-span-3">
                            <input :name="`variants[${i}][low_stock_threshold]`" x-model="v.low_stock_threshold" type="number" placeholder="Low-stock at" class="field col-span-2">
                        </div>
                        <div class="mt-2 grid grid-cols-12 gap-2">
                            <input :name="`variants[${i}][data_amount]`" x-model="v.data_amount" placeholder="Data amount (eSIM/data)" class="field col-span-4">
                            <input :name="`variants[${i}][validity_days]`" x-model="v.validity_days" type="number" placeholder="Validity (days)" class="field col-span-3">
                            <input :name="`variants[${i}][denomination]`" x-model="v.denomination" type="number" step="0.01" placeholder="Gift-card face value" class="field col-span-3">
                            <label class="col-span-2 flex items-center gap-1.5 text-xs text-body"><input type="checkbox" :name="`variants[${i}][is_active]`" value="1" x-model="v.is_active" class="rounded"> Active</label>
                        </div>
                    </div>
                </template>
                <p class="text-xs text-faint">Leave stock blank for unlimited (codes auto-generated on delivery). Cost price drives the profit margin shown in the products table.</p>
            </div>

            <button class="btn btn-primary">{{ $product->exists ? 'Update' : 'Create' }} product</button>
        </form>
    </x-glass-card>
</div>
@endsection
