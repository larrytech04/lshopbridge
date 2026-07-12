@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'My orders · '.config('platform.name'))
@section('page-title', __('My orders'))

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-strong">{{ __('My orders') }}</h1>
        <a href="{{ route('shop.index') }}" class="btn btn-ghost text-sm"><x-icon name="bag" class="h-4 w-4" /> {{ __('Shop') }}</a>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($orders as $o)
            <a href="{{ route('shop.orders.show', $o) }}" class="glass glass-hover flex items-center justify-between rounded-2xl p-4">
                <div class="flex items-center gap-4">
                    <span class="grid h-11 w-11 place-items-center rounded-xl surface text-brand-400"><x-icon name="receipt" class="h-5 w-5" /></span>
                    <div>
                        <p class="font-semibold text-strong">{{ disp($o->total) }} · {{ $o->items_count ?? $o->items->count() }} item(s)</p>
                        <p class="text-xs text-faint">{{ $o->reference }} · {{ $o->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                <x-status-badge :status="$o->status" />
            </a>
        @empty
            <x-empty icon="receipt" title="{{ __('No orders yet') }}" message="Your digital purchases will appear here.">
                <a href="{{ route('shop.index') }}" class="btn btn-primary mt-4">{{ __('Browse the shop') }}</a>
            </x-empty>
        @endforelse
    </div>
    <div class="mt-8">{{ $orders->links() }}</div>
</div>
@endsection
