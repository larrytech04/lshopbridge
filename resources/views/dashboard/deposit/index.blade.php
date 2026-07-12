@extends('layouts.app')
@section('page-title', 'Add money')

@section('content')
<div class="grid gap-6 lg:grid-cols-3"
     x-data="{
        methodId: {{ old('payment_method_id', optional($methods->first())->id ?? 'null') }},
        methods: {{ \Illuminate\Support\Js::from($methods->map(fn($m) => ['id'=>$m->id,'type'=>$m->type,'automated'=>$m->is_automated,'instructions'=>$m->instructions,'name'=>$m->name])) }},
        get current() { return this.methods.find(m => m.id === this.methodId) || {} },
     }">
    <div class="lg:col-span-2 space-y-6">
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Choose a payment method') }}</h3>
            <form method="POST" action="{{ route('deposit.store') }}" enctype="multipart/form-data" class="mt-5 space-y-5">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($methods as $m)
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method_id" value="{{ $m->id }}" x-model.number="methodId" class="peer sr-only">
                            <div class="rounded-2xl border border-app surface p-4 transition peer-checked:border-brand-400/60 peer-checked:bg-slate-500/10">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <x-icon :name="match($m->type){'momo'=>'phone-device','bank'=>'building','crypto'=>'bitcoin','card'=>'card',default=>'wallet'}" class="h-6 w-6 text-brand-200" />
                                        <span class="font-medium text-strong">{{ $m->name }}</span>
                                    </div>
                                    @if ($m->is_automated)<span class="pill bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/30">{{ __('Instant') }}</span>@else<span class="pill bg-amber-500/15 text-amber-300 ring-1 ring-amber-400/30">{{ __('Manual') }}</span>@endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div>
                    <label class="label">Amount ({{ config('platform.base_currency') }})</label>
                    <input type="number" name="amount" min="1" value="{{ old('amount') }}" required class="field text-lg font-semibold" placeholder="50000">
                </div>

                {{-- Manual instructions --}}
                <template x-if="current && !current.automated && current.instructions">
                    <div class="rounded-2xl border border-amber-400/30 bg-amber-500/10 p-4 text-sm text-amber-100">
                        <p class="font-semibold">{{ __('Payment instructions') }}</p>
                        <p class="mt-1 whitespace-pre-line" x-text="current.instructions"></p>
                    </div>
                </template>

                {{-- Proof upload for manual --}}
                <div x-show="current && !current.automated" x-cloak>
                    <label class="label">{{ __('Proof of payment (optional now, can upload later)') }}</label>
                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" class="field">
                </div>

                <div class="rounded-xl surface p-3 text-xs text-muted">
                    <template x-if="current && current.automated">
                        <span><x-icon name="shield" class="mr-1 inline h-3.5 w-3.5 text-emerald-400" /> {{ __('You\'ll be charged securely and your wallet is credited automatically once the provider confirms.') }}</span>
                    </template>
                    <template x-if="current && !current.automated">
                        <span><x-icon name="info" class="mr-1 inline h-3.5 w-3.5 text-amber-400" /> {{ __('Send the payment using the details above; an admin confirms it shortly.') }}</span>
                    </template>
                </div>

                <button class="btn btn-primary w-full" :disabled="!methodId">
                    <span x-text="(current && current.automated) ? 'Pay & credit wallet' : 'Submit deposit'">{{ __('Continue') }}</span>
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </button>
            </form>
        </x-glass-card>

        {{-- Manual channels reference --}}
        @if ($momoNumbers->isNotEmpty() || $cryptoWallets->isNotEmpty() || $bankAccounts->isNotEmpty())
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Where to send manual payments') }}</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($momoNumbers as $n)
                    <div class="rounded-2xl border border-app surface p-4">
                        <p class="text-xs uppercase tracking-wide text-faint">{{ ucfirst($n->provider) }} MoMo</p>
                        <p class="mt-1 font-semibold text-strong">{{ $n->number }}</p>
                        <p class="text-sm text-muted">{{ $n->account_name }}</p>
                    </div>
                @endforeach
                @foreach ($bankAccounts as $b)
                    <div class="rounded-2xl border border-app surface p-4">
                        <p class="text-xs uppercase tracking-wide text-faint">{{ $b->bank_name }}</p>
                        <p class="mt-1 font-semibold text-strong">{{ $b->account_number }}</p>
                        <p class="text-sm text-muted">{{ $b->account_name }}</p>
                    </div>
                @endforeach
                @foreach ($cryptoWallets as $c)
                    <div class="rounded-2xl border border-app surface p-4">
                        <p class="text-xs uppercase tracking-wide text-faint">{{ $c->asset }} · {{ $c->network }}</p>
                        <p class="mt-1 break-all font-mono text-sm text-strong">{{ $c->address }}</p>
                    </div>
                @endforeach
            </div>
        </x-glass-card>
        @endif
    </div>

    <div class="space-y-6">
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Recent deposits') }}</h3>
            <div class="mt-4 space-y-2">
                @forelse ($recent as $d)
                    <a href="{{ route('deposit.show', $d) }}" class="flex items-center justify-between rounded-xl px-3 py-2 hover:bg-white/5">
                        <div>
                            <p class="text-sm font-medium text-strong">{{ money($d->net_amount, $d->currency) }}</p>
                            <p class="text-xs text-faint">{{ $d->created_at->diffForHumans() }}</p>
                        </div>
                        <x-status-badge :status="$d->status" />
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-faint">{{ __('No deposits yet.') }}</p>
                @endforelse
            </div>
        </x-glass-card>
    </div>
</div>
@endsection
