@extends('layouts.app')
@section('page-title', $shippingRequest->reference)

@section('content')
<div class="mx-auto max-w-4xl">
    <x-page-header :title="$shippingRequest->reference" :subtitle="$shippingRequest->origin_city.' ('.$shippingRequest->origin_country.') → '.$shippingRequest->destination_city.' ('.$shippingRequest->destination_country.')'">
        <x-slot:actions>
            <x-status-badge :status="$shippingRequest->status" />
            @if ($shippingRequest->status->isCancellable())
                <form method="POST" action="{{ route('shipping-requests.cancel', $shippingRequest) }}" onsubmit="return confirm('{{ __('Cancel this shipping request?') }}')">
                    @csrf
                    <button class="btn btn-ghost text-xs text-rose-400">{{ __('Cancel') }}</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-faint">
                {{ __('Quotes') }} ({{ $shippingRequest->quotes->whereIn('status', [\App\Enums\ShippingQuoteStatus::Pending, \App\Enums\ShippingQuoteStatus::Accepted])->count() }})
            </h2>

            @if ($shippingRequest->quotes->isEmpty())
                <x-empty icon="truck" :title="__('No quotes yet')" :message="__('Verified agents will review your request and send quotes shortly.')" />
            @else
                @foreach ($shippingRequest->quotes as $quote)
                    <div class="glass rounded-2xl p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-strong">{{ $quote->agent->business_name }}</p>
                                <p class="text-sm text-muted">{{ __('ETA') }}: {{ $quote->eta_days }} {{ __('days') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-strong">{{ money($quote->price, $quote->currency) }}</p>
                                <x-status-badge :status="$quote->status" />
                            </div>
                        </div>
                        @if ($quote->notes)<p class="mt-3 text-sm text-body">{{ $quote->notes }}</p>@endif
                        @if ($quote->status->value === 'pending' && $shippingRequest->status->value === 'quote_received')
                            <form method="POST" action="{{ route('shipping-requests.quotes.accept', [$shippingRequest, $quote]) }}" class="mt-4" onsubmit="return confirm('{{ __('Accept this quote? Other quotes will be automatically declined.') }}')">
                                @csrf
                                <button class="btn btn-primary w-full text-sm">{{ __('Accept this quote') }}</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        <div class="space-y-4">
            <x-glass-card>
                <h3 class="font-semibold text-strong">{{ __('Package') }}</h3>
                <p class="mt-2 text-sm text-body">{{ $shippingRequest->package_description }}</p>
                @if ($shippingRequest->package_weight_kg)<p class="mt-1 text-xs text-muted">{{ __('Weight') }}: {{ $shippingRequest->package_weight_kg }} kg</p>@endif
                @if ($shippingRequest->package_value)<p class="text-xs text-muted">{{ __('Declared value') }}: {{ money($shippingRequest->package_value, $shippingRequest->package_currency) }}</p>@endif
                @if ($shippingRequest->notes)<p class="mt-3 border-t border-app pt-3 text-xs text-muted">{{ $shippingRequest->notes }}</p>@endif
            </x-glass-card>

            @if (! empty($shippingRequest->documents))
                <x-glass-card>
                    <h3 class="font-semibold text-strong">{{ __('Documents') }}</h3>
                    <div class="mt-2 space-y-1.5">
                        @foreach ($shippingRequest->documents as $i => $doc)
                            <a href="{{ route('shipping-requests.documents.show', [$shippingRequest, $i]) }}" class="flex items-center gap-2 text-sm text-brand-400 hover:text-brand-300">
                                <x-icon name="doc" class="h-4 w-4" /> {{ __('Document') }} {{ $i + 1 }}
                            </a>
                        @endforeach
                    </div>
                </x-glass-card>
            @endif

            @if ($shippingRequest->tracking_number)
                <x-glass-card>
                    <h3 class="font-semibold text-strong">{{ __('Tracking') }}</h3>
                    <p class="mt-2 font-mono text-sm text-body">{{ $shippingRequest->tracking_number }}</p>
                </x-glass-card>
            @endif
        </div>
    </div>
</div>
@endsection
