@extends('layouts.app')
@section('page-title', 'Learning center')

@section('content')
<div class="space-y-6">
    <p class="text-muted">{{ __('Practical guides to buy from China and ship to Africa.') }}</p>
    <div class="grid gap-6 md:grid-cols-3">
        @forelse ($guides as $guide)
            <a href="{{ route('learning.show', $guide) }}" class="glass glass-hover group overflow-hidden rounded-2xl">
                <div class="aspect-video bg-slate-500/15">@if ($guide->cover_image_path)<img src="{{ Storage::url($guide->cover_image_path) }}" class="h-full w-full object-cover" alt="">@endif</div>
                <div class="p-5">
                    <span class="pill surface text-brand-200 ring-1 ring-white/10">{{ ucfirst($guide->category) }}</span>
                    <h3 class="mt-3 font-semibold text-strong group-hover:text-brand-200">{{ $guide->title }}</h3>
                    <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $guide->excerpt }}</p>
                </div>
            </a>
        @empty
            <div class="md:col-span-3"><x-empty icon="book" title="{{ __('No guides yet') }}" message="Check back soon." /></div>
        @endforelse
    </div>
    <div>{{ $guides->links() }}</div>
</div>
@endsection
