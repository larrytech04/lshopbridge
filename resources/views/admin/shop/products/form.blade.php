@extends('layouts.admin')
@section('page-title', $product->exists ? 'Edit product' : 'New product')

@php
    $existingVariants = $product->exists ? $product->variants->map(fn($v) => [
        'id'=>$v->id,'name'=>$v->name,'price'=>(float)$v->price,'compare_at_price'=>$v->compare_at_price ? (float)$v->compare_at_price : null,
        'data_amount'=>$v->data_amount,'validity_days'=>$v->validity_days,'stock'=>$v->stock,'is_active'=>(bool)$v->is_active,
    ])->values() : collect([['name'=>'','price'=>'','compare_at_price'=>null,'data_amount'=>null,'validity_days'=>null,'stock'=>null,'is_active'=>true]]);
@endphp

@section('content')
<div class="mx-auto max-w-3xl" x-data="{ variants: {{ \Illuminate\Support\Js::from($existingVariants) }} }">
    <a href="{{ route('admin.shop.products.index') }}" class="text-sm text-brand-400 hover:text-brand-300">← Products</a>
    <x-glass-card class="mt-4">
        <form method="POST" action="{{ $product->exists ? route('admin.shop.products.update', $product) : route('admin.shop.products.store') }}" class="space-y-4">
            @csrf @if($product->exists)@method('PUT')@endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label">Name</label><input name="name" value="{{ old('name', $product->name) }}" required class="field"></div>
                <div><label class="label">Category</label><select name="shop_category_id" class="field">@foreach ($categories as $c)<option value="{{ $c->id }}" @selected($product->shop_category_id == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                <div><label class="label">Type</label><select name="type" class="field">@foreach (['giftcard','esim','vpn','gaming','streaming','software','other'] as $t)<option value="{{ $t }}" @selected(($product->type ?? '')===$t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
                <div><label class="label">Brand</label><input name="brand" value="{{ old('brand', $product->brand) }}" class="field"></div>
                <div><label class="label">Region</label><input name="region" value="{{ old('region', $product->region) }}" class="field"></div>
                <div class="sm:col-span-2"><label class="label">Summary</label><input name="summary" value="{{ old('summary', $product->summary) }}" class="field"></div>
                <div class="sm:col-span-2"><label class="label">Description</label><textarea name="description" rows="3" class="field">{{ old('description', $product->description) }}</textarea></div>
                <div class="sm:col-span-2"><label class="label">Redeem instructions</label><textarea name="redeem_instructions" rows="2" class="field">{{ old('redeem_instructions', $product->redeem_instructions) }}</textarea></div>
            </div>

            <div class="flex flex-wrap gap-5">
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="rounded"> Active</label>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false)) class="rounded"> Featured</label>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_best_deal" value="1" @checked(old('is_best_deal', $product->is_best_deal ?? false)) class="rounded"> Best deal</label>
            </div>

            {{-- Variants --}}
            <div class="border-t border-app pt-4">
                <div class="mb-2 flex items-center justify-between"><label class="label mb-0">Variants / plans</label><button type="button" @click="variants.push({name:'',price:'',compare_at_price:null,data_amount:null,validity_days:null,stock:null,is_active:true})" class="text-xs text-brand-400">+ Add variant</button></div>
                <template x-for="(v, i) in variants" :key="i">
                    <div class="mb-2 grid grid-cols-12 gap-2">
                        <input :name="`variants[${i}][id]`" :value="v.id" type="hidden">
                        <input :name="`variants[${i}][name]`" x-model="v.name" placeholder="e.g. $25 / 5GB·30d" class="field col-span-4">
                        <input :name="`variants[${i}][price]`" x-model="v.price" type="number" step="0.01" placeholder="Price" class="field col-span-3">
                        <input :name="`variants[${i}][compare_at_price]`" x-model="v.compare_at_price" type="number" step="0.01" placeholder="Was" class="field col-span-2">
                        <input :name="`variants[${i}][stock]`" x-model="v.stock" type="number" placeholder="Stock" class="field col-span-2">
                        <button type="button" @click="variants.splice(i,1)" class="col-span-1 text-rose-400">✕</button>
                    </div>
                </template>
                <p class="text-xs text-faint">Leave stock blank for unlimited (codes auto-generated on delivery).</p>
            </div>

            <button class="btn btn-primary">{{ $product->exists ? 'Update' : 'Create' }} product</button>
        </form>
    </x-glass-card>
</div>
@endsection
