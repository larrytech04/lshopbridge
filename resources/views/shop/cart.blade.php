@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'Your cart · '.config('platform.name'))
@section('page-title', __('Cart'))

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <h1 class="text-2xl font-bold text-strong">{{ __('Your cart') }}</h1>

    @if ($lines->isEmpty())
        <div class="glass mx-auto mt-10 max-w-md overflow-hidden rounded-3xl p-10 text-center">
            <x-empty-cart img="h-44" src="shop cart.png" drops class="slide-in-side" />
            <h2 class="mt-4 text-lg font-bold text-strong">{{ __('Your cart is empty') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ __('Browse the shop and add some digital products.') }}</p>
            <a href="{{ route('shop.index') }}" class="btn btn-primary mt-5">{{ __('Go to shop') }}</a>
        </div>
    @else
        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="space-y-3 lg:col-span-2">
                @foreach ($lines as $line)
                    @php $v = $line['variant']; $p = $v->product;
                        $icon = ['giftcard'=>'giftcard','esim'=>'sim','vpn'=>'shield','gaming'=>'gamepad','streaming'=>'play'][$p->type] ?? 'bag'; @endphp
                    <div class="glass flex items-center gap-4 rounded-2xl p-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl surface text-brand-400"><x-icon :name="$icon" class="h-6 w-6" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-strong">{{ $p->name }}</p>
                            <p class="text-sm text-muted">{{ $v->name }} · {{ disp($v->price) }}</p>
                        </div>
                        <form method="POST" action="{{ route('cart.update', $v->id) }}" class="flex items-center gap-1">
                            @csrf @method('PATCH')
                            <input type="number" name="quantity" value="{{ $line['qty'] }}" min="0" max="20" class="field w-16 py-1.5 text-center" onchange="this.form.submit()">
                        </form>
                        <p class="hidden w-28 text-right font-semibold text-strong sm:block">{{ disp($line['line_total']) }}</p>
                        <form method="POST" action="{{ route('cart.remove', $v->id) }}">@csrf @method('DELETE')
                            <button class="grid h-9 w-9 place-items-center rounded-xl text-rose-400 hover:surface-2"><x-icon name="trash" class="h-4 w-4" /></button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div>
                <x-glass-card>
                    <h3 class="font-semibold text-strong">{{ __('Summary') }}</h3>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-muted">{{ __('Subtotal') }}</span><span class="font-semibold text-strong">{{ disp($subtotal) }}</span></div>
                        <div class="flex justify-between"><span class="text-muted">{{ __('Fees') }}</span><span class="text-strong">{{ disp(0) }}</span></div>
                        <div class="flex justify-between border-t border-app pt-2 text-base font-bold"><span class="text-strong">{{ __('Total') }}</span><span class="text-strong">{{ disp($subtotal) }}</span></div>
                    </div>
                    <a href="{{ route('shop.checkout') }}" class="btn btn-primary mt-5 w-full">{{ __('Checkout') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
                    <a href="{{ route('shop.index') }}" class="btn btn-ghost mt-2 w-full">{{ __('Continue shopping') }}</a>
                </x-glass-card>
            </div>
        </div>
    @endif
</div>
@endsection
