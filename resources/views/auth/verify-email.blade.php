@extends('layouts.auth')
@section('title', 'Verify email')
@section('heading', 'Verify your email')
@section('sub', 'We sent a verification link to your inbox')

@section('content')
<p class="text-sm text-body">
    Before continuing, please check your email for a verification link. In local/sandbox mode, links are written to <code class="rounded surface-2 px-1.5 py-0.5 text-xs">storage/logs/laravel.log</code>.
</p>
<form method="POST" action="{{ route('verification.send') }}" class="mt-5">
    @csrf
    <button type="submit" class="btn btn-primary w-full">{{ __('Resend verification email') }}</button>
</form>
<form method="POST" action="{{ route('logout') }}" class="mt-3">
    @csrf
    <button type="submit" class="btn btn-ghost w-full">{{ __('Log out') }}</button>
</form>
@endsection
