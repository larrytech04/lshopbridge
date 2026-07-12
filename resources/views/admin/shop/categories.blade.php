@extends('layouts.admin')
@section('page-title', 'Shop categories')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-3">
        @forelse ($categories as $c)
            <div class="glass rounded-2xl p-4" x-data="{ edit:false }">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl surface text-brand-400"><x-icon :name="$c->icon" class="h-5 w-5" /></span>
                        <div><p class="font-semibold text-strong">{{ $c->name }}</p><p class="text-xs text-faint">{{ $c->products_count }} products · {{ $c->slug }} @unless($c->is_active) · <span class="text-amber-400">hidden</span>@endunless</p></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="edit=!edit" class="text-sm text-brand-400">Edit</button>
                        <form method="POST" action="{{ route('admin.shop.categories.destroy', $c) }}" onsubmit="return confirm('Delete category?')">@csrf @method('DELETE')<button class="text-rose-400"><x-icon name="x" class="h-4 w-4" /></button></form>
                    </div>
                </div>
                <div x-show="edit" x-collapse style="display:none">
                    <form method="POST" action="{{ route('admin.shop.categories.update', $c) }}" class="mt-3 grid gap-2 border-t border-app pt-3 sm:grid-cols-2">@csrf @method('PUT')
                        <input name="name" value="{{ $c->name }}" class="field" required>
                        <input name="icon" value="{{ $c->icon }}" class="field" placeholder="icon name">
                        <input name="tagline" value="{{ $c->tagline }}" class="field sm:col-span-2" placeholder="Tagline">
                        <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" @checked($c->is_active) class="rounded"> Active</label>
                        <div class="sm:col-span-2"><button class="btn btn-primary text-sm">Save</button></div>
                    </form>
                </div>
            </div>
        @empty
            <x-empty icon="list" title="No categories" />
        @endforelse
    </div>
    <div>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Add category</h3>
            <form method="POST" action="{{ route('admin.shop.categories.store') }}" class="mt-4 space-y-3">@csrf
                <input name="name" class="field" placeholder="Name" required>
                <input name="icon" class="field" placeholder="Icon (e.g. giftcard, sim, shield)" value="sparkles">
                <input name="tagline" class="field" placeholder="Tagline">
                <input name="sort" type="number" class="field" placeholder="Sort" value="0">
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Active</label>
                <button class="btn btn-primary w-full">Add category</button>
            </form>
        </x-glass-card>
    </div>
</div>
@endsection
