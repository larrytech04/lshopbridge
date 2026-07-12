@extends('layouts.public')
@section('title', $page->title.' · '.config('platform.name'))

@section('content')
<article class="mx-auto max-w-3xl px-4 pt-20 sm:px-6">
    <h1 class="text-4xl font-extrabold text-strong">{{ $page->title }}</h1>
    @if ($page->last_reviewed_at)<p class="mt-2 text-sm text-faint">Last updated {{ $page->last_reviewed_at->format('M j, Y') }}</p>@endif
    @if ($page->excerpt)<p class="mt-4 text-lg text-body">{{ $page->excerpt }}</p>@endif
    <div class="mt-8 space-y-4 leading-relaxed text-body">{!! nl2br(e($page->body)) !!}</div>
</article>
@endsection
