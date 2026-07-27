@extends('layouts.public')
@section('title', 'Contact us · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-5xl px-4 pt-20 sm:px-6">
    <div class="mx-auto max-w-2xl text-center">
        <h1 class="text-4xl font-extrabold text-strong sm:text-5xl">{{ __('Get in touch') }}</h1>
        <p class="mt-4 text-lg text-body">{{ __('Questions, partnerships or support, we’re here to help.') }}</p>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-3">
        <div class="glass rounded-3xl p-8 lg:col-span-2">
            <x-flash />
            <form method="POST" action="{{ route('contact.submit') }}" class="space-y-4">
                @csrf
                <x-honeypot />
                <x-form-timing form-type="contact" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">{{ __('Name') }}</label><input name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required class="field"></div>
                    <div><label class="label">{{ __('Email') }}</label><input name="email" type="email" value="{{ old('email', auth()->user()->email ?? '') }}" required class="field"></div>
                </div>
                <div><label class="label">{{ __('Subject') }}</label><input name="subject" value="{{ old('subject') }}" required class="field"></div>
                <div><label class="label">{{ __('Message') }}</label><textarea name="message" rows="6" required class="field">{{ old('message') }}</textarea></div>
                <x-turnstile action="contact" />
                <button class="btn btn-primary w-full">{{ __('Send message') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
            </form>
            <p class="mt-3 flex items-center gap-1.5 text-xs text-faint">
                <x-icon name="clock" class="h-3.5 w-3.5 shrink-0" />
                {{ __('Typical response time: a few hours for high priority, within 1-2 days otherwise.') }}
            </p>
        </div>

        <div class="space-y-4">
            <a href="{{ route('public.faqs') }}" class="glass flex items-center gap-3 rounded-2xl p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-brand-600" style="background: color-mix(in srgb, var(--color-brand-600) 12%, transparent)">
                    <x-icon name="info" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <p class="font-medium text-strong">{{ __('Check our FAQs') }}</p>
                    <p class="text-xs text-muted">{{ __('Common questions answered instantly.') }}</p>
                </div>
                <x-icon name="arrow-right" class="ml-auto h-4 w-4 shrink-0 text-faint" />
            </a>

            <div class="glass flex items-center gap-3 rounded-2xl p-5">
                <x-icon name="mail" class="h-6 w-6 text-brand-600" />
                <div class="min-w-0"><p class="text-xs text-muted">{{ __('Email') }}</p><p class="truncate font-medium text-strong">{{ setting('support_email', config('platform.support_email')) }}</p></div>
            </div>
            <div class="glass flex items-center gap-3 rounded-2xl p-5">
                <x-icon name="phone" class="h-6 w-6 text-brand-600" />
                <div class="min-w-0"><p class="text-xs text-muted">{{ __('Phone') }}</p><p class="truncate font-medium text-strong">{{ setting('support_phone', '+237 600 000 000') }}</p></div>
            </div>

            <x-discord-card />
        </div>
    </div>
</section>
@endsection
