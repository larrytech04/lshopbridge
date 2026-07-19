@extends('layouts.app')
@section('page-title', 'Business profile')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-glass-card>
        <form method="POST" action="{{ route('agent.profile.update') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
            @csrf @method('PUT')
            <div class="sm:col-span-2"><label class="label">{{ __('Business name') }}</label><input name="business_name" value="{{ old('business_name', $agent->business_name) }}" required class="field"></div>
            <div class="sm:col-span-2"><label class="label">{{ __('About') }}</label><textarea name="bio" rows="3" class="field">{{ old('bio', $agent->bio) }}</textarea></div>
            <div><label class="label">{{ __('Phone') }}</label><input name="phone" value="{{ old('phone', $agent->phone) }}" class="field"></div>
            <div><label class="label">{{ __('WhatsApp') }}</label><input name="whatsapp" value="{{ old('whatsapp', $agent->whatsapp) }}" class="field"></div>
            <div><label class="label">{{ __('WeChat') }}</label><input name="wechat" value="{{ old('wechat', $agent->wechat) }}" class="field"></div>
            <div><label class="label">{{ __('Warehouse country') }}</label>
                <select name="warehouse_country_id" class="field">
                    <option value="">, </option>
                    @foreach ($countries as $c)<option value="{{ $c->id }}" @selected($agent->warehouse_country_id == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="label">{{ __('Warehouse city') }}</label><input name="warehouse_city" value="{{ old('warehouse_city', $agent->warehouse_city) }}" class="field"></div>
            <div class="sm:col-span-2"><label class="label">{{ __('Warehouse address') }}</label><input name="warehouse_address" value="{{ old('warehouse_address', $agent->warehouse_address) }}" class="field"></div>
            <div class="sm:col-span-2"><label class="label">{{ __('Served cities (comma separated)') }}</label><input name="cities" value="{{ collect($agent->cities ?? [])->implode(', ') }}" class="field" placeholder="{{ __('Douala, Lagos, Accra') }}"></div>
            <div class="sm:col-span-2">
                <label class="label">{{ __('Shipping methods') }}</label>
                <div class="flex flex-wrap gap-4">
                    @foreach ($allMethods as $k => $v)
                        <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="shipping_methods[]" value="{{ $k }}" @checked(in_array($k, $agent->shipping_methods ?? [])) class="rounded border-app surface-2 text-brand-500"> {{ $v }}</label>
                    @endforeach
                </div>
            </div>
            <div class="sm:col-span-2">
                <label class="label">{{ __('Countries served') }}</label>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($countries as $c)
                        <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="countries[]" value="{{ $c->id }}" @checked($agent->countries->contains($c->id)) class="rounded border-app surface-2 text-brand-500"> {{ $c->name }}</label>
                    @endforeach
                </div>
            </div>
            <div class="sm:col-span-2"><label class="label">{{ __('Logo') }}</label><input type="file" name="logo" accept="image/*" class="field"></div>
            <div class="sm:col-span-2"><button class="btn btn-primary">{{ __('Save profile') }}</button></div>
        </form>
    </x-glass-card>
</div>
@endsection
