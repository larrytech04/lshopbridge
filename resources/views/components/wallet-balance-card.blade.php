@props(['wallet', 'native' => false])

@php
    // Native mode shows the wallet's own currency as-is (used for every wallet
    // beyond the primary one in the balance carousel) — converting a USD wallet's
    // balance into the visitor's display currency and labelling it "XAF" would
    // misreport the actual amount held, not just reformat it.
    if ($native) {
        $curMeta = config("platform.currencies.{$wallet->currency}", ['symbol' => $wallet->currency, 'rate' => 1, 'decimals' => 2]);
        $dc = ['code' => $wallet->currency, 'symbol' => $curMeta['symbol'], 'decimals' => $curMeta['decimals']];
        $balVal = $wallet->availableBalance();
        $lockedVal = (float) $wallet->locked_balance;
    } else {
        $dc = display_currency();
        $balVal = $wallet->availableBalance() * ($dc['rate'] ?? 1);
        $lockedVal = (float) $wallet->locked_balance * ($dc['rate'] ?? 1);
    }
    $curColors = [
        'XAF' => '#840a20', 'NGN' => '#047857', 'GHS' => '#b45309', 'KES' => '#0f766e',
        'USD' => '#334155', 'EUR' => '#6d28d9', 'CNY' => '#c2410c',
    ];
    $balTint = $curColors[$dc['code']] ?? '#3f3f46';
@endphp

<div class="relative min-w-0" x-data="{ hideBal: localStorage.getItem('pb-hide-bal') === '1' }">
    <div class="relative overflow-hidden rounded-3xl p-4 sm:p-5"
         style="background: {{ $balTint }}; color: #fff">
        <p class="text-[11px] font-bold uppercase tracking-wider opacity-90">{{ __('Available balance') }}</p>
        <div class="mt-1 flex items-center gap-2">
            <p class="min-w-0 truncate text-2xl font-extrabold tracking-tight sm:text-3xl">
                <span x-show="!hideBal">{{ number_format($balVal, $dc['decimals'] ?? 0) }}</span>
                <span x-show="hideBal" x-cloak>••••••</span>
            </p>
            <button type="button" @click="hideBal = !hideBal; localStorage.setItem('pb-hide-bal', hideBal ? '1' : '0')"
                    class="grid h-6 w-6 shrink-0 place-items-center rounded-full transition hover:bg-white/15"
                    :aria-label="hideBal ? '{{ __('Show balance') }}' : '{{ __('Hide balance') }}'">
                <svg x-show="!hideBal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.6"/></svg>
                <svg x-show="hideBal" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M3 3l18 18M10.7 5.1A10.9 10.9 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4M6.6 6.6C3.8 8.5 2 12 2 12s3.5 7 10 7c1.8 0 3.4-.5 4.8-1.3M9.9 9.9a3 3 0 1 0 4.2 4.2"/></svg>
            </button>
        </div>
        <p class="mt-0.5 text-xs font-medium opacity-90">
            {{ $dc['symbol'] }} . {{ $dc['code'] }}
            @if ($lockedVal > 0)
                &middot; {{ __(':amount locked in pending requests', ['amount' => $dc['symbol'].' '.number_format($lockedVal, $dc['decimals'] ?? 0)]) }}
            @endif
        </p>
        <div class="mt-4 flex flex-wrap items-center justify-end gap-2">
            @if (\Illuminate\Support\Facades\Route::has('withdrawals.index'))
                <a href="{{ route('withdrawals.index') }}" class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1.5 text-xs font-bold shadow-sm transition hover:shadow-md" style="color: {{ $balTint }}">
                    <x-img-icon name="Money-Bags--Streamline-Ultimate.png" class="h-3 w-3" /> {{ __('Withdraw') }}
                </a>
            @endif
            <a href="{{ route('funding.create') }}" class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1.5 text-xs font-bold shadow-sm transition hover:shadow-md" style="color: {{ $balTint }}">
                <x-icon name="fund" class="h-3.5 w-3.5" /> {{ __('Fund') }}
            </a>
            <a href="{{ route('deposit.index') }}" class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1.5 text-xs font-bold shadow-sm transition hover:shadow-md" style="color: {{ $balTint }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="h-3.5 w-3.5"><path d="M12 5v14M5 12h14"/></svg> {{ __('Top up') }}
            </a>
        </div>
    </div>
</div>
