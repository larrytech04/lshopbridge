@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'Order '.$order->reference.' · '.config('platform.name'))
@section('page-title', __('Order'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <a href="{{ route('shop.orders.index') }}" class="text-sm text-brand-400 hover:text-brand-300">← {{ __('Order history') }}</a>

    <div class="card-solid mt-4 overflow-hidden rounded-3xl border border-app shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 pt-4 sm:px-6">
            <span class="text-xs text-faint"><span class="text-slate-400">{{ $order->created_at->format('d/m/Y') }}</span> &nbsp;|&nbsp; <span class="font-mono">{{ $order->reference }}</span></span>
        </div>
        <div class="px-5 pb-4 pt-2 sm:px-6">
            <x-status-badge :status="$order->status" class="text-[10px] font-bold uppercase tracking-wide" />
        </div>

        @if ($order->status->value === 'fulfilled')
            <div class="mx-5 mb-4 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-700 sm:mx-6">
                <x-icon name="check-circle" class="mr-1 inline h-4 w-4" /> {{ __('Delivered! Your codes are below and were emailed to :email.', ['email' => $order->email]) }}
            </div>
        @endif

        @include('shop.orders._detail', ['order' => $order])
    </div>

    <p class="mt-6 text-center text-xs text-faint">{{ __('Need help with this order?') }} <a href="{{ route('disputes.index') }}" class="text-brand-400">{{ __('Contact support') }}</a></p>
</div>
@endsection
