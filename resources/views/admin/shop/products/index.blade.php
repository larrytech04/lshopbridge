@extends('layouts.admin')
@section('page-title', 'Shop products')

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap gap-3">
            <input name="q" value="{{ $filters['q'] ?? '' }}" class="field max-w-xs" placeholder="Search products…">
            <select name="category" class="field max-w-[180px]">
                <option value="">All categories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(($filters['category'] ?? '') == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
            <button class="btn btn-ghost"><x-icon name="search" class="h-4 w-4" /></button>
        </form>
        <a href="{{ route('admin.shop.products.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add product</a>
    </div>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Variants</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-app">
                @forelse ($products as $p)
                    <tr class="border-t border-app">
                        <td class="px-5 py-3"><p class="font-medium text-strong">{{ $p->name }}</p><p class="text-xs text-faint">{{ $p->brand }} · {{ $p->sales_count }} sold</p></td>
                        <td class="px-5 py-3 text-body">{{ $p->category->name ?? '-' }}</td>
                        <td class="px-5 py-3 text-body">{{ ucfirst($p->type) }}</td>
                        <td class="px-5 py-3 text-body">{{ $p->variants_count }}</td>
                        <td class="px-5 py-3">
                            @if($p->is_active)<span class="pill bg-emerald-500/15 text-emerald-300">Active</span>@else<span class="pill surface text-muted">Hidden</span>@endif
                            @if($p->is_best_deal)<span class="pill bg-amber-500/15 text-amber-300">Deal</span>@endif
                        </td>
                        <td class="px-5 py-3 text-right"><div class="flex justify-end gap-3"><a href="{{ route('admin.shop.products.edit', $p) }}" class="text-brand-400">Edit</a><form method="POST" action="{{ route('admin.shop.products.destroy', $p) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-rose-400">Delete</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-muted">No products.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
    <div>{{ $products->links() }}</div>
</div>
@endsection
