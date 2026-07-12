@extends('layouts.admin')
@section('page-title', 'Banners')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-3">
        @forelse ($banners as $b)
            <div class="glass rounded-2xl p-5">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="font-medium text-strong">{{ $b->title }}</p><p class="text-sm text-muted">{{ $b->subtitle }}</p><p class="mt-1 text-xs text-faint">{{ ucfirst($b->type) }} · {{ $b->position }} @unless($b->is_active)· <span class="text-amber-300">Inactive</span>@endunless</p></div>
                    <form method="POST" action="{{ route('admin.banners.destroy', $b) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-rose-300"><x-icon name="x" class="h-4 w-4" /></button></form>
                </div>
            </div>
        @empty
            <x-empty icon="sparkles" title="No banners" message="Add a hero banner for the homepage." />
        @endforelse
    </div>
    <div>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Add banner</h3>
            <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="mt-4 space-y-3">@csrf
                <input name="title" class="field" placeholder="Title" required>
                <input name="subtitle" class="field" placeholder="Subtitle">
                <div class="grid grid-cols-2 gap-2"><input name="cta_label" class="field" placeholder="CTA label"><input name="cta_url" class="field" placeholder="CTA URL"></div>
                <div class="grid grid-cols-2 gap-2">
                    <select name="type" class="field"><option value="hero">Hero</option><option value="promo">Promo</option><option value="strip">Strip</option></select>
                    <input name="position" class="field" value="home">
                </div>
                <input type="file" name="image" accept="image/*" class="field">
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded surface-2"> Active</label>
                <button class="btn btn-primary w-full">Add banner</button>
            </form>
        </x-glass-card>
    </div>
</div>
@endsection
