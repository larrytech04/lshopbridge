{{--
    A real, visible breadcrumb trail — not schema-only markup. Deliberately
    built this way (not just a JSON-LD block) so BreadcrumbList structured
    data always matches what's actually on the page, per the brief's own
    "schema must match visible content, never mark hidden content" rule.

    Feed the SAME $items array into SeoService::withBreadcrumbs($seo, $items)
    for the matching JSON-LD — see guides/show.blade.php for the pattern.
--}}
@props(['items'])

<nav aria-label="{{ __('Breadcrumb') }}" class="flex flex-wrap items-center gap-1.5 text-sm text-muted">
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                @if (! $loop->first)
                    <x-icon name="chevron-right" class="h-3 w-3 shrink-0 text-faint" />
                @endif
                @if ($loop->last)
                    <span class="font-medium text-body" aria-current="page">{{ $item['name'] }}</span>
                @else
                    <a href="{{ $item['url'] }}" class="truncate hover:text-brand-500">{{ $item['name'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
