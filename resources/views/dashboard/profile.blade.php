@extends('layouts.app')
@section('page-title', 'Profile & security')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <x-glass-card>
        <h3 class="font-semibold text-strong">{{ __('Profile') }}</h3>
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
            @csrf @method('PUT')
            <div class="sm:col-span-2"><label class="label">{{ __('Full name') }}</label><input name="name" value="{{ old('name', $user->name) }}" required class="field"></div>
            <div><label class="label">{{ __('Phone') }}</label><input name="phone" value="{{ old('phone', $user->phone) }}" required class="field"></div>
            <div><label class="label">{{ __('Country') }}</label>
                <select name="country_id" required class="field">
                    @foreach ($countries as $c)<option value="{{ $c->id }}" @selected($user->country_id == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="label">{{ __('City') }}</label><input name="city" value="{{ old('city', $user->city) }}" class="field"></div>
            <div><label class="label">{{ __('Address') }}</label><input name="address" value="{{ old('address', $user->address) }}" class="field"></div>
            <div class="sm:col-span-2"><label class="label">{{ __('Avatar') }}</label><input type="file" name="avatar" accept="image/*" class="field"></div>
            <div class="sm:col-span-2"><button class="btn btn-primary">{{ __('Save profile') }}</button></div>
        </form>
    </x-glass-card>

    <x-glass-card>
        <h3 class="font-semibold text-strong">{{ __('Change password') }}</h3>
        <form method="POST" action="{{ route('profile.password') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
            @csrf @method('PUT')
            <div class="sm:col-span-2"><label class="label">{{ __('Current password') }}</label><input type="password" name="current_password" required class="field"></div>
            <div><label class="label">{{ __('New password') }}</label><input type="password" name="password" required class="field"></div>
            <div><label class="label">{{ __('Confirm') }}</label><input type="password" name="password_confirmation" required class="field"></div>
            <div class="sm:col-span-2"><button class="btn btn-primary">{{ __('Update password') }}</button></div>
        </form>
    </x-glass-card>
</div>
@endsection
