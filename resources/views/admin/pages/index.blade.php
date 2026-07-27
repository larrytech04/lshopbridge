@extends('layouts.admin')
@section('page-title', 'Legal & info pages')

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Legal documents publish to the <a href="{{ route('legal.index') }}" target="_blank" class="text-brand-600 hover:underline">Legal & Policy Center</a>. Info pages (like About) publish to their own <code class="surface px-1 text-xs">/p/{slug}</code> URL.</p>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> New page</a>
    </div>
    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Title</th><th class="px-5 py-3">URL</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Effective date</th><th class="px-5 py-3">Version</th><th class="px-5 py-3">Published</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($pages as $p)
                    <tr class="{{ $p->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3 text-strong">{{ $p->title }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-muted">{{ $p->type === 'legal' ? '/legal/'.$p->slug : '/p/'.$p->slug }}</td>
                        <td class="px-5 py-3 text-body">{{ ucfirst($p->type) }}</td>
                        <td class="px-5 py-3 text-body">{{ $p->category ? (\App\Models\Page::CATEGORIES[$p->category] ?? $p->category) : '—' }}</td>
                        <td class="px-5 py-3 text-body">{{ $p->effective_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-5 py-3 text-body">v{{ $p->version }}</td>
                        <td class="px-5 py-3">@if($p->is_published)<span class="pill bg-emerald-500/15 text-emerald-600">Yes</span>@else<span class="pill bg-slate-400/15 text-body">Draft</span>@endif</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                @unless($p->trashed())
                                    <a href="{{ route('admin.pages.edit', $p) }}" class="text-brand-600">Edit</a>
                                    <form method="POST" action="{{ route('admin.pages.destroy', $p) }}" onsubmit="return confirm('Archive this page?')">@csrf @method('DELETE')<button class="text-rose-600">Archive</button></form>
                                @else
                                    <form method="POST" action="{{ route('admin.pages.restore', $p) }}">@csrf<button class="text-brand-600">Restore</button></form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-0">
                        <x-empty icon="doc" title="No pages match your filters" message="Clear your filters or create a new content page.">
                            <x-slot:action><a href="{{ route('admin.pages.create') }}" class="qa-btn qa-btn-good">Create page</a></x-slot:action>
                        </x-empty>
                    </td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
</div>
@endsection
