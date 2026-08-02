@extends('layouts.public')
@section('title', 'Get support · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-3xl px-4 pt-20 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
        <h1 class="text-4xl font-extrabold text-strong sm:text-5xl">{{ __('Get support') }}</h1>
        <p class="mt-4 text-lg text-body">{{ __("Don't have an account yet? Tell us what's going on and our team will help.") }}</p>
    </div>

    <div class="glass mt-10 rounded-3xl p-8">
        <x-flash />
        <form method="POST" action="{{ route('support.guest.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <x-honeypot />
            <x-form-timing form-type="guest_support" />
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">{{ __('Name') }}</label><input name="name" value="{{ old('name') }}" required class="field"></div>
                <div><label class="label">{{ __('Email') }}</label><input name="email" type="email" value="{{ old('email') }}" required class="field"></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">{{ __('Phone (optional)') }}</label><input name="phone" value="{{ old('phone') }}" class="field"></div>
                <div>
                    <label class="label">{{ __('Category') }}</label>
                    <select name="category" class="field">
                        @foreach (['general' => 'General', 'deposit' => 'Deposit', 'funding' => 'Funding', 'agent' => 'Agent / shipping'] as $v => $lbl)
                            <option value="{{ $v }}" @selected(old('category') === $v)>{{ __($lbl) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div><label class="label">{{ __('Subject') }}</label><input name="subject" value="{{ old('subject', request('subject')) }}" required class="field"></div>
            <div><label class="label">{{ __('Describe your issue') }}</label><textarea name="description" rows="6" required class="field">{{ old('description') }}</textarea></div>
            <div>
                <label class="label">{{ __('Attachment (optional)') }}</label>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="field !py-2 text-sm">
                <p class="mt-1 text-[11px] text-faint">{{ __('JPG, PNG, or PDF, up to 5MB.') }}</p>
            </div>
            <x-turnstile action="guest_support" />
            <button class="btn btn-primary w-full">{{ __('Submit request') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-muted">
        {{ __('Already have an account?') }} <a href="{{ route('login') }}" class="font-semibold text-brand-400 hover:text-brand-300">{{ __('Log in for faster support') }}</a>
    </p>
</section>
@endsection
