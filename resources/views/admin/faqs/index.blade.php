@extends('layouts.admin')
@section('page-title', 'FAQs')

@php $categories = $faqs->pluck('category')->unique()->sort()->values(); @endphp

@section('content')
<datalist id="faq-categories">
    @foreach ($categories as $c)<option value="{{ $c }}">@endforeach
</datalist>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-3">
        @forelse ($faqs as $faq)
            <div class="glass rounded-2xl p-5" x-data="{ edit: false }">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="font-medium text-strong">{{ $faq->question }}</p><p class="mt-1 text-sm text-muted">{{ $faq->answer }}</p><p class="mt-1 text-xs text-faint">{{ ucfirst($faq->category) }} · Sort {{ $faq->sort }} @unless($faq->is_published)· <span class="text-amber-600">Hidden</span>@endunless</p></div>
                    <div class="flex items-center gap-2">
                        <button @click="edit=!edit" class="text-brand-600 text-sm">Edit</button>
                        <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-rose-600"><x-icon name="x" class="h-4 w-4" /></button></form>
                    </div>
                </div>
                <div x-show="edit" x-collapse style="display:none">
                    <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="mt-3 space-y-2 border-t border-app pt-3">@csrf @method('PUT')
                        <input name="question" value="{{ $faq->question }}" class="field" required>
                        <textarea name="answer" rows="2" class="field" required>{{ $faq->answer }}</textarea>
                        <div class="flex flex-wrap gap-2">
                            <input name="category" value="{{ $faq->category }}" list="faq-categories" class="field flex-1" placeholder="Category">
                            <input type="number" name="sort" value="{{ $faq->sort }}" class="field w-24" placeholder="Sort">
                            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_published" value="1" @checked($faq->is_published) class="rounded surface-2"> Published</label>
                        </div>
                        <button class="btn btn-primary text-sm">Save</button>
                    </form>
                </div>
            </div>
        @empty
            <x-empty icon="info" title="No FAQs" />
        @endforelse
    </div>
    <div>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Add FAQ</h3>
            <form method="POST" action="{{ route('admin.faqs.store') }}" class="mt-4 space-y-3">@csrf
                <input name="question" class="field" placeholder="Question" required>
                <textarea name="answer" rows="3" class="field" placeholder="Answer" required></textarea>
                <div class="flex flex-wrap gap-2">
                    <input name="category" class="field flex-1" list="faq-categories" placeholder="Category" value="general" required>
                    <input type="number" name="sort" class="field w-24" placeholder="Sort" value="0">
                </div>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_published" value="1" checked class="rounded surface-2"> Published</label>
                <button class="btn btn-primary w-full">Add FAQ</button>
            </form>
        </x-glass-card>
    </div>
</div>
@endsection
