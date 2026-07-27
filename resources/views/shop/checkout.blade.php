@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'Checkout · '.config('platform.name'))
@section('page-title', __('Checkout'))

@php
    $esimLines = $lines->filter(fn ($l) => $l['variant']->product->type === \App\Enums\ShopProductType::Esim);
@endphp

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6" x-data="{ source: 'wallet' }">
    <h1 class="text-2xl font-bold text-strong">{{ __('Checkout') }}</h1>

    <form method="POST" action="{{ route('shop.checkout.store') }}" class="mt-6 grid gap-6 lg:grid-cols-3">
        @csrf
        <div class="space-y-6 lg:col-span-2">
            <x-glass-card>
                <h3 class="font-semibold text-strong">{{ __('Delivery') }}</h3>
                <p class="mt-1 text-sm text-muted">{{ __('Your codes are shown instantly on the order page and emailed here.') }}</p>
                <div class="mt-3"><label class="label">{{ __('Email') }}</label><input name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required class="field"></div>
            </x-glass-card>

            @if ($esimLines->isNotEmpty())
                <x-glass-card>
                    <h3 class="font-semibold text-strong">{{ __('Before you pay: eSIM plans') }}</h3>
                    <ul class="mt-3 space-y-2 text-sm text-muted">
                        @foreach ($esimLines as $line)
                            <li class="flex items-start gap-2">
                                <x-icon name="sim" class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" />
                                <span>{{ $line['variant']->product->name }} ({{ $line['variant']->name }}): {{ __(esim_activation_policy_label($line['variant']->activation_policy)) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <label class="mt-4 flex items-start gap-2.5 text-sm text-body">
                        <input type="checkbox" name="esim_device_confirmed" value="1" required class="mt-0.5 h-4 w-4 rounded border-app text-brand-600 focus:ring-brand-500">
                        <span>{{ __('I\'ve confirmed my device supports eSIM (') }}<a href="{{ route('esim.compatibility.index') }}" target="_blank" class="font-semibold text-brand-500 hover:text-brand-600">{{ __('check here') }}</a>{{ __(') and understand eSIM plans cannot be refunded once installed.') }}</span>
                    </label>
                </x-glass-card>
            @endif

            <x-glass-card>
                <h3 class="font-semibold text-strong">{{ __('Payment') }}</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_source" value="wallet" x-model="source" class="peer sr-only">
                        <div class="rounded-2xl border border-app surface p-4 peer-checked:border-brand-400 peer-checked:bg-slate-500/10">
                            <div class="flex items-center gap-3"><x-icon name="wallet" class="h-5 w-5 text-brand-400" /><span class="font-medium text-strong">{{ __('Wallet') }}</span></div>
                            <p class="mt-1 text-xs text-muted">{{ disp($wallet->balance) }} {{ __('available') }}</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_source" value="direct" x-model="source" class="peer sr-only">
                        <div class="rounded-2xl border border-app surface p-4 peer-checked:border-brand-400 peer-checked:bg-slate-500/10">
                            <div class="flex items-center gap-3"><x-icon name="card" class="h-5 w-5 text-brand-400" /><span class="font-medium text-strong">{{ __('Pay directly') }}</span></div>
                            <p class="mt-1 text-xs text-muted">{{ __('MoMo, card or crypto') }}</p>
                        </div>
                    </label>
                </div>
                <div x-show="source === 'direct'" x-cloak class="mt-4">
                    <label class="label">{{ __('Payment method') }}</label>
                    <select name="payment_method_id" class="field">
                        @forelse ($methods as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@empty<option value="">{{ __('No automated methods') }}</option>@endforelse
                    </select>
                </div>
            </x-glass-card>
        </div>

        <div>
            <x-glass-card>
                <h3 class="font-semibold text-strong">{{ __('Order summary') }}</h3>
                <div class="mt-4 space-y-2 text-sm">
                    @foreach ($lines as $line)
                        <div class="flex justify-between gap-2"><span class="text-muted">{{ \Illuminate\Support\Str::limit($line['variant']->product->name.' ('.$line['variant']->name.') ×'.$line['qty'], 30) }}</span><span class="text-strong">{{ disp($line['line_total']) }}</span></div>
                    @endforeach
                    <div class="flex justify-between border-t border-app pt-2 text-base font-bold"><span class="text-strong">{{ __('Total') }}</span><span class="text-strong">{{ disp($subtotal) }}</span></div>
                </div>
                <button class="btn btn-primary mt-5 w-full">{{ __('Pay') }} {{ disp($subtotal) }}</button>
                <p class="mt-2 text-center text-xs text-faint">
                    {{ $esimLines->isNotEmpty() ? __('Most items deliver instantly; eSIMs are provisioned as soon as possible after payment') : __('Digital goods · delivered instantly') }}
                </p>
            </x-glass-card>
        </div>
    </form>
</div>
@endsection
