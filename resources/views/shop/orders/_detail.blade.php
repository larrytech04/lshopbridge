@php
    // Points aren't credited to a per-order ledger yet, this mirrors the dashboard's
    // loyalty-tier math (1 pt per 100 base-currency units) purely for display.
    $earnedPoints = (int) floor($order->total / 100);
    $downloadLines = [$order->reference, disp($order->total), ''];
    foreach ($order->items as $di) {
        $downloadLines[] = $di->name;
        foreach ($di->delivered ?? [] as $dc) {
            $downloadLines[] = '  '.$dc;
        }
    }
    $downloadText = implode("\n", $downloadLines);
@endphp

<div class="border-t border-app px-5 py-4 sm:px-6">
    <div class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
        <div class="flex items-center justify-between gap-3 sm:col-span-2">
            <span class="text-muted">{{ __('Order id') }}</span>
            <span class="flex items-center gap-1.5 font-mono text-xs text-strong" x-data>
                {{ $order->reference }}
                <button type="button" @click="navigator.clipboard.writeText(@js($order->reference))" class="text-faint hover:text-strong">
                    <x-icon name="copy" class="h-3.5 w-3.5" />
                </button>
            </span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-muted">{{ __('Payment method') }}</span>
            <span class="font-semibold text-strong">{{ $order->payment_source === 'wallet' ? __('Wallet') : __('Card') }}</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-muted">{{ __('Earned points') }}</span>
            <span class="flex items-center gap-1 font-semibold text-strong"><x-icon name="sparkles" class="h-3.5 w-3.5 text-brand-500" /> {{ $earnedPoints }}</span>
        </div>
        <div class="flex items-center justify-between sm:col-span-2">
            <span class="text-muted">{{ __('Total amount') }}</span>
            <span class="text-base font-bold text-strong">{{ disp($order->total) }}</span>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-4">
        <a href="{{ route('disputes.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-500 hover:text-brand-600">
            <x-icon name="help" class="h-4 w-4" /> {{ __('Need help with your order? Contact us!') }}
        </a>
        <button type="button"
                @click="const blob = new Blob([{{ Illuminate\Support\Js::from($downloadText) }}], { type: 'text/plain' }); const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = '{{ $order->reference }}.txt'; a.click(); URL.revokeObjectURL(a.href);"
                class="btn btn-ghost ml-auto shrink-0 !py-1.5 text-xs">
            <x-icon name="download" class="h-3.5 w-3.5" /> {{ __('Download card') }}
        </button>
    </div>

    <div class="mt-4 space-y-3">
        @foreach ($order->items as $item)
            @php
                $flag = $item->product?->region ? \App\Models\Country::where('iso2', $item->product->region)->orWhere('name', $item->product->region)->value('flag_emoji') : null;
                $img = $item->product?->image_path ?? $item->product?->logo_path;
            @endphp
            <div class="rounded-2xl surface-2 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid h-12 w-16 shrink-0 place-items-center overflow-hidden rounded-xl bg-white ring-1 ring-app">
                            @if ($img)
                                <img src="{{ Storage::url($img) }}" class="max-h-10 w-auto object-contain" alt="">
                            @else
                                <x-icon name="giftcard" class="h-6 w-6 text-slate-400" />
                            @endif
                        </span>
                        <p class="truncate text-sm font-semibold text-strong">{{ $item->name }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5 text-sm font-semibold text-strong">
                        <span>{{ disp($item->unit_price) }}</span>
                        @if ($flag)
                            <span>{{ $flag }}</span>
                        @elseif ($item->product?->region)
                            <span class="h-3 w-4 rounded-sm surface"></span>
                        @endif
                        @if ($item->product?->region)<span class="text-xs font-normal text-muted">{{ $item->product->region }}</span>@endif
                    </div>
                </div>

                @if (! empty($item->delivered))
                    <div class="mt-3 space-y-2" x-data>
                        @foreach ($item->delivered as $code)
                            <div class="flex items-center gap-2 rounded-xl border border-app surface px-4 py-2.5">
                                <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide text-faint">{{ __('Code') }}</span>
                                <code class="min-w-0 flex-1 truncate font-mono text-sm font-semibold text-strong">{{ $code }}</code>
                                <button type="button"
                                        @click="navigator.clipboard.writeText(@js($code)); $el.querySelector('span').textContent = @js(__('Copied'))"
                                        class="shrink-0 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">
                                    <span>{{ __('Copy code') }}</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 flex items-center gap-2" x-data="{ open: false }">
                        @if ($item->product?->redeem_instructions)
                            <button type="button" @click="open = !open" class="text-xs font-semibold text-brand-500 hover:text-brand-600">{{ __('Redeem instructions') }}</button>
                            <span class="text-faint">·</span>
                        @endif
                        <a href="{{ route('pages.show', 'terms') }}" class="text-xs font-semibold text-muted hover:text-strong">{{ __('Terms and conditions') }}</a>
                        @if ($item->product?->redeem_instructions)
                            <p x-show="open" x-collapse style="display:none" class="mt-2 basis-full text-xs leading-relaxed text-muted">{{ $item->product->redeem_instructions }}</p>
                        @endif
                    </div>
                @else
                    <p class="mt-3 text-sm text-muted">{{ __('Awaiting delivery…') }}</p>
                @endif
            </div>
        @endforeach
    </div>
</div>
