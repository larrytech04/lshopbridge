@extends('layouts.app')
@section('title', 'My eSIMs · '.config('platform.name'))
@section('page-title', __('My eSIMs'))

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">{{ __('My eSIMs') }}</h1>
            <p class="mt-1 text-sm text-muted">{{ __('Install, track data, and top up your travel eSIMs.') }}</p>
        </div>
        <a href="{{ route('shop.orders.digital') }}" class="btn btn-ghost text-sm"><x-icon name="receipt" class="h-4 w-4" /> {{ __('Digital Purchases') }}</a>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($provisionings as $p)
            <a href="{{ route('esim.mine.show', $p) }}" class="card-solid flex items-center justify-between gap-3 rounded-3xl border border-app p-5 shadow-sm hover:surface-2">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl surface-2 text-muted"><x-icon name="sim" class="h-5 w-5" /></span>
                    <div>
                        <p class="font-semibold text-strong">{{ $p->orderItem->name }}</p>
                        <p class="text-xs text-faint">{{ $p->orderItem->order->reference }} &middot; {{ $p->created_at->format('M j, Y') }}</p>
                    </div>
                </div>
                <x-status-badge :status="$p->status" />
            </a>
        @empty
            <x-empty icon="sim" title="{{ __('No eSIMs yet') }}" message="{{ __('eSIMs you purchase will show up here for install and data tracking.') }}">
                <x-slot:action>
                    <a href="{{ route('shop.index') }}" class="btn btn-primary">{{ __('Browse the marketplace') }}</a>
                </x-slot:action>
            </x-empty>
        @endforelse
    </div>

    <div class="mt-6">{{ $provisionings->links() }}</div>
</div>
@endsection
