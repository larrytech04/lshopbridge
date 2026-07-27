@extends('layouts.admin')
@section('page-title', 'Order '.$order->reference)

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('admin.shop.orders.index') }}" class="text-sm text-brand-400 hover:text-brand-600">← Shop orders</a>

    <x-glass-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-faint">{{ $order->reference }}</p>
                <p class="mt-1 text-2xl font-bold text-strong">{{ money($order->total, $order->currency) }}</p>
                <p class="text-sm text-muted">{{ $order->user->name }} · {{ $order->email }} · {{ $order->created_at->format('M j, Y H:i') }}</p>
            </div>
            <x-status-badge :status="$order->status" class="text-sm" />
        </div>
        @if ($order->risk_flagged)
            <p class="mt-3 rounded-lg bg-rose-500/10 px-3 py-2 text-xs text-rose-700">Flagged for review: {{ $order->manual_review_reason }}</p>
        @endif
        @if ($order->tracking_number)
            <p class="mt-3 rounded-lg surface-2 px-3 py-2 text-xs text-body">Tracking: {{ $order->tracking_number }} @if($order->carrier) · {{ $order->carrier }} @endif</p>
        @endif
    </x-glass-card>

    <div class="space-y-3">
        @foreach ($order->items as $item)
            <x-glass-card>
                <div class="flex items-center justify-between"><p class="font-semibold text-strong">{{ $item->name }}</p><span class="text-sm text-muted">×{{ $item->quantity }} · {{ money($item->line_total, $order->currency) }}</span></div>
                @if (! empty($item->delivered))
                    <div class="mt-3 space-y-1">
                        @foreach ($item->delivered as $code)<code class="block break-all rounded-lg surface px-3 py-2 font-mono text-xs text-strong">{{ $code }}</code>@endforeach
                    </div>
                @endif
            </x-glass-card>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-3">
        @unless (in_array($order->status->value, ['fulfilled','delivered','refunded','cancelled']))
            <form method="POST" action="{{ route('admin.shop.orders.fulfill', $order) }}">@csrf<button class="btn btn-success"><x-icon name="check" class="h-4 w-4" /> Fulfill / re-deliver</button></form>
        @endunless
        @if ($order->refundableAmount() > 0)
            <form method="POST" action="{{ route('admin.shop.orders.refund', $order) }}" class="flex gap-2">@csrf<input type="hidden" name="amount" value="{{ $order->refundableAmount() }}"><input name="reason" class="field max-w-xs" placeholder="Refund reason" required><button class="btn btn-danger">Refund {{ money($order->refundableAmount(), $order->currency) }}</button></form>
        @endif
    </div>

    @if ($order->refunds->count())
        <x-glass-card>
            <p class="text-xs font-semibold uppercase text-faint">Refund history</p>
            <div class="mt-2 space-y-1.5">
                @foreach ($order->refunds as $refund)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg surface-2 px-3 py-2 text-xs">
                        <span class="text-body">{{ money($refund->amount, $refund->currency) }} · {{ $refund->reason }}</span>
                        <span class="flex items-center gap-2">
                            <span class="text-faint">{{ $refund->status }} · {{ ($refund->completed_at ?? $refund->created_at)?->format('M j, Y') }}</span>
                            @if ($refund->status === 'requested')
                                <form method="POST" action="{{ route('admin.shop.orders.refunds.reject', [$order, $refund]) }}" class="flex items-center gap-1.5"
                                      onsubmit="const r = prompt('Reason for declining this refund request?'); if (!r) return false; this.reason.value = r;">
                                    @csrf
                                    <input type="hidden" name="reason">
                                    <button type="submit" class="text-rose-400 hover:text-rose-300">Decline</button>
                                </form>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </x-glass-card>
    @endif

    @if ($order->events->count())
        <x-glass-card>
            <p class="text-xs font-semibold uppercase text-faint">Order timeline</p>
            <div class="mu-timeline mt-2">
                @foreach ($order->events as $event)
                    <div class="mu-timeline-item">
                        <span class="mu-timeline-dot"></span>
                        <p class="text-sm font-medium capitalize text-body">{{ str_replace('_', ' ', $event->event) }}</p>
                        @if ($event->reason)<p class="text-xs text-muted">{{ $event->reason }}</p>@endif
                        <p class="text-xs text-faint">{{ $event->actor?->name ?? 'System' }} · {{ $event->created_at->format('M j, Y g:ia') }}</p>
                    </div>
                @endforeach
            </div>
        </x-glass-card>
    @endif
</div>
@endsection
