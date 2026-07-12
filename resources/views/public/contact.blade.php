@extends('layouts.public')
@section('title', 'Contact us · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-2xl px-4 pt-20 sm:px-6">
    <div class="text-center">
        <h1 class="text-4xl font-extrabold text-strong sm:text-5xl">{{ __('Get in touch') }}</h1>
        <p class="mt-4 text-lg text-body">{{ __('Questions, partnerships or support — we’re here to help.') }}</p>
    </div>

    <div class="glass mt-10 rounded-3xl p-8">
        <x-flash />
        <form method="POST" action="{{ route('contact.submit') }}" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">{{ __('Name') }}</label><input name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required class="field"></div>
                <div><label class="label">{{ __('Email') }}</label><input name="email" type="email" value="{{ old('email', auth()->user()->email ?? '') }}" required class="field"></div>
            </div>
            <div><label class="label">{{ __('Subject') }}</label><input name="subject" value="{{ old('subject') }}" required class="field"></div>
            <div><label class="label">{{ __('Message') }}</label><textarea name="message" rows="5" required class="field">{{ old('message') }}</textarea></div>
            <button class="btn btn-primary w-full">{{ __('Send message') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
        </form>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        <div class="glass flex items-center gap-3 rounded-2xl p-5">
            <x-icon name="mail" class="h-6 w-6 text-brand-200" />
            <div><p class="text-xs text-muted">{{ __('Email') }}</p><p class="font-medium text-strong">{{ setting('support_email', config('platform.support_email')) }}</p></div>
        </div>
        <div class="glass flex items-center gap-3 rounded-2xl p-5">
            <x-icon name="phone" class="h-6 w-6 text-brand-200" />
            <div><p class="text-xs text-muted">{{ __('Phone') }}</p><p class="font-medium text-strong">{{ setting('support_phone', '+237 600 000 000') }}</p></div>
        </div>
    </div>
</section>
@endsection
