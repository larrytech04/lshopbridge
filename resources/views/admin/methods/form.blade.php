@extends('layouts.admin')
@section('page-title', $method->exists ? 'Edit method' : 'New method')

@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.methods.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← Methods</a>
    <x-glass-card class="mt-4">
        <form method="POST" action="{{ $method->exists ? route('admin.methods.update', $method) : route('admin.methods.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf @if($method->exists)@method('PUT')@endif
            <div><label class="label">Code</label><input name="code" value="{{ old('code', $method->code) }}" required class="field"></div>
            <div><label class="label">Name</label><input name="name" value="{{ old('name', $method->name) }}" required class="field"></div>
            <div><label class="label">Type</label><select name="type" class="field">@foreach (['momo','bank','crypto','card'] as $t)<option value="{{ $t }}" @selected(($method->type ?? '')===$t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
            <div><label class="label">Provider code (for automation)</label>
                <select name="provider_code" class="field"><option value="">None (manual), </option>@foreach (array_keys(config('payments.providers')) as $code)<option value="{{ $code }}" @selected(($method->provider_code ?? '')===$code)>{{ $code }}</option>@endforeach</select>
            </div>
            <div class="sm:col-span-2"><label class="label">Description</label><input name="description" value="{{ old('description', $method->description) }}" class="field"></div>
            <div class="sm:col-span-2"><label class="label">Manual instructions</label><textarea name="instructions" rows="2" class="field">{{ old('instructions', $method->instructions) }}</textarea></div>
            <div><label class="label">Min amount</label><input name="min_amount" type="number" step="0.01" value="{{ old('min_amount', $method->min_amount ?? 0) }}" class="field"></div>
            <div><label class="label">Max amount</label><input name="max_amount" type="number" step="0.01" value="{{ old('max_amount', $method->max_amount) }}" class="field"></div>
            <div><label class="label">Currency</label><input name="currency" value="{{ old('currency', $method->currency ?? config('platform.base_currency')) }}" maxlength="3" class="field uppercase"></div>
            <div><label class="label">Sort</label><input name="sort" type="number" value="{{ old('sort', $method->sort ?? 0) }}" class="field"></div>
            <div class="sm:col-span-2 flex flex-wrap gap-5">
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_automated" value="1" @checked(old('is_automated', $method->is_automated ?? false)) class="rounded border-app surface-2 text-brand-500"> Automated (API + webhook)</label>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="requires_proof" value="1" @checked(old('requires_proof', $method->requires_proof ?? true)) class="rounded border-app surface-2 text-brand-500"> Requires proof (manual)</label>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $method->is_active ?? true)) class="rounded border-app surface-2 text-brand-500"> Active</label>
            </div>
            <div class="sm:col-span-2"><button class="btn btn-primary">{{ $method->exists ? 'Update' : 'Create' }} method</button></div>
        </form>
    </x-glass-card>
</div>
@endsection
