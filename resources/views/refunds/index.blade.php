@extends('layouts.app')
@section('page-title', __('Disputes & Refunds'))

@section('content')
<x-page-header :title="__('Disputes & Refunds')" :subtitle="__('Request a refund on an eligible order within :days days of payment.', ['days' => $windowDays])" />

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-faint">{{ __('Your requests') }}</h2>
        @forelse ($requests as $refund)
            <div class="glass rounded-2xl p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-strong">{{ $refund->order?->reference }}</p>
                        <p class="text-sm text-muted">{{ disp($refund->amount) }} &middot; {{ $refund->created_at->format('M j, Y') }}</p>
                    </div>
                    <x-status-badge :status="$refund->status" />
                </div>
                <p class="mt-3 text-sm text-body">{{ $refund->reason }}</p>
            </div>
        @empty
            <x-empty icon="refresh" title="{{ __('No refund requests yet') }}" message="{{ __('Refund requests you submit will appear here.') }}" />
        @endforelse
        <div class="mt-4">{{ $requests->links() }}</div>
    </div>

    <div>
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Request a refund') }}</h3>
            @if ($eligibleOrders->isEmpty())
                <p class="mt-4 text-sm text-muted">{{ __('No orders are currently eligible for a refund. Orders must be paid, within the eligibility window, and have no pending request.') }}</p>
            @else
                <form method="POST" action="{{ route('refunds.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="label">{{ __('Order') }}</label>
                        <select name="shop_order_id" required class="field">
                            @foreach ($eligibleOrders as $order)
                                <option value="{{ $order->id }}">{{ $order->reference }} &middot; {{ disp($order->refundableAmount()) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">{{ __('Reason') }}</label>
                        <textarea name="reason" required rows="4" class="field" placeholder="{{ __('Tell us what went wrong…') }}"></textarea>
                    </div>
                    <button class="btn btn-primary w-full">{{ __('Submit request') }}</button>
                </form>
            @endif
            <p class="mt-3 text-xs text-faint">{{ __('Our team reviews every request. Approved refunds are credited back to your wallet.') }}</p>
        </x-glass-card>
    </div>
</div>
@endsection
