@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', $product->name.' · '.config('platform.name'))
@section('page-title', __('Shop'))

@php
    $img = $product->image_path ?? $product->logo_path;
    $isEsim = $product->type === \App\Enums\ShopProductType::Esim;
    $variants = $product->activeVariants->map(fn($v) => [
        'id'=>$v->id,'name'=>$v->name,'price'=>(float)$v->price,'compare'=>$v->compare_at_price ? (float)$v->compare_at_price : null,'cur'=>$v->currency,
        'data_amount' => $v->data_amount, 'validity_days' => $v->validity_days, 'is_unlimited_data' => (bool) $v->is_unlimited_data,
        'networks' => $v->networks, 'network_speeds' => $v->network_speeds, 'hotspot_supported' => $v->hotspot_supported,
        'topup_supported' => (bool) $v->topup_supported, 'activation_policy' => $v->activation_policy, 'fair_usage_note' => $v->fair_usage_note,
    ]);
    $isGiftCard = $product->category && ($product->category->slug === 'gift-cards' || optional($product->category->parent)->slug === 'gift-cards');
    $coverage = $isEsim ? \App\Models\Country::whereIn('iso2', $product->esim_coverage_countries ?? [])->get() : collect();
@endphp

@section('content')
@if ($isGiftCard)
    @include('partials.giftcard-intro')
@endif

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6"
     x-data="{
        variants: {{ \Illuminate\Support\Js::from($variants) }},
        selected: {{ $product->activeVariants->first()->id ?? 'null' }},
        qty: 1,
        sym: @js(display_currency()['symbol']),
        rate: {{ display_currency()['rate'] }},
        dec: {{ display_currency()['decimals'] }},
        get current() { return this.variants.find(v => v.id === this.selected) || {} },
        money(v) { return this.sym + ' ' + (Number(v||0) * this.rate).toLocaleString(undefined, { maximumFractionDigits: this.dec }) },
     }">
    <a href="{{ route('shop.index') }}" class="text-sm text-brand-400 hover:text-brand-300">← {{ __('Back to shop') }}</a>

    <div class="mt-4 grid gap-8 lg:grid-cols-2">
        {{-- Visual --}}
        <div class="glass relative overflow-hidden rounded-3xl p-8">
            <div class="dotgrid absolute inset-0 opacity-40"></div>
            <div class="relative flex h-full min-h-64 flex-col items-center justify-center text-center">
                @if ($img)
                    <span class="grid h-40 w-40 place-items-center overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-app"><img src="{{ Storage::url($img) }}" class="max-h-32 w-auto object-contain" alt="{{ $product->name }}"></span>
                @else
                    <span class="grid h-24 w-24 place-items-center rounded-3xl bg-brand-600 text-2xl font-extrabold text-white shadow-xl">{{ strtoupper(\Illuminate\Support\Str::substr($product->brand ?? $product->name, 0, 2)) }}</span>
                @endif
                <p class="mt-5 text-lg font-bold text-strong">{{ $product->brand ?? $product->category->name }}</p>
                @if ($product->region)<span class="mt-2 pill surface border border-app text-muted"><x-icon name="globe" class="h-3.5 w-3.5" /> {{ $product->region }}</span>@endif
            </div>
        </div>

        {{-- Buy box --}}
        <div>
            <span class="pill surface border border-app text-brand-400">{{ __($product->category->name) }}</span>
            <h1 class="mt-3 text-2xl font-bold text-strong sm:text-3xl">{{ $product->name }}</h1>
            <p class="mt-2 text-muted">{{ $product->summary }}</p>

            <div class="mt-5 flex items-end gap-3">
                <span class="text-3xl font-extrabold text-strong" x-text="money(current.price)"></span>
                <template x-if="current.compare">
                    <span class="mb-1 text-lg text-faint line-through" x-text="money(current.compare)"></span>
                </template>
            </div>

            <form method="POST" action="{{ route('cart.add') }}" class="mt-6 space-y-5">
                @csrf
                <input type="hidden" name="variant_id" :value="selected">
                <input type="hidden" name="quantity" :value="qty">

                <div>
                    <p class="label">{{ __('Choose a plan') }}</p>
                    @if ($isEsim)
                        <div class="space-y-2">
                            <template x-for="v in variants" :key="v.id">
                                <button type="button" @click="selected = v.id"
                                        class="flex w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition"
                                        :class="selected === v.id ? 'border-brand-400 bg-slate-500/15' : 'border-app surface'">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-strong" x-text="[(v.is_unlimited_data ? '{{ __('Unlimited data') }}' : v.data_amount), v.validity_days ? (v.validity_days + ' {{ __('days') }}') : null].filter(Boolean).join(' · ')"></p>
                                        <p class="text-xs text-muted" x-show="v.topup_supported">{{ __('Top-up available') }}</p>
                                    </div>
                                    <span class="shrink-0 text-sm font-bold text-strong" x-text="money(v.price)"></span>
                                </button>
                            </template>
                        </div>
                    @else
                        <div class="flex flex-wrap gap-2">
                            <template x-for="v in variants" :key="v.id">
                                <button type="button" @click="selected = v.id"
                                        class="rounded-xl border px-4 py-2.5 text-sm font-semibold transition"
                                        :class="selected === v.id ? 'border-brand-400 bg-slate-500/15 text-strong' : 'border-app surface text-body'">
                                    <span x-text="v.name"></span>
                                </button>
                            </template>
                        </div>
                    @endif
                </div>

                @if ($isEsim)
                    <div class="rounded-2xl bg-amber-500/10 p-3 text-xs text-amber-700" x-show="current.activation_policy">
                        <span x-text="current.activation_policy ? '{{ addslashes(__('Activation:')) }} ' : ''"></span>
                        <template x-if="current.activation_policy === 'first_connect'"><span>{{ __('Validity begins the first time your device connects to a supported network.') }}</span></template>
                        <template x-if="current.activation_policy === 'on_install'"><span>{{ __('Validity begins immediately after installation.') }}</span></template>
                        <template x-if="current.activation_policy === 'on_date'"><span>{{ __('Validity begins on the date you select during setup.') }}</span></template>
                        <template x-if="current.activation_policy === 'manual'"><span>{{ __('Requires manual activation, instructions provided after purchase.') }}</span></template>
                    </div>
                @endif

                <div class="flex items-center gap-4">
                    <p class="label mb-0">{{ __('Qty') }}</p>
                    <div class="inline-flex items-center rounded-xl border border-app surface">
                        <button type="button" @click="qty = Math.max(1, qty-1)" class="grid h-9 w-9 place-items-center text-muted hover:text-strong"><x-icon name="minus" class="h-4 w-4" /></button>
                        <span class="w-8 text-center text-sm font-semibold text-strong" x-text="qty"></span>
                        <button type="button" @click="qty = Math.min(20, qty+1)" class="grid h-9 w-9 place-items-center text-muted hover:text-strong"><x-icon name="plus" class="h-4 w-4" /></button>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn btn-ghost flex-1"><x-icon name="cart" class="h-4 w-4" /> {{ __('Add to cart') }}</button>
                    <button type="submit" name="buy_now" value="1" class="btn btn-primary flex-1">{{ __('Buy now') }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
                </div>
            </form>

            @auth
                <form method="POST" action="{{ route($inWishlist ? 'wishlist.destroy' : 'wishlist.store', $product) }}" class="mt-3">
                    @csrf
                    @if ($inWishlist) @method('DELETE') @endif
                    <button type="submit" class="btn btn-ghost w-full text-sm">
                        <x-icon name="heart" class="h-4 w-4 {{ $inWishlist ? 'text-rose-500' : '' }}" />
                        {{ $inWishlist ? __('Remove from wishlist') : __('Save to wishlist') }}
                    </button>
                </form>
            @endauth

            <div class="mt-6 flex items-center gap-4 text-xs text-muted">
                <span class="flex items-center gap-1.5"><x-icon name="bolt" class="h-4 w-4 text-emerald-400" /> {{ __('Instant delivery') }}</span>
                <span class="flex items-center gap-1.5"><x-icon name="shield" class="h-4 w-4 text-emerald-400" /> {{ __('Secure checkout') }}</span>
                <span class="flex items-center gap-1.5"><x-icon name="refresh" class="h-4 w-4 text-emerald-400" /> {{ __('Refund protection') }}</span>
            </div>
        </div>
    </div>

    {{-- eSIM-specific info --}}
    @if ($isEsim)
        <div class="mt-10 grid gap-6 lg:grid-cols-2">
            <x-glass-card>
                <h3 class="font-semibold text-strong">{{ __('Coverage') }}</h3>
                @if ($coverage->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($coverage as $c)
                            <span class="pill surface border border-app text-body">{{ $c->flag_emoji ?? '🌍' }} {{ $c->name }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-sm text-muted">{{ __('Multi-country coverage. See the plan description for included destinations.') }}</p>
                @endif
            </x-glass-card>
            <x-glass-card>
                <h3 class="font-semibold text-strong">{{ __('Before you buy') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-muted">{{ __('Most eSIM-capable phones from 2019 onward support installation via QR code. Confirm your exact device before purchasing: eSIM plans are digital and cannot be returned once installed.') }}</p>
                <a href="{{ route('esim.compatibility.index') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-500 hover:text-brand-600">
                    <x-icon name="sim" class="h-4 w-4" /> {{ __('Check my device compatibility') }}
                </a>
            </x-glass-card>
        </div>
    @endif

    {{-- Details --}}
    <div class="mt-10 grid gap-6 lg:grid-cols-2">
        @if ($product->description)
            <x-glass-card><h3 class="font-semibold text-strong">{{ __('About') }}</h3><p class="mt-3 text-sm leading-relaxed text-muted">{{ $product->description }}</p></x-glass-card>
        @endif
        @if ($product->redeem_instructions)
            <x-glass-card><h3 class="font-semibold text-strong">{{ __('How to redeem') }}</h3><p class="mt-3 text-sm leading-relaxed text-muted">{{ $product->redeem_instructions }}</p></x-glass-card>
        @endif
    </div>

    {{-- Related --}}
    @if ($related->isNotEmpty())
        <h2 class="mt-12 text-xl font-bold text-strong">{{ __('You might also like') }}</h2>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ($related as $product)
                @include('shop._product', ['product' => $product])
            @endforeach
        </div>
    @endif
</div>
@endsection
