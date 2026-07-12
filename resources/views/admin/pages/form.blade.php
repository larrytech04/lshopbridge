@extends('layouts.admin')
@section('page-title', $page->exists ? 'Edit page' : 'New page')

@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('admin.pages.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← Pages</a>
    <x-glass-card class="mt-4">
        <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="space-y-4">
            @csrf @if($page->exists)@method('PUT')@endif
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Title</label><input name="title" value="{{ old('title', $page->title) }}" required class="field"></div>
                <div><label class="label">Slug (optional)</label><input name="slug" value="{{ old('slug', $page->slug) }}" class="field" placeholder="terms, privacy, refund-policy, about"></div>
            </div>
            <div><label class="label">Type</label><select name="type" class="field"><option value="legal" @selected(($page->type ?? 'legal')==='legal')>Legal</option><option value="info" @selected(($page->type ?? '')==='info')>Info</option></select></div>
            <div><label class="label">Excerpt</label><textarea name="excerpt" rows="2" class="field">{{ old('excerpt', $page->excerpt) }}</textarea></div>
            <div><label class="label">Body</label><textarea name="body" rows="12" class="field">{{ old('body', $page->body) }}</textarea></div>
            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published ?? true)) class="rounded surface-2"> Published</label>
            <button class="btn btn-primary">{{ $page->exists ? 'Update' : 'Create' }} page</button>
        </form>
    </x-glass-card>
</div>
@endsection
