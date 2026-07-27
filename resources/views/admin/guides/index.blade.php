@extends('layouts.admin')
@section('page-title', 'Guides')

@section('content')
<div class="space-y-5">
    <div class="flex justify-end"><a href="{{ route('admin.guides.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> New guide</a></div>
    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Title</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Difficulty</th><th class="px-5 py-3">Views</th><th class="px-5 py-3">Published</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($guides as $g)
                    <tr class="{{ $g->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3 text-strong">{{ $g->title }} @if($g->is_featured)<span class="pill bg-accent-500/15 text-accent-300">Featured</span>@endif</td>
                        <td class="px-5 py-3 text-body">{{ ucfirst($g->category) }}</td>
                        <td class="px-5 py-3"><span class="pill {{ $g->difficulty->color() }} text-[10px]">{{ $g->difficulty->label() }}</span></td>
                        <td class="px-5 py-3 text-body">{{ number_format($g->views) }}</td>
                        <td class="px-5 py-3">@if($g->is_published)<span class="pill bg-emerald-500/15 text-emerald-600">Yes</span>@else<span class="pill bg-slate-400/15 text-body">Draft</span>@endif</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                @unless($g->trashed())
                                    <a href="{{ route('admin.guides.edit', $g) }}" class="text-brand-600">Edit</a>
                                    <form method="POST" action="{{ route('admin.guides.destroy', $g) }}" onsubmit="return confirm('Archive this guide?')">@csrf @method('DELETE')<button class="text-rose-600">Archive</button></form>
                                @else
                                    <form method="POST" action="{{ route('admin.guides.restore', $g) }}">@csrf<button class="text-brand-600">Restore</button></form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-0">
                        <x-empty icon="book" title="No guides found" message="Create a guide to help customers shop from China, fund wallets, and understand platform services.">
                            <x-slot:action><a href="{{ route('admin.guides.create') }}" class="qa-btn qa-btn-good">Create guide</a></x-slot:action>
                        </x-empty>
                    </td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
</div>
@endsection
