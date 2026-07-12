{{-- App-themed pagination: small back/forward buttons at each end + page numbers. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-wrap items-center justify-center gap-1.5">
        {{-- Backward --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="grid h-8 w-8 place-items-center rounded-lg border border-app surface text-faint opacity-50">
                <x-icon name="chevron-right" class="h-3.5 w-3.5 rotate-180" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('Previous') }}"
               class="grid h-8 w-8 place-items-center rounded-lg border border-app surface text-muted transition hover:text-strong">
                <x-icon name="chevron-right" class="h-3.5 w-3.5 rotate-180" />
            </a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1 text-sm text-faint">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="grid h-8 min-w-8 place-items-center rounded-lg bg-brand-600 px-2 text-sm font-bold text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="grid h-8 min-w-8 place-items-center rounded-lg border border-app surface px-2 text-sm font-medium text-body transition hover:text-strong">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Forward --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('Next') }}"
               class="grid h-8 w-8 place-items-center rounded-lg border border-app surface text-muted transition hover:text-strong">
                <x-icon name="chevron-right" class="h-3.5 w-3.5" />
            </a>
        @else
            <span aria-disabled="true" class="grid h-8 w-8 place-items-center rounded-lg border border-app surface text-faint opacity-50">
                <x-icon name="chevron-right" class="h-3.5 w-3.5" />
            </span>
        @endif
    </nav>
@endif
