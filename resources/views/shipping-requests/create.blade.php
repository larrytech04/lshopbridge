@extends('layouts.app')
@section('page-title', __('New Shipping Request'))

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-header :title="__('New Shipping Request')" :subtitle="__('Describe your shipment — verified agents will send you competing quotes.')" />

    <form method="POST" action="{{ route('shipping-requests.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Origin') }}</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">{{ __('Country') }}</label>
                    <select name="origin_country" required class="field">
                        @foreach ($countries as $c)<option value="{{ $c->iso2 }}">{{ $c->flag_emoji }} {{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="label">{{ __('City') }}</label><input name="origin_city" required class="field"></div>
                <div class="sm:col-span-2"><label class="label">{{ __('Pickup address (optional)') }}</label><textarea name="origin_address" rows="2" class="field"></textarea></div>
            </div>
        </x-glass-card>

        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Destination') }}</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">{{ __('Country') }}</label>
                    <select name="destination_country" required class="field">
                        @foreach ($countries as $c)<option value="{{ $c->iso2 }}">{{ $c->flag_emoji }} {{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="label">{{ __('City') }}</label><input name="destination_city" required class="field"></div>
                <div class="sm:col-span-2"><label class="label">{{ __('Delivery address (optional)') }}</label><textarea name="destination_address" rows="2" class="field"></textarea></div>
            </div>
        </x-glass-card>

        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Package') }}</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-3"><label class="label">{{ __('Description') }}</label><input name="package_description" required class="field" placeholder="{{ __('e.g. 3 boxes of clothing, 25kg total') }}"></div>
                <div><label class="label">{{ __('Weight (kg)') }}</label><input type="number" step="0.01" min="0" name="package_weight_kg" class="field"></div>
                <div><label class="label">{{ __('Declared value') }}</label><input type="number" step="0.01" min="0" name="package_value" class="field"></div>
                <div>
                    <label class="label">{{ __('Currency') }}</label>
                    <input name="package_currency" value="{{ config('platform.base_currency', 'XAF') }}" maxlength="3" class="field uppercase">
                </div>
                <div class="sm:col-span-3"><label class="label">{{ __('Documents (invoices, photos — optional)') }}</label><input type="file" name="documents[]" multiple accept=".jpg,.jpeg,.png,.pdf" class="field"></div>
                <div class="sm:col-span-3"><label class="label">{{ __('Notes for agents (optional)') }}</label><textarea name="notes" rows="3" class="field"></textarea></div>
            </div>
        </x-glass-card>

        <button type="submit" class="btn btn-primary w-full">{{ __('Submit Request') }}</button>
    </form>
</div>
@endsection
