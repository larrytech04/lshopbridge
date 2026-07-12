@extends('layouts.admin')
@section('page-title', 'Order '.$order->reference)

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('admin.shop.orders.index') }}" class="text-sm text-brand-400 hover:text-brand-300">← Shop orders</a>

    <x-glass-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-faint">{{ $order->reference }}</p>
                <p class="mt-1 text-2xl font-bold text-strong">{{ money($order->total, $order->currency) }}</p>
                <p class="text-sm text-muted">{{ $order->user->name }} · {{ $order->email }} · {{ $order->created_at->format('M j, Y H:i') }}</p>
            </div>
            <x-status-badge :status="$order->status" class="text-sm" />
        </div>
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
        @unless (in_array($order->status->value, ['fulfilled','refunded']))
            <form method="POST" action="{{ route('admin.shop.orders.fulfill', $order) }}">@csrf<button class="btn btn-success"><x-icon name="check" class="h-4 w-4" /> Fulfill / re-deliver</button></form>
        @endunless
        @unless ($order->status->value === 'refunded')
            <form method="POST" action="{{ route('admin.shop.orders.refund', $order) }}" class="flex gap-2">@csrf<input name="reason" class="field max-w-xs" placeholder="Refund reason"><button class="btn btn-danger">Refund to wallet</button></form>
        @endunless
    </div>
</div>
@endsection
