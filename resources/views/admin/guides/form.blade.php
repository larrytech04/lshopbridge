@extends('layouts.admin')
@section('page-title', $guide->exists ? 'Edit guide' : 'New guide')

@section('content')
<div class="mx-auto max-w-3xl"
     x-data="{
        steps: {{ \Illuminate\Support\Js::from(old('steps', $guide->steps ?: [['title'=>'','body'=>'']])) }},
        faqs: {{ \Illuminate\Support\Js::from(old('faqs', $guide->faqs ?: [['q'=>'','a'=>'']])) }},
     }">
    <a href="{{ route('admin.guides.index') }}" class="text-sm text-brand-600 hover:text-brand-700">← Guides</a>

    @if ($guide->exists && isset($feedback))
        <x-glass-card class="mt-4">
            <h3 class="font-semibold text-strong">Reader feedback</h3>
            <div class="mt-3 flex flex-wrap gap-4 text-sm">
                <span class="pill bg-emerald-500/15 text-emerald-600">{{ $feedback['helpful'] }} helpful</span>
                <span class="pill bg-rose-500/15 text-rose-600">{{ $feedback['not_helpful'] }} not helpful</span>
            </div>
            @if (!empty($feedback['reasons']))
                <ul class="mt-3 space-y-1 text-xs text-muted">
                    @foreach ($feedback['reasons'] as $reason => $count)
                        <li>{{ \App\Enums\GuideFeedbackReason::tryFrom($reason)?->label() ?? ucfirst($reason) }}: {{ $count }}</li>
                    @endforeach
                </ul>
            @endif
        </x-glass-card>
    @endif

    <x-glass-card class="mt-4">
        <form method="POST" action="{{ $guide->exists ? route('admin.guides.update', $guide) : route('admin.guides.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @if($guide->exists)@method('PUT')@endif
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label">Title</label><input name="title" value="{{ old('title', $guide->title) }}" required class="field"></div>
                <div><label class="label">Category</label>
                    <select name="category" class="field">@foreach ($categories as $c)<option value="{{ $c }}" @selected(($guide->category ?? '')===$c)>{{ ucfirst($c) }}</option>@endforeach</select>
                </div>
                <div><label class="label">Difficulty</label>
                    <select name="difficulty" class="field">@foreach ($difficulties as $d)<option value="{{ $d->value }}" @selected(($guide->difficulty?->value ?? 'beginner')===$d->value)>{{ $d->label() }}</option>@endforeach</select>
                </div>
                <div><label class="label">Read minutes</label><input name="read_minutes" type="number" value="{{ old('read_minutes', $guide->read_minutes ?? 4) }}" class="field"></div>
            </div>
            <div><label class="label">Excerpt</label><textarea name="excerpt" rows="2" class="field">{{ old('excerpt', $guide->excerpt) }}</textarea></div>
            <div><label class="label">Body</label><textarea name="body" rows="6" class="field">{{ old('body', $guide->body) }}</textarea></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Cover image</label><input type="file" name="cover" accept="image/*" class="field"></div>
                <div><label class="label">Video URL (embed)</label><input name="video_url" value="{{ old('video_url', $guide->video_url) }}" class="field"></div>
            </div>

            {{-- Steps repeater --}}
            <div>
                <div class="mb-2 flex items-center justify-between"><label class="label mb-0">Steps</label><button type="button" @click="steps.push({title:'',body:''})" class="text-xs text-brand-600">+ Add step</button></div>
                <template x-for="(step, i) in steps" :key="i">
                    <div class="mb-2 grid grid-cols-12 gap-2">
                        <input :name="`steps[${i}][title]`" x-model="step.title" placeholder="Step title" class="field col-span-4">
                        <input :name="`steps[${i}][body]`" x-model="step.body" placeholder="Step description" class="field col-span-7">
                        <button type="button" @click="steps.splice(i,1)" class="col-span-1 text-rose-600">✕</button>
                    </div>
                </template>
            </div>

            {{-- FAQs repeater --}}
            <div>
                <div class="mb-2 flex items-center justify-between"><label class="label mb-0">FAQs</label><button type="button" @click="faqs.push({q:'',a:''})" class="text-xs text-brand-600">+ Add FAQ</button></div>
                <template x-for="(faq, i) in faqs" :key="i">
                    <div class="mb-2 grid grid-cols-12 gap-2">
                        <input :name="`faqs[${i}][q]`" x-model="faq.q" placeholder="Question" class="field col-span-4">
                        <input :name="`faqs[${i}][a]`" x-model="faq.a" placeholder="Answer" class="field col-span-7">
                        <button type="button" @click="faqs.splice(i,1)" class="col-span-1 text-rose-600">✕</button>
                    </div>
                </template>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">CTA label</label><input name="cta_label" value="{{ old('cta_label', $guide->cta_label) }}" class="field"></div>
                <div><label class="label">CTA URL</label><input name="cta_url" value="{{ old('cta_url', $guide->cta_url) }}" class="field"></div>
            </div>

            <div class="border-t border-app pt-4">
                <p class="label mb-2">SEO</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label text-xs">Meta title (optional)</label><input name="meta_title" value="{{ old('meta_title', $guide->meta_title) }}" class="field" placeholder="{{ $guide->title ?: 'Defaults to the guide title' }}"></div>
                    <div><label class="label text-xs">Meta description (optional)</label><input name="meta_description" value="{{ old('meta_description', $guide->meta_description) }}" class="field" placeholder="Defaults to the excerpt"></div>
                </div>
            </div>

            <div class="flex gap-5">
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $guide->is_published ?? true)) class="rounded surface-2"> Published</label>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $guide->is_featured ?? false)) class="rounded surface-2"> Featured</label>
            </div>
            <button class="btn btn-primary">{{ $guide->exists ? 'Update' : 'Create' }} guide</button>
        </form>
    </x-glass-card>
</div>
@endsection
