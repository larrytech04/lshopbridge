@extends('layouts.public')
@section('title', ($page->meta_title ?: $page->title).' · '.config('platform.name'))
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($excerptText), 160))

@section('content')
<div class="mx-auto max-w-[1200px] px-4 pt-8 pb-16 sm:px-6" x-data="{ mobileToc: false }">

    <nav class="legal-print-hide mb-5 flex flex-wrap items-center gap-1.5 text-xs text-faint">
        <a href="{{ route('legal.index') }}" class="hover:text-body">{{ __('Legal Center') }}</a>
        <x-icon name="chevron-right" class="h-3 w-3 shrink-0" />
        <span class="text-body">{{ $page->title }}</span>
    </nav>

    <div class="grid gap-8 lg:grid-cols-[240px_minmax(0,1fr)]">
        {{-- Desktop table of contents --}}
        @if (count($headings))
            <aside class="legal-toc-sidebar legal-print-hide hidden lg:block">
                <div class="glass sticky top-24 max-h-[calc(100vh-7rem)] overflow-y-auto rounded-2xl p-4 ring-1 ring-app">
                    <p class="px-2 text-[11px] font-bold uppercase tracking-wider text-faint">{{ __('On this page') }}</p>
                    <nav class="mt-2 space-y-0.5 text-sm">
                        @foreach ($headings as $h)
                            <a href="#{{ $h['id'] }}" class="block truncate rounded-lg px-2 py-1.5 text-body hover:surface-2 hover:text-strong {{ $h['level'] === 3 ? 'pl-5 text-xs text-muted' : '' }}">{{ $h['text'] }}</a>
                        @endforeach
                    </nav>
                    <div class="mt-4 space-y-1.5 border-t border-app pt-3">
                        <button type="button" onclick="window.print()" class="btn btn-ghost w-full !py-1.5 text-xs"><x-icon name="download" class="h-3.5 w-3.5" /> {{ __('Print this policy') }}</button>
                        <button type="button" x-data @click="navigator.clipboard.writeText(window.location.href.split('#')[0]); $el.textContent = @js(__('Link copied'))" class="btn btn-ghost w-full !py-1.5 text-xs">{{ __('Copy link') }}</button>
                    </div>
                </div>
            </aside>
        @endif

        <article class="min-w-0 max-w-3xl">
            {{-- Mobile table of contents (collapsible, sticky toggle) --}}
            @if (count($headings))
                <div class="legal-toc-mobile legal-print-hide sticky top-16 z-20 mb-4 lg:hidden">
                    <button type="button" @click="mobileToc = !mobileToc"
                            class="btn btn-ghost flex w-full items-center justify-between !py-2.5 text-sm shadow-sm" style="background: var(--sidebar-bg); backdrop-filter: blur(12px);"
                            ::aria-expanded="mobileToc.toString()" aria-controls="legal-mobile-toc-panel">
                        <span class="flex items-center gap-2"><x-icon name="list" class="h-4 w-4" /> {{ __('Contents') }}</span>
                        <x-icon name="chevron-down" class="h-4 w-4 shrink-0 transition-transform duration-200" ::class="mobileToc ? 'rotate-180' : ''" />
                    </button>
                    <div id="legal-mobile-toc-panel" x-show="mobileToc" x-collapse x-cloak class="card-solid mt-2 rounded-2xl border border-app p-3 shadow-lg" style="display:none">
                        <nav class="space-y-0.5 text-sm">
                            @foreach ($headings as $h)
                                <a href="#{{ $h['id'] }}" @click="mobileToc = false" class="block truncate rounded-lg px-2 py-2 text-body {{ $h['level'] === 3 ? 'pl-5 text-xs text-muted' : '' }}">{{ $h['text'] }}</a>
                            @endforeach
                        </nav>
                    </div>
                </div>
            @endif

            <header class="border-b border-app pb-6">
                <p class="text-xs font-bold uppercase tracking-wider text-brand-500">{{ $categoryLabel }}</p>
                <h1 class="mt-1 text-3xl font-extrabold leading-tight text-strong sm:text-4xl">{{ $page->title }}</h1>
                @if ($excerptText)<p class="mt-3 text-base text-muted sm:text-lg">{{ $excerptText }}</p>@endif

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-faint">
                    @if ($page->effective_date)
                        <span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ __('Effective :date', ['date' => $page->effective_date->format('M j, Y')]) }}</span>
                    @endif
                    @if ($page->last_reviewed_at)
                        <span>{{ __('Last updated :date', ['date' => $page->last_reviewed_at->format('M j, Y')]) }}</span>
                    @endif
                    <span>{{ __('Version :n', ['n' => $page->version]) }}</span>
                    <span>{{ __('Applies to: :scope', ['scope' => $page->applicable_countries ? implode(', ', $page->applicable_countries) : __('all supported countries')]) }}</span>
                </div>
            </header>

            @if ($summaryHtml)
                <div class="surface-2 my-6 rounded-2xl border border-app p-5">
                    <p class="mb-2 flex items-center gap-2 text-sm font-bold text-strong"><x-img-icon name="Idea-Strategy--Streamline-Ultimate.png" class="h-4 w-4 text-brand-500" /> {{ __('In simple terms') }}</p>
                    <div class="legal-content prose prose-sm max-w-none">{!! $summaryHtml !!}</div>
                    <p class="mt-3 text-xs text-faint">{{ __('This summary is provided to make the policy easier to understand. The complete policy below contains the full terms.') }}</p>
                </div>
            @endif

            <div id="content" class="legal-content prose prose-sm mt-8 sm:prose-base">
                {!! $bodyHtml !!}
            </div>

            <div class="legal-print-hide mt-10 border-t border-app pt-6 text-sm text-muted">
                <p class="font-semibold text-strong">{{ __('Questions about this policy?') }}</p>
                <p class="mt-1">{{ __('Contact :email.', ['email' => setting('support_email', config('platform.support_email', 'support@example.com'))]) }}</p>
            </div>

            @if ($related->isNotEmpty())
                <div class="legal-print-hide mt-8 border-t border-app pt-6">
                    <p class="mb-3 text-sm font-bold text-strong">{{ __('Related policies') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($related as $r)
                            <a href="{{ route('legal.show', $r) }}" class="pill surface text-xs text-body hover:surface-2">{{ $r->title }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="legal-print-footer">
                {{ setting('site_name', config('platform.name')) }} — {{ $page->title }} — {{ __('Version :n', ['n' => $page->version]) }}
                @if ($page->effective_date) — {{ __('Effective :date', ['date' => $page->effective_date->format('M j, Y')]) }} @endif
                — {{ __('Generated :date', ['date' => now()->format('M j, Y')]) }} — {{ __('Check the LshopBridge Legal Center for the latest version.') }}
            </p>
        </article>
    </div>
</div>
@endsection
