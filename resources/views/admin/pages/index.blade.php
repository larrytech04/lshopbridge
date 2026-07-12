@extends('layouts.admin')
@section('page-title', 'Legal & info pages')

@section('content')
<div class="space-y-5">
    <div class="flex justify-end"><a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> New page</a></div>
    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Title</th><th class="px-5 py-3">Slug</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Published</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($pages as $p)
                    <tr>
                        <td class="px-5 py-3 text-strong">{{ $p->title }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-muted">/p/{{ $p->slug }}</td>
                        <td class="px-5 py-3 text-body">{{ ucfirst($p->type) }}</td>
                        <td class="px-5 py-3">@if($p->is_published)<span class="pill bg-emerald-500/15 text-emerald-300">Yes</span>@else<span class="pill bg-slate-400/15 text-body">Draft</span>@endif</td>
                        <td class="px-5 py-3 text-right"><div class="flex justify-end gap-3"><a href="{{ route('admin.pages.edit', $p) }}" class="text-brand-300">Edit</a><form method="POST" action="{{ route('admin.pages.destroy', $p) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-rose-300">Delete</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-faint">No pages.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
</div>
@endsection
