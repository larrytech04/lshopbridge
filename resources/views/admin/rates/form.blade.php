@extends('layouts.admin')
@section('page-title', $rate->exists ? 'Edit rate' : 'New rate')

@section('content')
<div class="mx-auto max-w-xl">
    <a href="{{ route('admin.rates.index') }}" class="text-sm text-brand-600 hover:text-brand-700">← Rates</a>
    <x-glass-card class="mt-4">
        <form method="POST" action="{{ $rate->exists ? route('admin.rates.update', $rate) : route('admin.rates.store') }}" class="space-y-4">
            @csrf @if($rate->exists)@method('PUT')@endif
            <div class="grid grid-cols-2 gap-3">
                <div><label class="label">Base currency</label><input name="base_currency" value="{{ old('base_currency', $rate->base_currency ?? config('platform.base_currency')) }}" maxlength="3" required class="field uppercase"></div>
                <div><label class="label">Quote currency</label><input name="quote_currency" value="{{ old('quote_currency', $rate->quote_currency ?? config('platform.target_currency')) }}" maxlength="3" required class="field uppercase"></div>
            </div>
            <div><label class="label">Rate (1 base = ? quote)</label><input name="rate" type="number" step="0.00000001" value="{{ old('rate', $rate->rate) }}" required class="field"></div>
            <div><label class="label">Margin / spread (%)</label><input name="margin_percent" type="number" step="0.01" value="{{ old('margin_percent', $rate->margin_percent ?? 0) }}" class="field"></div>
            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rate->is_active ?? true)) class="rounded border-app surface-2 text-brand-500"> Active</label>
            <button class="btn btn-primary">{{ $rate->exists ? 'Update' : 'Create' }} rate</button>
        </form>
    </x-glass-card>
</div>
@endsection
