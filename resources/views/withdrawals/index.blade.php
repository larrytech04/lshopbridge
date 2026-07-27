@extends('layouts.app')
@section('page-title', __('Withdraw Funds'))

@section('content')
<x-page-header :title="__('Withdraw Funds')" :subtitle="__('Available balance: :amount', ['amount' => disp($wallet->availableBalance())])" />

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-faint">{{ __('Your requests') }}</h2>
        @forelse ($withdrawals as $w)
            <div class="glass rounded-2xl p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-strong">{{ $w->reference }}</p>
                        <p class="text-sm text-muted">{{ __('To') }} {{ $w->destination_label }} &middot; {{ $w->created_at->format('M j, Y') }}</p>
                    </div>
                    <x-status-badge :status="$w->status" />
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-app pt-3 text-sm">
                    <span class="text-muted">{{ __('Amount') }} <span class="font-semibold text-strong">{{ disp($w->amount) }}</span> &middot; {{ __('Fee') }} {{ disp($w->fee) }} &middot; {{ __('Net') }} <span class="font-semibold text-strong">{{ disp($w->net_amount) }}</span></span>
                    @if ($w->status->value === 'pending')
                        <form method="POST" action="{{ route('withdrawals.cancel', $w) }}" onsubmit="return confirm('{{ __('Cancel this withdrawal request?') }}')">
                            @csrf
                            <button class="text-xs font-semibold text-rose-400 hover:text-rose-300">{{ __('Cancel') }}</button>
                        </form>
                    @endif
                </div>
                @if ($w->rejection_reason)
                    <p class="mt-2 rounded-lg border border-rose-400/30 bg-rose-500/10 p-2 text-xs text-rose-200">{{ $w->rejection_reason }}</p>
                @endif
            </div>
        @empty
            <x-empty img="Money-Bags--Streamline-Ultimate.png" title="{{ __('No withdrawal requests yet') }}" message="{{ __('Requests you submit will appear here with live status updates.') }}" />
        @endforelse
        <div class="mt-4">{{ $withdrawals->links() }}</div>
    </div>

    <div>
        <x-glass-card x-data="{
            amount: '',
            fee: 0,
            net: 0,
            async refreshQuote() {
                if (!this.amount || this.amount <= 0) { this.fee = 0; this.net = 0; return; }
                const res = await fetch({{ \Illuminate\Support\Js::from(route('withdrawals.quote')) }}, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ amount: this.amount }),
                });
                if (!res.ok) return;
                const data = await res.json();
                this.fee = data.fee;
                this.net = data.net_amount;
            },
        }">
            <h3 class="font-semibold text-strong">{{ __('Request a withdrawal') }}</h3>

            @if (! $user->hasTransactionPin())
                <p class="mt-4 text-sm text-muted">{{ __('Set a transaction PIN in :link before you can withdraw.', ['link' => '']) }}</p>
                <a href="{{ route('security.index') }}" class="btn btn-primary mt-3 w-full">{{ __('Set up Security & Devices') }}</a>
            @elseif ($destinations->isEmpty())
                <p class="mt-4 text-sm text-muted">{{ __('Save a payment method to withdraw to before requesting a payout.') }}</p>
                <a href="{{ route('payment-methods.index') }}" class="btn btn-primary mt-3 w-full">{{ __('Add a Saved Payment Method') }}</a>
            @else
                <form method="POST" action="{{ route('withdrawals.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="label">{{ __('Amount') }}</label>
                        <input type="number" step="0.01" min="0.01" name="amount" x-model="amount" @input.debounce.400ms="refreshQuote()" required class="field">
                    </div>
                    <div class="rounded-xl surface-2 p-3 text-xs text-muted">
                        <div class="flex items-center justify-between"><span>{{ __('Fee') }}</span><span x-text="Number(fee).toLocaleString() + ' {{ $wallet->currency }}'"></span></div>
                        <div class="mt-1 flex items-center justify-between font-semibold text-strong"><span>{{ __('You receive') }}</span><span x-text="Number(net).toLocaleString() + ' {{ $wallet->currency }}'"></span></div>
                    </div>
                    <div>
                        <label class="label">{{ __('Withdraw to') }}</label>
                        <select name="saved_payment_method_id" required class="field">
                            @foreach ($destinations as $d)
                                <option value="{{ $d->id }}">{{ $d->label }} &middot; {{ $d->paymentMethod?->name }}@if ($d->maskedAccountRef()) &middot; {{ $d->maskedAccountRef() }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">{{ __('Transaction PIN') }}</label>
                        <input type="password" inputmode="numeric" name="pin" required class="field" placeholder="••••">
                    </div>
                    <button class="btn btn-primary w-full">{{ __('Request withdrawal') }}</button>
                </form>
            @endif
            <p class="mt-3 text-xs text-faint">{{ __('Approved withdrawals are paid out to your saved method — funds are held, not deducted, until then.') }}</p>
        </x-glass-card>
    </div>
</div>
@endsection
