@extends('layouts.app')
@section('page-title', __('Shipping Requests'))

@section('content')
<x-page-header :title="__('Shipping Requests')" :subtitle="__('Post a shipment and let verified agents quote on it.')">
    <x-slot:actions>
        <a href="{{ route('shipping-requests.create') }}" class="btn btn-primary text-sm"><x-icon name="plus" class="h-4 w-4" /> {{ __('New Request') }}</a>
    </x-slot:actions>
</x-page-header>

@if ($requests->isEmpty())
    <x-empty icon="truck" :title="__('No shipping requests yet')" :message="__('Create a request with your origin, destination and package details — verified agents will send you quotes.')">
        <a href="{{ route('shipping-requests.create') }}" class="btn btn-primary">{{ __('Create a Shipping Request') }}</a>
    </x-empty>
@else
    <div class="space-y-4">
        @foreach ($requests as $r)
            <a href="{{ route('shipping-requests.show', $r) }}" class="glass glass-hover flex flex-wrap items-center justify-between gap-3 rounded-2xl p-5">
                <div>
                    <p class="font-semibold text-strong">{{ $r->reference }}</p>
                    <p class="text-sm text-muted">{{ $r->origin_city }} ({{ $r->origin_country }}) &rarr; {{ $r->destination_city }} ({{ $r->destination_country }})</p>
                    <p class="mt-1 text-xs text-faint">{{ $r->package_description }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if ($r->quotes_count > 0)
                        <span class="pill surface border border-app text-body">{{ trans_choice(':count quote|:count quotes', $r->quotes_count, ['count' => $r->quotes_count]) }}</span>
                    @endif
                    <x-status-badge :status="$r->status" />
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-8">{{ $requests->links() }}</div>
@endif
@endsection
