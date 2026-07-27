@extends('layouts.app')
@section('page-title', __('Shipping Requests'))

@section('content')
<x-page-header :title="__('Shipping Requests')" :subtitle="__('Quote on open customer requests and track shipments you have won.')" />

<div class="space-y-8">
    <div>
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-faint">{{ __('Open for quotes') }}</h2>
        @forelse ($openRequests as $r)
            <a href="{{ route('agent.shipping-requests.show', $r) }}" class="glass glass-hover mb-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-5">
                <div>
                    <p class="font-semibold text-strong">{{ $r->reference }}</p>
                    <p class="text-sm text-muted">{{ $r->origin_city }} ({{ $r->origin_country }}) &rarr; {{ $r->destination_city }} ({{ $r->destination_country }})</p>
                    <p class="mt-1 text-xs text-faint">{{ $r->package_description }}</p>
                </div>
                <x-status-badge :status="$r->status" />
            </a>
        @empty
            <x-empty icon="truck" :title="__('No open requests')" :message="__('New customer shipping requests will appear here.')" />
        @endforelse
        <div class="mt-3">{{ $openRequests->onEachSide(1)->links() }}</div>
    </div>

    <div>
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-faint">{{ __('Active shipments') }}</h2>
        @forelse ($assigned as $r)
            <a href="{{ route('agent.shipping-requests.show', $r) }}" class="glass glass-hover mb-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-5">
                <div>
                    <p class="font-semibold text-strong">{{ $r->reference }}</p>
                    <p class="text-sm text-muted">{{ $r->user->name }}</p>
                </div>
                <x-status-badge :status="$r->status" />
            </a>
        @empty
            <x-empty icon="truck" :title="__('No active shipments')" :message="__('Shipments you win quotes on will appear here.')" />
        @endforelse
        <div class="mt-3">{{ $assigned->onEachSide(1)->links() }}</div>
    </div>

    <div>
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-faint">{{ __('My quotes') }}</h2>
        @forelse ($myQuotes as $quote)
            <a href="{{ route('agent.shipping-requests.show', $quote->shippingRequest) }}" class="glass glass-hover mb-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-5">
                <div>
                    <p class="font-semibold text-strong">{{ $quote->shippingRequest->reference }}</p>
                    <p class="text-sm text-muted">{{ money($quote->price, $quote->currency) }} &middot; {{ __('ETA') }} {{ $quote->eta_days }} {{ __('days') }}</p>
                </div>
                <x-status-badge :status="$quote->status" />
            </a>
        @empty
            <x-empty icon="truck" :title="__('No quotes submitted yet')" />
        @endforelse
        <div class="mt-3">{{ $myQuotes->onEachSide(1)->links() }}</div>
    </div>
</div>
@endsection
