@extends('layouts.app')
@section('page-title', $shippingRequest->reference)

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-header :title="$shippingRequest->reference" :subtitle="$shippingRequest->origin_city.' ('.$shippingRequest->origin_country.') → '.$shippingRequest->destination_city.' ('.$shippingRequest->destination_country.')'">
        <x-slot:actions><x-status-badge :status="$shippingRequest->status" /></x-slot:actions>
    </x-page-header>

    <x-glass-card>
        <h3 class="font-semibold text-strong">{{ __('Package') }}</h3>
        <p class="mt-2 text-sm text-body">{{ $shippingRequest->package_description }}</p>
        @if ($shippingRequest->package_weight_kg)<p class="mt-1 text-xs text-muted">{{ __('Weight') }}: {{ $shippingRequest->package_weight_kg }} kg</p>@endif
        @if ($shippingRequest->notes)<p class="mt-3 border-t border-app pt-3 text-xs text-muted">{{ $shippingRequest->notes }}</p>@endif
    </x-glass-card>

    @if ($myQuote)
        <x-glass-card class="mt-6">
            <h3 class="font-semibold text-strong">{{ __('Your quote') }}</h3>
            <p class="mt-2 text-lg font-bold text-strong">{{ money($myQuote->price, $myQuote->currency) }} &middot; {{ __('ETA') }} {{ $myQuote->eta_days }} {{ __('days') }}</p>
            <x-status-badge :status="$myQuote->status" class="mt-2" />
            @if ($myQuote->status->value === 'pending')
                <form method="POST" action="{{ route('agent.shipping-quotes.withdraw', $myQuote) }}" class="mt-4" onsubmit="return confirm('{{ __('Withdraw your quote?') }}')">
                    @csrf
                    <button class="btn btn-ghost text-xs text-rose-400">{{ __('Withdraw quote') }}</button>
                </form>
            @endif
        </x-glass-card>
    @elseif ($shippingRequest->status->isOpenForQuotes())
        <x-glass-card class="mt-6">
            <h3 class="font-semibold text-strong">{{ __('Submit a quote') }}</h3>
            <form method="POST" action="{{ route('agent.shipping-requests.quote', $shippingRequest) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <div><label class="label">{{ __('Price') }} ({{ $shippingRequest->package_currency }})</label><input type="number" step="0.01" min="0.01" name="price" required class="field"></div>
                <div><label class="label">{{ __('ETA (days)') }}</label><input type="number" min="1" name="eta_days" required class="field"></div>
                <div class="sm:col-span-2"><label class="label">{{ __('Notes (optional)') }}</label><textarea name="notes" rows="3" class="field"></textarea></div>
                <div class="sm:col-span-2"><button class="btn btn-primary w-full">{{ __('Submit quote') }}</button></div>
            </form>
        </x-glass-card>
    @endif

    @if ($shippingRequest->acceptedQuote && $shippingRequest->acceptedQuote->agent_id === auth()->user()->agent->id)
        <x-glass-card class="mt-6">
            <h3 class="font-semibold text-strong">{{ __('Update shipment status') }}</h3>
            <form method="POST" action="{{ route('agent.shipping-requests.advance', $shippingRequest) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                @php
                    $next = match ($shippingRequest->status->value) {
                        'accepted' => 'awaiting_pickup',
                        'awaiting_pickup' => 'in_transit',
                        'in_transit' => 'delivered',
                        default => null,
                    };
                @endphp
                @if ($next)
                    <input type="hidden" name="status" value="{{ $next }}">
                    @if ($next === 'in_transit')
                        <div class="sm:col-span-2"><label class="label">{{ __('Tracking number') }}</label><input name="tracking_number" class="field"></div>
                    @endif
                    <div class="sm:col-span-2"><button class="btn btn-primary w-full">{{ __('Mark as :status', ['status' => \App\Enums\ShippingRequestStatus::from($next)->label()]) }}</button></div>
                @else
                    <p class="text-sm text-muted sm:col-span-2">{{ __('This shipment is complete.') }}</p>
                @endif
            </form>
        </x-glass-card>
    @endif
</div>
@endsection
