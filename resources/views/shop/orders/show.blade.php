@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'Order '.$order->reference.' · '.config('platform.name'))
@section('page-title', __('Order'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <a href="{{ route('shop.orders.index') }}" class="text-sm text-brand-400 hover:text-brand-300">← {{ __('My orders') }}</a>

    <x-glass-card class="mt-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-faint">{{ $order->reference }}</p>
                <p class="mt-1 text-2xl font-bold text-strong">{{ disp($order->total) }}</p>
                <p class="text-sm text-muted">{{ $order->created_at->format('M j, Y H:i') }} · paid via {{ $order->payment_source }}</p>
            </div>
            <x-status-badge :status="$order->status" class="text-sm" />
        </div>
    </x-glass-card>

    @if ($order->status->value === 'fulfilled')
        <div class="mt-4 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 p-4 text-sm text-emerald-200">
            <x-icon name="check-circle" class="mr-1 inline h-4 w-4" /> Delivered! Your codes are below and were emailed to {{ $order->email }}.
        </div>
    @endif

    <div class="mt-6 space-y-4">
        @foreach ($order->items as $item)
            <x-glass-card>
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-strong">{{ $item->name }}</p>
                    <span class="text-sm text-muted">×{{ $item->quantity }} · {{ disp($item->line_total) }}</span>
                </div>
                @if (! empty($item->delivered))
                    <div class="mt-4 space-y-2" x-data>
                        @foreach ($item->delivered as $code)
                            <div class="flex items-center gap-2 rounded-xl border border-app surface px-4 py-3">
                                <code class="flex-1 break-all font-mono text-sm text-strong">{{ $code }}</code>
                                <button type="button" @click="navigator.clipboard.writeText(@js($code)); $el.querySelector('span').textContent='Copied'"
                                        class="shrink-0 rounded-lg bg-slate-600/15 px-2.5 py-1.5 text-xs font-semibold text-brand-400 hover:bg-brand-600 hover:text-white"><span>{{ __('Copy') }}</span></button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-sm text-muted">{{ __('Awaiting delivery…') }}</p>
                @endif
            </x-glass-card>
        @endforeach
    </div>

    <p class="mt-6 text-center text-xs text-faint">{{ __('Need help with this order?') }} <a href="{{ route('disputes.index') }}" class="text-brand-400">{{ __('Contact support') }}</a></p>
</div>
@endsection
