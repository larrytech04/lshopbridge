@extends('layouts.public')
@section('title', __('Legal & Policy Center').' · '.config('platform.name'))
@section('meta_description', __('Terms, privacy, refund and other policies governing your use of :name.', ['name' => setting('site_name', config('platform.name'))]))

@push('structured-data')
    {!! \App\Services\Seo\StructuredDataBuilder::scriptTag(app(\App\Services\Seo\StructuredDataBuilder::class)->breadcrumbList([
        ['name' => __('Home'), 'url' => app(\App\Services\Seo\CanonicalUrlService::class)->normalize(route('home'))],
        ['name' => __('Legal Center'), 'url' => app(\App\Services\Seo\CanonicalUrlService::class)->normalize(route('legal.index'))],
    ])) !!}
@endpush

@section('content')
<div class="mx-auto max-w-5xl px-4 pt-10 pb-16 sm:px-6">
    <div class="text-center">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-500/10 text-brand-500"><x-icon name="doc" class="h-6 w-6" /></span>
        <h1 class="mt-4 text-3xl font-extrabold text-strong sm:text-4xl">{{ __('Legal & Policy Center') }}</h1>
        <p class="mx-auto mt-3 max-w-2xl text-base text-muted">{{ __('Every policy that governs how :name works, written in plain language wherever possible.', ['name' => setting('site_name', config('platform.name'))]) }}</p>
    </div>

    <div class="relative mx-auto mt-8 max-w-md">
        <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
        <input type="text" x-data x-on:input="
            const q = $event.target.value.toLowerCase();
            document.querySelectorAll('[data-legal-link]').forEach(el => {
                el.closest('[data-legal-item]').style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        " placeholder="{{ __('Search policies…') }}" class="field !rounded-full pl-11" aria-label="{{ __('Search policies') }}">
    </div>

    @if ($grouped->isEmpty())
        <p class="mt-12 text-center text-sm text-faint">{{ __('No policies are published yet.') }}</p>
    @else
        <div class="mt-10 grid gap-6 sm:grid-cols-2">
            @foreach ($categories as $key => $label)
                @continue(! isset($grouped[$key]) || $grouped[$key]->isEmpty())
                <div class="card-solid rounded-3xl border border-app p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-brand-500">{{ __($label) }}</p>
                    <ul class="mt-3 space-y-1">
                        @foreach ($grouped[$key] as $doc)
                            <li data-legal-item>
                                <a href="{{ route('legal.show', $doc) }}" data-legal-link class="group flex items-center justify-between gap-2 rounded-xl px-2 py-2 text-sm text-body hover:surface-2 hover:text-strong">
                                    <span class="truncate">{{ $doc->title }}</span>
                                    <x-icon name="chevron-right" class="h-3.5 w-3.5 shrink-0 text-faint transition group-hover:translate-x-0.5 group-hover:text-body" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-12 rounded-3xl border border-app p-5 text-sm text-muted">
        <p class="font-semibold text-strong">{{ __('Questions about any policy?') }}</p>
        <p class="mt-1">{{ __('Contact :email and we\'ll point you to the right document or answer directly.', ['email' => setting('support_email', config('platform.support_email', 'support@example.com'))]) }}</p>
    </div>
</div>
@endsection
