@extends('layouts.public')
@section('title', 'Shipping agents · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-none px-4 pt-8 sm:px-6">
    <nav class="flex items-center gap-2 text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('home') }}" class="hover:text-strong">{{ __('Home') }}</a>
        <x-img-icon name="Arrow-Button-Right-3--Streamline-Ultimate.png" class="h-3 w-3 text-faint" />
        <span class="font-semibold text-strong">{{ __('Shipping agents') }}</span>
    </nav>

    <form method="GET" class="glass mt-6 grid gap-3 rounded-2xl p-4 sm:grid-cols-4">
        <input name="q" value="{{ $filters['q'] ?? '' }}" class="field sm:col-span-2" placeholder="{{ __('Search agents…') }}">
        <select name="country" class="field">
            <option value="">{{ __('All countries served') }}</option>
            @foreach ($countries as $c)<option value="{{ $c->id }}" @selected(($filters['country'] ?? '') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
        <select name="method" class="field">
            <option value="">{{ __('All methods') }}</option>
            @foreach (['air' => 'Air', 'sea' => 'Sea', 'express' => 'Express'] as $k => $v)<option value="{{ $k }}" @selected(($filters['method'] ?? '') == $k)>{{ $v }}</option>@endforeach
        </select>
        <div class="sm:col-span-4"><button class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> {{ __('Search') }}</button></div>
    </form>

    <div class="mt-8 rounded-3xl border border-app card-solid px-5 shadow-sm sm:px-7">
        @forelse ($agents as $agent)
            @include('public.agents._p2p_row', ['agent' => $agent])
        @empty
            <div class="py-10"><x-empty icon="truck" title="{{ __('No agents found') }}" message="Try adjusting your filters." /></div>
        @endforelse
    </div>
    <div class="mt-10">{{ $agents->links() }}</div>

    <div class="mt-14 glass rounded-2xl p-8 text-center">
        <h3 class="text-xl font-bold text-strong">{{ __('Are you a shipping or procurement agent?') }}</h3>
        <p class="mt-2 text-muted">{{ __('Join the marketplace and reach thousands of African buyers.') }}</p>
        <a href="{{ route('register.agent') }}" class="btn btn-primary mt-5 px-6 py-3">{{ __('Become an agent') }}</a>
    </div>
</section>
@endsection
