@extends('layouts.app')
@section('page-title', __('Track Shipment'))

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-header :title="__('Track Shipment')" :subtitle="__('Enter your shipping request reference or tracking number.')" />

    <form method="GET" class="mb-8">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
            <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('e.g. PB-SHR-XXXXXXXX or TRK-XXXXXXX') }}" class="field pl-11" autofocus>
        </div>
        <button class="btn btn-primary mt-3 w-full">{{ __('Track') }}</button>
    </form>

    @if ($q !== '' && ! $shipment)
        <x-empty icon="search" :title="__('No matching shipment found')" :message="__('Double-check the reference or tracking number, or view all your shipping requests.')">
            <a href="{{ route('shipping-requests.index') }}" class="btn btn-ghost">{{ __('View My Shipping Requests') }}</a>
        </x-empty>
    @elseif ($shipment)
        @php
            $steps = ['awaiting_quotes', 'quote_received', 'accepted', 'awaiting_pickup', 'in_transit', 'delivered'];
            $currentIndex = array_search($shipment->status->value, $steps, true);
        @endphp
        <x-glass-card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold text-strong">{{ $shipment->reference }}</p>
                    <p class="text-sm text-muted">{{ $shipment->origin_city }} ({{ $shipment->origin_country }}) &rarr; {{ $shipment->destination_city }} ({{ $shipment->destination_country }})</p>
                </div>
                <x-status-badge :status="$shipment->status" />
            </div>

            @if (in_array($shipment->status->value, ['cancelled', 'disputed'], true))
                <p class="mt-4 text-sm text-muted">{{ __('This shipment is :status.', ['status' => strtolower($shipment->status->label())]) }}</p>
            @else
                <div class="mt-6 flex items-center">
                    @foreach ($steps as $i => $step)
                        <div class="flex flex-1 flex-col items-center text-center">
                            <span class="grid h-8 w-8 place-items-center rounded-full text-xs font-bold {{ $currentIndex !== false && $i <= $currentIndex ? 'bg-brand-600 text-white' : 'surface-2 text-faint' }}">
                                @if ($currentIndex !== false && $i < $currentIndex)<x-icon name="check" class="h-4 w-4" />@else{{ $i + 1 }}@endif
                            </span>
                            <span class="mt-1.5 text-[10px] font-medium text-muted">{{ \App\Enums\ShippingRequestStatus::from($step)->label() }}</span>
                        </div>
                        @if (! $loop->last)
                            <span class="mx-1 h-0.5 flex-1 {{ $currentIndex !== false && $i < $currentIndex ? 'bg-brand-600' : 'surface-2' }}"></span>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($shipment->tracking_number)
                <p class="mt-6 border-t border-app pt-4 text-sm text-body">{{ __('Tracking number') }}: <span class="font-mono font-semibold text-strong">{{ $shipment->tracking_number }}</span></p>
            @endif
            @if ($shipment->acceptedQuote?->agent)
                <p class="mt-2 text-sm text-body">{{ __('Carrier') }}: <span class="font-semibold text-strong">{{ $shipment->acceptedQuote->agent->business_name }}</span></p>
            @endif

            <a href="{{ route('shipping-requests.show', $shipment) }}" class="btn btn-ghost mt-6 w-full text-sm">{{ __('View full details') }}</a>
        </x-glass-card>
    @endif
</div>
@endsection
