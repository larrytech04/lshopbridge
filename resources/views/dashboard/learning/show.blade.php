@extends('layouts.app')
@section('page-title', $guide->title)

@section('content')
<article class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('learning.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← {{ __('Learning center') }}</a>
    <h1 class="text-3xl font-bold text-strong">{{ $guide->title }}</h1>
    @if ($guide->excerpt)<p class="text-lg text-body">{{ $guide->excerpt }}</p>@endif
    @if ($guide->cover_image_path)<img src="{{ Storage::url($guide->cover_image_path) }}" class="w-full rounded-2xl" alt="">@endif
    @if ($guide->body)<div class="space-y-4 leading-relaxed text-body">{!! nl2br(e($guide->body)) !!}</div>@endif

    @if (!empty($guide->steps))
        <div class="space-y-3">
            @foreach ($guide->steps as $i => $step)
                <div class="glass flex gap-4 rounded-2xl p-5">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-600 font-bold text-strong">{{ $i+1 }}</span>
                    <div><h3 class="font-semibold text-strong">{{ $step['title'] ?? '' }}</h3><p class="mt-1 text-sm text-muted">{{ $step['body'] ?? '' }}</p></div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($related->isNotEmpty())
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ($related as $r)
                <a href="{{ route('learning.show', $r) }}" class="glass glass-hover rounded-2xl p-4"><p class="text-sm font-semibold text-strong">{{ $r->title }}</p></a>
            @endforeach
        </div>
    @endif
</article>
@endsection
