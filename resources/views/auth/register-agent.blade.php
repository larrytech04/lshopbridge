@extends('layouts.auth')
@section('title', 'Become an agent')
@section('heading', 'Register as a shipping agent')
@section('sub', 'List your procurement & shipping service to thousands of buyers')

@section('content')
<form method="POST" action="{{ route('register.agent') }}" class="space-y-4">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="label" for="name">{{ __('Your name') }}</label>
            <input id="name" name="name" value="{{ old('name') }}" required class="field">
        </div>
        <div>
            <label class="label" for="business_name">{{ __('Business name') }}</label>
            <input id="business_name" name="business_name" value="{{ old('business_name') }}" required class="field">
        </div>
    </div>
    <div>
        <label class="label" for="email">{{ __('Email') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="field">
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="label" for="country_id">{{ __('Country') }}</label>
            <select id="country_id" name="country_id" required class="field">
                <option value="">{{ __('Select…') }}</option>
                @foreach ($countries as $c)
                    <option value="{{ $c->id }}" @selected(old('country_id') == $c->id)>{{ $c->flag_emoji }} {{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="phone">{{ __('Phone') }}</label>
            <input id="phone" name="phone" value="{{ old('phone') }}" required class="field">
        </div>
    </div>
    <div>
        <label class="label" for="warehouse_city">{{ __('Warehouse city (China)') }}</label>
        <input id="warehouse_city" name="warehouse_city" value="{{ old('warehouse_city') }}" class="field" placeholder="{{ __('Guangzhou') }}">
    </div>
    <div>
        <label class="label" for="bio">{{ __('Short description') }}</label>
        <textarea id="bio" name="bio" rows="2" class="field" placeholder="{{ __('What do you ship and from where?') }}">{{ old('bio') }}</textarea>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="label" for="password">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required class="field">
        </div>
        <div>
            <label class="label" for="password_confirmation">{{ __('Confirm') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="field">
        </div>
    </div>
    <label class="flex items-start gap-2 text-sm text-body">
        <input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-app surface-2 text-brand-500">
        <span>{{ __('I agree to the') }} <a href="{{ route('pages.show', 'terms') }}" class="text-brand-300">{{ __('Terms') }}</a>.</span>
    </label>
    <button type="submit" class="btn btn-primary w-full">{{ __('Create agent account') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
</form>

<p class="mt-6 text-center text-sm text-muted">
    {{ __('Not an agent?') }} <a href="{{ route('register') }}" class="font-semibold text-brand-300 hover:text-brand-200">{{ __('Create a user account') }}</a>
</p>
@endsection
