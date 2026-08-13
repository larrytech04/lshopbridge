@extends('layouts.admin')
@section('page-title', 'SEO content quality')

@php
    $summaryCards = [
        ['Reviewed records', $summary['total'], 'list', 'slate'],
        ['Missing description', $summary['missing_description'], 'alert', 'rose'],
        ['Duplicate titles', $summary['duplicate_title'], 'copy', 'amber'],
        ['Duplicate descriptions', $summary['duplicate_description'], 'copy', 'amber'],
        ['Length warnings', $summary['length_warning'], 'alert', 'amber'],
        ['Noindexed', $summary['noindexed'], 'ban', 'slate'],
        ['Never reviewed', $summary['never_reviewed'], 'clock', 'sky'],
    ];
@endphp

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-strong">SEO content quality</h1>
        <p class="text-sm text-muted">Every content record with its own title/description, and what's missing, duplicated, or oddly sized. Legal pages, Learning Center guides and shop categories use their own admin form; shipping agents use the SEO panel on their profile.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
        @foreach ($summaryCards as [$label, $value, $icon, $tint])
            <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-{{ $tint }}-500/15 text-{{ $tint }}-600"><x-icon :name="$icon" class="h-4 w-4" /></span>
                    <p class="truncate text-[11px] text-faint">{{ $label }}</p>
                </div>
                <p class="mt-2 text-lg font-bold text-strong">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-y border-app text-muted">
                    <tr>
                        <th class="px-5 py-3">Record</th>
                        <th class="px-5 py-3">Title</th>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Reviewed</th>
                        <th class="px-5 py-3">Warnings</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-app">
                    @forelse ($rows as $row)
                        <tr class="align-top">
                            <td class="px-5 py-3">
                                <a href="{{ $row['edit_url'] }}" class="font-semibold text-strong hover:text-brand-500">{{ $row['label'] }}</a>
                                <p class="text-xs text-faint">{{ $row['type'] }}</p>
                            </td>
                            <td class="px-5 py-3 text-body">
                                <p class="max-w-xs truncate" title="{{ $row['title'] }}">{{ $row['title'] }}</p>
                                <p class="text-xs text-faint">{{ $row['title_length'] }} chars</p>
                            </td>
                            <td class="px-5 py-3 text-body">
                                @if ($row['description'])
                                    <p class="max-w-xs truncate" title="{{ $row['description'] }}">{{ $row['description'] }}</p>
                                    <p class="text-xs text-faint">{{ $row['description_length'] }} chars</p>
                                @else
                                    <span class="text-xs text-rose-600">Not set</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="pill {{ $row['robots_status'] === 'noindex' ? 'bg-slate-500/15 text-slate-600' : 'bg-emerald-500/15 text-emerald-600' }} text-[10px]">{{ $row['robots_status'] === 'noindex' ? 'Noindex' : 'Indexable' }}</span>
                                <span class="pill {{ $row['sitemap_included'] ? 'bg-emerald-500/15 text-emerald-600' : 'bg-amber-500/15 text-amber-600' }} text-[10px]">{{ $row['sitemap_included'] ? 'In sitemap' : 'Out of sitemap' }}</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-faint">
                                @if ($row['last_reviewed_at'])
                                    {{ $row['last_reviewed_at']->diffForHumans() }}
                                    @if ($row['reviewer_name'])<br>by {{ $row['reviewer_name'] }}@endif
                                @else
                                    {{ $row['reviewed_label'] ?? 'Never reviewed' }}
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if (empty($row['warnings']))
                                    <span class="pill bg-emerald-500/15 text-emerald-600 text-[10px]">No warnings</span>
                                @else
                                    <ul class="space-y-1">
                                        @foreach ($row['warnings'] as $warning)
                                            <li class="pill bg-rose-500/15 text-rose-600 text-[10px]">{{ $warning }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-faint">No published content yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-glass-card>
</div>
@endsection
