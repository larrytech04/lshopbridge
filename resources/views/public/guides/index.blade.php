@extends('layouts.public')
@section('title', 'China buying academy · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-none px-4 pt-20 sm:px-6">
    <div class="text-center">
        <span class="pill surface text-brand-200 ring-1 ring-white/10"><x-icon name="book" class="h-3.5 w-3.5" /> {{ __('Free academy') }}</span>
        <h1 class="mt-5 text-4xl font-extrabold text-strong sm:text-5xl">{{ __('China buying academy') }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-lg text-body">{{ __('Master 1688, Taobao, Pinduoduo, Alipay, shipping and customs with step-by-step guides.') }}</p>
    </div>

    @php $cats = ['1688','taobao','pinduoduo','alipay','shipping','customs','mistakes']; @endphp
    <div class="mt-10 flex flex-wrap justify-center gap-2">
        <a href="{{ route('guides.index') }}" class="pill {{ !$category ? 'bg-slate-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ __('All') }}</a>
        @foreach ($cats as $cat)
            <a href="{{ route('guides.index', ['category' => $cat]) }}" class="pill {{ $category === $cat ? 'bg-slate-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ ucfirst($cat) }}</a>
        @endforeach
    </div>

    <div class="mt-10 grid gap-6 md:grid-cols-3">
        @forelse ($guides as $guide)
            <a href="{{ route('guides.show', $guide) }}" class="glass glass-hover group overflow-hidden rounded-2xl">
                <div class="aspect-video bg-slate-500/15">
                    @if ($guide->cover_image_path)<img src="{{ Storage::url($guide->cover_image_path) }}" class="h-full w-full object-cover" alt="">@endif
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-2 text-xs text-faint"><span class="pill surface text-brand-200 ring-1 ring-white/10">{{ __(ucfirst($guide->category)) }}</span> · {{ __(':n min read', ['n' => $guide->read_minutes]) }}</div>
                    <h3 class="mt-3 font-semibold text-strong group-hover:text-brand-200">{{ __($guide->title) }}</h3>
                    <p class="mt-1.5 line-clamp-2 text-sm text-muted">{{ __($guide->excerpt) }}</p>
                </div>
            </a>
        @empty
            <div class="md:col-span-3"><x-empty icon="book" title="{{ __('No guides yet') }}" message="Guides will appear here once published." /></div>
        @endforelse
    </div>

    <div class="mt-10">{{ $guides->links() }}</div>
</section>
@endsection
