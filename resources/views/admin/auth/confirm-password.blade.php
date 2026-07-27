@extends('layouts.auth')
@section('title', 'Confirm password')
@section('heading', 'Confirm your password')
@section('sub', 'This is a sensitive action, please confirm your password to continue.')

@section('content')
<form method="POST" action="{{ route('admin.password.confirm') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="password">{{ __('Password') }}</label>
        <input id="password" name="password" type="password" required autofocus class="field" placeholder="••••••••">
    </div>
    <button type="submit" class="btn btn-primary w-full">{{ __('Confirm') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
</form>
@endsection
