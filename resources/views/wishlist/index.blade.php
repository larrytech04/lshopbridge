@extends('layouts.app')
@section('title', 'Wishlist · '.config('platform.name'))
@section('page-title', __('Wishlist'))

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">{{ __('Wishlist') }}</h1>
            <p class="mt-1 text-sm text-muted">{{ __('Products you have saved for later.') }}</p>
        </div>
        <a href="{{ route('shop.index') }}" class="btn btn-ghost text-sm"><x-icon name="bag" class="h-4 w-4" /> {{ __('Marketplace') }}</a>
    </div>

    @if ($wishlists->isEmpty())
        <div class="mt-8">
            <x-empty icon="heart" :title="__('Your wishlist is empty')" :message="__('Save products while you browse the Marketplace and they will show up here.')">
                <a href="{{ route('shop.index') }}" class="btn btn-primary">{{ __('Browse the Marketplace') }}</a>
            </x-empty>
        </div>
    @else
        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
            @foreach ($wishlists as $wishlist)
                @php $product = $wishlist->product; @endphp
                @if ($product)
                    <div class="glass relative flex flex-col rounded-2xl p-5">
                        <form method="POST" action="{{ route('wishlist.destroy', $product) }}" class="absolute right-3 top-3 z-10">
                            @csrf
                            @method('DELETE')
                            <button type="submit" aria-label="{{ __('Remove from wishlist') }}" class="grid h-8 w-8 place-items-center rounded-full surface-2 text-rose-500 hover:bg-rose-500/15">
                                <x-icon name="heart" class="h-4 w-4" />
                            </button>
                        </form>
                        <a href="{{ route('shop.show', $product) }}" class="group flex flex-1 flex-col">
                            @php $img = $product->image_path ?? $product->logo_path; @endphp
                            @if ($img)
                                <span class="grid h-20 w-full place-items-center overflow-hidden rounded-xl bg-white ring-1 ring-app">
                                    <img src="{{ Storage::url($img) }}" class="max-h-16 w-auto object-contain" alt="{{ $product->name }}" loading="lazy">
                                </span>
                            @endif
                            <p class="{{ $img ? 'mt-4' : '' }} text-xs font-medium text-faint">{{ $product->brand ?? $product->category?->name }}</p>
                            <h3 class="line-clamp-1 font-semibold text-strong group-hover:text-brand-400">{{ $product->name }}</h3>
                            <p class="mt-1 line-clamp-2 flex-1 text-sm text-muted">{{ $product->summary }}</p>
                            <div class="mt-4 flex items-center justify-between border-t border-app pt-3">
                                <span class="text-sm font-bold text-strong">{{ $product->fromPrice() ? disp($product->fromPrice()->price) : '-' }}</span>
                                <span class="inline-flex items-center gap-1 rounded-xl bg-slate-600/15 px-3 py-1.5 text-sm font-semibold text-brand-400 group-hover:bg-brand-600 group-hover:text-white transition">{{ __('View') }} <x-icon name="arrow-right" class="h-3.5 w-3.5" /></span>
                            </div>
                        </a>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="mt-8">{{ $wishlists->links() }}</div>
    @endif
</div>
@endsection
