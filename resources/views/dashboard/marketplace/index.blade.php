@extends('layouts.app')
@section('page-title', 'Shipping agents')

@section('content')
<div class="space-y-6">
    <form method="GET" class="glass grid gap-3 rounded-2xl p-4 sm:grid-cols-4">
        <input name="q" value="{{ $filters['q'] ?? '' }}" class="field sm:col-span-2" placeholder="{{ __('Search agents…') }}">
        <select name="country" class="field">
            <option value="">{{ __('All countries') }}</option>
            @foreach ($countries as $c)<option value="{{ $c->id }}" @selected(($filters['country'] ?? '') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
        <select name="method" class="field">
            <option value="">{{ __('All methods') }}</option>
            @foreach (['air'=>'Air','sea'=>'Sea','express'=>'Express'] as $k=>$v)<option value="{{ $k }}" @selected(($filters['method'] ?? '')==$k)>{{ $v }}</option>@endforeach
        </select>
        <div class="sm:col-span-4"><button class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> {{ __('Search') }}</button></div>
    </form>

    <div class="grid gap-6 md:grid-cols-3">
        @forelse ($agents as $agent)
            @include('public.agents._card', ['agent' => $agent, 'href' => route('marketplace.show', $agent)])
        @empty
            <div class="md:col-span-3"><x-empty icon="truck" title="{{ __('No agents found') }}" message="Try different filters." /></div>
        @endforelse
    </div>
    <div>{{ $agents->links() }}</div>
</div>
@endsection
