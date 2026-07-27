{{--
    Payment availability — real active, country-available, customer-facing
    PaymentMethod rows only, mapped to the closest matching x-pay-icon glyph.
    Never a hardcoded brand list. By product decision this row: excludes
    Crypto and Bank transfer (kept on the full /payment-methods page), and
    caps at 5 items so the row's length stays consistent across countries.
--}}
@php
    // code/type -> x-pay-icon key. Falls back to a neutral badge (see
    // components/pay-icon.blade.php's else branch) for any code not mapped
    // here, rather than mislabeling it as a brand it isn't.
    $iconKey = fn (\App\Models\PaymentMethod $m) => match (true) {
        str_contains($m->code, 'mtn') => 'mtn',
        str_contains($m->code, 'orange') => 'orange',
        str_contains($m->code, 'vodafone') => 'vodafone',
        str_contains($m->code, 'wave') => 'wave',
        str_contains($m->code, 'bank') || $m->type === 'bank' => 'bank',
        $m->type === 'crypto' && str_contains(strtolower($m->name), 'usdt') => 'usdt',
        $m->type === 'crypto' && str_contains(strtolower($m->name), 'btc') => 'btc',
        $m->type === 'card' => 'card',
        default => 'account',
    };

    $visitorIso = region()['iso'] ?? null;

    $methods = \App\Models\PaymentMethod::active()
        ->where(fn ($q) => $q->where('deposit_enabled', true)->orWhere('marketplace_enabled', true))
        ->whereNotIn('type', ['crypto', 'bank'])
        ->get()
        ->filter(fn ($m) => $m->isAvailableInCountry($visitorIso))
        ->values();

    // "Card" is one real gateway (Flutterwave) behind the scenes — selecting
    // any of these at checkout goes through that same single method. Shown
    // as its real accepted networks/wallets here purely for a fuller,
    // recognizable icon row, not as four independently selectable methods.
    $cardSubBrands = [['visa', 'Visa'], ['mastercard', 'Mastercard'], ['applepay', 'Apple Pay']];

    // Real, active, country-available methods only — just capped to a fixed
    // count so the row's length doesn't jump around per country. Local
    // operators (lowest "sort") lead, so the 5 shown are the most relevant
    // ones for the visitor, not an arbitrary truncation.
    $displayItems = $methods->flatMap(function ($method) use ($iconKey, $cardSubBrands) {
        if ($method->type === 'card') {
            return collect($cardSubBrands)->map(fn ($b) => ['icon' => $b[0], 'label' => $b[1]]);
        }

        return [['icon' => $iconKey($method), 'label' => $method->name]];
    })->take(5);
@endphp
@if ($displayItems->isNotEmpty())
    <div class="flex flex-col items-center gap-3 sm:items-start">
        <p class="text-center text-[11px] font-semibold uppercase tracking-wider text-faint sm:hidden">{{ __('Payment availability varies by country') }}</p>
        <div class="flex flex-wrap items-center justify-center gap-3 sm:justify-start">
            @foreach ($displayItems as $item)
                <span class="inline-flex items-center gap-1.5" title="{{ $item['label'] }}">
                    <x-pay-icon :name="$item['icon']" class="h-7 w-7" />
                    <span class="hidden text-xs text-muted sm:inline">{{ $item['label'] }}</span>
                </span>
            @endforeach
            @if (\Illuminate\Support\Facades\Route::has('public.payment-methods'))
                <a href="{{ route('public.payment-methods') }}" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-brand-500 hover:underline">{{ __('View payment methods') }} →</a>
            @endif
        </div>
    </div>
@endif
