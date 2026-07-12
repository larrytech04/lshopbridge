@extends('layouts.app')
@section('page-title', 'Shipping rates')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-3">
        @forelse ($rates as $rate)
            <div class="glass rounded-2xl p-5" x-data="{ editing: false }">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-strong">{{ ucfirst($rate->method) }} → {{ $rate->destinationCountry?->name ?? 'Various' }}</p>
                        <p class="text-sm text-muted">
                            @if($rate->price_per_kg){{ money($rate->price_per_kg, $rate->currency) }}/kg @endif
                            @if($rate->price_per_cbm)· {{ money($rate->price_per_cbm, $rate->currency) }}/cbm @endif
                            · {{ $rate->estimated_days_min }}–{{ $rate->estimated_days_max }} {{ __('days') }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        @if (!$rate->is_active)<span class="pill bg-slate-400/15 text-body">{{ __('Inactive') }}</span>@endif
                        <button type="button" @click="editing = !editing" class="text-sm font-semibold text-brand-500 hover:text-brand-600" x-text="editing ? '{{ __('Close') }}' : '{{ __('Edit') }}'">{{ __('Edit') }}</button>
                        <form method="POST" action="{{ route('agent.rates.destroy', $rate) }}" onsubmit="return confirm('{{ __('Delete this rate?') }}')">@csrf @method('DELETE')<button class="text-rose-400 hover:text-rose-300"><x-icon name="x" class="h-4 w-4" /></button></form>
                    </div>
                </div>
                @if ($rate->notes)<p class="mt-2 text-sm text-muted" x-show="!editing">{{ $rate->notes }}</p>@endif

                {{-- Inline edit form --}}
                <form x-show="editing" x-collapse method="POST" action="{{ route('agent.rates.update', $rate) }}" class="mt-4 space-y-3 border-t border-app pt-4" style="display:none">
                    @csrf @method('PUT')
                    <div><label class="label">{{ __('Method') }}</label>
                        <select name="method" required class="field">@foreach (['air' => __('Air'), 'sea' => __('Sea'), 'express' => __('Express')] as $mk => $mv)<option value="{{ $mk }}" @selected($rate->method === $mk)>{{ $mv }}</option>@endforeach</select>
                    </div>
                    <div><label class="label">{{ __('Destination country') }}</label>
                        <select name="destination_country_id" class="field"><option value="">{{ __('Various') }}</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected($rate->destination_country_id == $c->id)>{{ $c->name }}</option>@endforeach</select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="label">{{ __('Price/kg') }}</label><input type="number" step="0.01" name="price_per_kg" value="{{ $rate->price_per_kg }}" class="field"></div>
                        <div><label class="label">{{ __('Price/cbm') }}</label><input type="number" step="0.01" name="price_per_cbm" value="{{ $rate->price_per_cbm }}" class="field"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="label">{{ __('Currency') }}</label><input name="currency" value="{{ $rate->currency }}" maxlength="3" required class="field"></div>
                        <div><label class="label">{{ __('Min charge') }}</label><input type="number" step="0.01" name="min_charge" value="{{ $rate->min_charge }}" class="field"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="label">{{ __('Days min') }}</label><input type="number" name="estimated_days_min" value="{{ $rate->estimated_days_min }}" class="field"></div>
                        <div><label class="label">{{ __('Days max') }}</label><input type="number" name="estimated_days_max" value="{{ $rate->estimated_days_max }}" class="field"></div>
                    </div>
                    <div><label class="label">{{ __('Notes') }}</label><textarea name="notes" rows="2" class="field">{{ $rate->notes }}</textarea></div>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($rate->is_active) class="rounded border-app surface-2 text-brand-500"> {{ __('Active') }}</label>
                    <div class="flex gap-2">
                        <button class="btn btn-primary">{{ __('Save changes') }}</button>
                        <button type="button" @click="editing = false" class="btn btn-ghost">{{ __('Cancel') }}</button>
                    </div>
                </form>
            </div>
        @empty
            <x-empty icon="truck" title="{{ __('No shipping rates yet') }}" message="Add your first rate so buyers can compare." />
        @endforelse
    </div>

    <div>
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Add a rate') }}</h3>
            <form method="POST" action="{{ route('agent.rates.store') }}" class="mt-4 space-y-3">
                @csrf
                <div><label class="label">{{ __('Method') }}</label><select name="method" required class="field"><option value="air">{{ __('Air') }}</option><option value="sea">{{ __('Sea') }}</option><option value="express">{{ __('Express') }}</option></select></div>
                <div><label class="label">{{ __('Destination country') }}</label>
                    <select name="destination_country_id" class="field"><option value="">{{ __('Various') }}</option>@foreach ($countries as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="label">{{ __('Price/kg') }}</label><input type="number" step="0.01" name="price_per_kg" class="field"></div>
                    <div><label class="label">{{ __('Price/cbm') }}</label><input type="number" step="0.01" name="price_per_cbm" class="field"></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="label">{{ __('Currency') }}</label><input name="currency" value="USD" maxlength="3" required class="field"></div>
                    <div><label class="label">{{ __('Min charge') }}</label><input type="number" step="0.01" name="min_charge" class="field"></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="label">{{ __('Days min') }}</label><input type="number" name="estimated_days_min" class="field"></div>
                    <div><label class="label">{{ __('Days max') }}</label><input type="number" name="estimated_days_max" class="field"></div>
                </div>
                <div><label class="label">{{ __('Notes') }}</label><textarea name="notes" rows="2" class="field"></textarea></div>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded border-app surface-2 text-brand-500"> {{ __('Active') }}</label>
                <button class="btn btn-primary w-full"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add rate') }}</button>
            </form>
        </x-glass-card>
    </div>
</div>
@endsection
