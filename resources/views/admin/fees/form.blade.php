@extends('layouts.admin')
@section('page-title', $fee->exists ? 'Edit fee' : 'New fee')

@section('content')
<div class="mx-auto max-w-xl">
    <a href="{{ route('admin.fees.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← Fees</a>
    <x-glass-card class="mt-4">
        <form method="POST" action="{{ $fee->exists ? route('admin.fees.update', $fee) : route('admin.fees.store') }}" class="space-y-4">
            @csrf @if($fee->exists)@method('PUT')@endif
            <div><label class="label">Name</label><input name="name" value="{{ old('name', $fee->name) }}" required class="field"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="label">Applies to</label><select name="applies_to" class="field"><option value="funding" @selected(($fee->applies_to ?? 'funding')==='funding')>Funding</option><option value="deposit" @selected(($fee->applies_to ?? '')==='deposit')>Deposit</option><option value="all" @selected(($fee->applies_to ?? '')==='all')>All</option></select></div>
                <div><label class="label">Type</label><select name="type" class="field"><option value="percent" @selected(($fee->type ?? 'percent')==='percent')>Percent</option><option value="fixed" @selected(($fee->type ?? '')==='fixed')>Fixed</option></select></div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="label">Value</label><input name="value" type="number" step="0.0001" value="{{ old('value', $fee->value) }}" required class="field"></div>
                <div><label class="label">Min fee</label><input name="min_fee" type="number" step="0.01" value="{{ old('min_fee', $fee->min_fee ?? 0) }}" class="field"></div>
                <div><label class="label">Max fee</label><input name="max_fee" type="number" step="0.01" value="{{ old('max_fee', $fee->max_fee) }}" class="field"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="label">Scope (method/app code, optional)</label><input name="scope" value="{{ old('scope', $fee->scope) }}" class="field"></div>
                <div><label class="label">Currency</label><input name="currency" value="{{ old('currency', $fee->currency ?? config('platform.base_currency')) }}" maxlength="3" class="field uppercase"></div>
            </div>
            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $fee->is_active ?? true)) class="rounded border-app surface-2 text-brand-500"> Active</label>
            <button class="btn btn-primary">{{ $fee->exists ? 'Update' : 'Create' }} fee</button>
        </form>
    </x-glass-card>
</div>
@endsection
