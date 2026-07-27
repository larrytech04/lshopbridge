@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@php $digitalOnly = $digitalOnly ?? false; @endphp
@section('title', ($digitalOnly ? 'Digital purchases' : 'My orders').' · '.config('platform.name'))
@section('page-title', $digitalOnly ? __('Digital Purchases') : __('My Orders'))

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">{{ $digitalOnly ? __('Digital Purchases') : __('My Orders') }}</h1>
            @if ($digitalOnly)
                <p class="mt-1 text-sm text-muted">{{ __('Gift cards, eSIMs, top-ups & other instantly delivered purchases.') }}</p>
            @endif
        </div>
        @if ($digitalOnly)
            <a href="{{ route('shop.orders.index') }}" class="btn btn-ghost text-sm"><x-icon name="receipt" class="h-4 w-4" /> {{ __('All Orders') }}</a>
        @else
            <a href="{{ route('shop.index') }}" class="btn btn-ghost text-sm"><x-icon name="bag" class="h-4 w-4" /> {{ __('Marketplace') }}</a>
        @endif
    </div>

    <form method="GET" class="mt-6">
        <label class="mb-2 block text-sm font-semibold text-strong">{{ __('Enter your order id') }}</label>
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-[240px] flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Search by order id…') }}" class="field pl-11">
            </div>
            <label class="flex shrink-0 items-center gap-2 text-sm text-body">
                <input type="checkbox" name="expired" value="1" @checked(request()->boolean('expired')) onchange="this.form.requestSubmit()" class="h-4 w-4 rounded border-app text-brand-600 focus:ring-brand-500">
                {{ __('Expired orders') }}
            </label>
        </div>
    </form>

    <div class="mt-6 space-y-3">
        @forelse ($orders as $o)
            <div x-data="{ open: false }" class="card-solid overflow-hidden rounded-3xl border border-app shadow-sm">
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left sm:px-6">
                    <span class="text-xs text-faint"><span class="text-slate-400">{{ $o->created_at->format('d/m/Y') }}</span> &nbsp;|&nbsp; <span class="font-mono">{{ $o->reference }}</span></span>
                    <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-faint transition-transform duration-200" ::class="open ? 'rotate-180' : ''" />
                </button>
                <div class="px-5 pb-4 sm:px-6">
                    <x-status-badge :status="$o->status" class="text-[10px] font-bold uppercase tracking-wide" />
                    <p class="mt-2.5 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-strong">
                        @foreach ($o->items as $i)
                            @php $flag = $i->product?->region ? \App\Models\Country::where('iso2', $i->product->region)->orWhere('name', $i->product->region)->value('flag_emoji') : null; @endphp
                            <span class="font-semibold">{{ $i->quantity }}</span><span class="text-faint">x</span><span>{{ $i->name }}</span>
                            <span class="font-semibold">{{ disp($i->unit_price) }}</span>
                            @if ($flag)<span>{{ $flag }}</span>@endif
                            @if ($i->product?->region)<span class="text-faint">{{ $i->product->region }}</span>@endif
                        @endforeach
                    </p>
                </div>
                <div x-show="open" x-collapse style="display:none">
                    @include('shop.orders._detail', ['order' => $o])
                </div>
            </div>
        @empty
            @if ($digitalOnly)
                <x-empty icon="download" title="{{ __('No digital purchases yet') }}" message="{{ __('Gift cards, eSIMs and other instant purchases will appear here.') }}">
                    <a href="{{ route('shop.index') }}" class="btn btn-primary">{{ __('Browse the Marketplace') }}</a>
                </x-empty>
            @else
                <x-empty icon="receipt" title="{{ __('No orders yet') }}" message="{{ __('Your purchases will appear here.') }}">
                    <a href="{{ route('shop.index') }}" class="btn btn-primary">{{ __('Browse the Marketplace') }}</a>
                </x-empty>
            @endif
        @endforelse
    </div>
    <div class="mt-8">{{ $orders->links() }}</div>
</div>
@endsection
