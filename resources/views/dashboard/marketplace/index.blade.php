@extends('layouts.app')
@section('page-title', 'Shipping agents')

@section('content')
<div class="space-y-6">
    <x-page-header :title="__('Shipping agents')" />

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

    <div class="rounded-3xl border border-app px-5 sm:px-7">
        @forelse ($agents as $agent)
            @include('public.agents._p2p_row', ['agent' => $agent, 'href' => route('marketplace.show', $agent)])
        @empty
            <div class="py-10"><x-empty icon="truck" title="{{ __('No agents found') }}" message="{{ __('Try different filters.') }}" /></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $agents->links() }}</div>
</div>
@endsection
