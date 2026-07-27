@extends('layouts.admin')
@section('page-title', 'Banners & Promotions')

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-strong">Banners & Promotions</h1>
        <p class="text-sm text-muted">Hero banners (homepage) and strip banners (sitewide announcement bar) with real, enforced targeting by date, audience, and country.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-3">
            @forelse ($banners as $b)
                <div class="glass rounded-2xl p-5 {{ $b->trashed() ? 'opacity-60' : '' }}" x-data="{ edit: false }">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium text-strong">{{ $b->title }}</p>
                            <p class="text-sm text-muted">{{ $b->subtitle }}</p>
                            <p class="mt-1 flex flex-wrap gap-x-2 text-xs text-faint">
                                <span>{{ ucfirst($b->type) }} · {{ $b->position }}</span>
                                <span>· {{ $b->audience->label() }}</span>
                                @if($b->country)<span>· {{ $b->country->name }}</span>@endif
                                @if($b->starts_at || $b->ends_at)<span>· {{ $b->starts_at?->format('M j') ?? 'now' }} → {{ $b->ends_at?->format('M j, Y') ?? 'no end' }}</span>@endif
                                @unless($b->is_active)<span class="text-amber-600">· Inactive</span>@endunless
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            @unless($b->trashed())
                                <button type="button" @click="edit = !edit" class="text-sm text-brand-600">Edit</button>
                                <form method="POST" action="{{ route('admin.banners.destroy', $b) }}" onsubmit="return confirm('Archive this banner?')">@csrf @method('DELETE')<button class="text-rose-600"><x-icon name="x" class="h-4 w-4" /></button></form>
                            @else
                                <form method="POST" action="{{ route('admin.banners.restore', $b) }}">@csrf<button class="text-sm text-brand-600">Restore</button></form>
                            @endunless
                        </div>
                    </div>

                    @unless($b->trashed())
                        <div x-show="edit" x-collapse style="display:none" class="mt-4 border-t border-app pt-4">
                            <form method="POST" action="{{ route('admin.banners.update', $b) }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-2">
                                @csrf @method('PUT')
                                <input name="title" value="{{ $b->title }}" class="field sm:col-span-2" placeholder="Title" required>
                                <input name="subtitle" value="{{ $b->subtitle }}" class="field sm:col-span-2" placeholder="Subtitle">
                                <input name="cta_label" value="{{ $b->cta_label }}" class="field" placeholder="CTA label">
                                <input name="cta_url" value="{{ $b->cta_url }}" class="field" placeholder="CTA URL">
                                <select name="type" class="field"><option value="hero" @selected($b->type==='hero')>Hero</option><option value="promo" @selected($b->type==='promo')>Promo</option><option value="strip" @selected($b->type==='strip')>Strip</option></select>
                                <input name="position" value="{{ $b->position }}" class="field" placeholder="Position (e.g. home)">
                                <select name="audience" class="field">
                                    @foreach ($audiences as $a)<option value="{{ $a->value }}" @selected($b->audience===$a)>{{ $a->label() }}</option>@endforeach
                                </select>
                                <select name="country_id" class="field">
                                    <option value="">Any country</option>
                                    @foreach ($countries as $c)<option value="{{ $c->id }}" @selected($b->country_id===$c->id)>{{ $c->name }}</option>@endforeach
                                </select>
                                <div><label class="label text-xs">Starts</label><input type="datetime-local" name="starts_at" value="{{ $b->starts_at?->format('Y-m-d\TH:i') }}" class="field"></div>
                                <div><label class="label text-xs">Ends</label><input type="datetime-local" name="ends_at" value="{{ $b->ends_at?->format('Y-m-d\TH:i') }}" class="field"></div>
                                <input type="file" name="image" accept="image/*" class="field sm:col-span-2">
                                <label class="flex items-center gap-2 text-sm text-body sm:col-span-2"><input type="checkbox" name="is_active" value="1" @checked($b->is_active) class="rounded surface-2"> Active</label>
                                <button class="btn btn-primary sm:col-span-2">Save changes</button>
                            </form>
                        </div>
                    @endunless
                </div>
            @empty
                <x-empty icon="sparkles" title="No banners" message="Add a hero banner for the homepage or a strip banner for the sitewide announcement bar." />
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
                    <select name="audience" class="field">
                        @foreach ($audiences as $a)<option value="{{ $a->value }}">{{ $a->label() }}</option>@endforeach
                    </select>
                    <select name="country_id" class="field">
                        <option value="">Any country</option>
                        @foreach ($countries as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="label text-xs">Starts</label><input type="datetime-local" name="starts_at" class="field"></div>
                        <div><label class="label text-xs">Ends</label><input type="datetime-local" name="ends_at" class="field"></div>
                    </div>
                    <input type="file" name="image" accept="image/*" class="field">
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded surface-2"> Active</label>
                    <button class="btn btn-primary w-full">Add banner</button>
                </form>
            </x-glass-card>
        </div>
    </div>
</div>
@endsection
