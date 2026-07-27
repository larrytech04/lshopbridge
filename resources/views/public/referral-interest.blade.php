@extends('layouts.public')
@section('title', 'Become an agent · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-3xl px-4 pt-20 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
        <h1 class="text-4xl font-extrabold text-strong sm:text-5xl">{{ __('Interested in becoming an agent?') }}</h1>
        <p class="mt-4 text-lg text-body">{{ __('Leave your details and our team will reach out with next steps — no account needed yet.') }}</p>
    </div>

    <div class="glass mt-10 rounded-3xl p-8">
        <x-flash />
        <form method="POST" action="{{ route('referral.store') }}" class="space-y-4">
            @csrf
            <x-honeypot />
            <x-form-timing form-type="referral" />
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">{{ __('Name') }}</label><input name="name" value="{{ old('name') }}" required class="field"></div>
                <div><label class="label">{{ __('Email') }}</label><input name="email" type="email" value="{{ old('email') }}" required class="field"></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">{{ __('Phone (optional)') }}</label><input name="phone" value="{{ old('phone') }}" class="field"></div>
                <div>
                    <label class="label">{{ __('Country') }}</label>
                    <select name="country_id" class="field">
                        <option value="">{{ __('Select country') }}</option>
                        @foreach ($countries as $c)
                            <option value="{{ $c->id }}" @selected((string) old('country_id') === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div><label class="label">{{ __('Tell us a bit about yourself (optional)') }}</label><textarea name="message" rows="4" class="field">{{ old('message') }}</textarea></div>
            <x-turnstile action="referral" />
            <button class="btn btn-primary w-full">{{ __('Submit interest') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-muted">
        {{ __('Ready to go now?') }} <a href="{{ route('register.agent') }}" class="font-semibold text-brand-400 hover:text-brand-300">{{ __('Create your agent account directly') }}</a>
    </p>
</section>
@endsection
