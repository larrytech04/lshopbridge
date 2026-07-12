@extends('layouts.app')
@section('page-title', 'Funding '.$funding->reference)

@section('content')
@php
    $steps = [
        ['payment', 'Payment', in_array($funding->status->value, ['payment_successful','funding_processing','funding_successful','manual_review','refunded'])],
        ['processing', 'Funding', in_array($funding->status->value, ['funding_processing','funding_successful'])],
        ['done', 'Delivered', $funding->status->value === 'funding_successful'],
    ];
@endphp
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('funding.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← {{ __('Back to funding') }}</a>

    <x-glass-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-muted">{{ $funding->reference }}</p>
                <p class="mt-1 text-3xl font-bold text-strong">{{ money($funding->target_amount, $funding->target_currency) }}</p>
                <p class="text-sm text-muted">to {{ $funding->recipient_account }} ({{ $funding->app_type->label() }})</p>
            </div>
            <x-status-badge :status="$funding->status" class="text-sm" />
        </div>

        {{-- Progress --}}
        <div class="mt-6 flex items-center">
            @foreach ($steps as $i => [$key, $label, $done])
                <div class="flex flex-1 items-center {{ $i === count($steps)-1 ? 'flex-none' : '' }}">
                    <div class="flex flex-col items-center">
                        <span class="grid h-9 w-9 place-items-center rounded-full {{ $done ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/40' : 'surface text-faint ring-1 ring-white/10' }}">
                            <x-icon :name="$done ? 'check' : 'clock'" class="h-4 w-4" />
                        </span>
                        <span class="mt-1.5 text-xs {{ $done ? 'text-emerald-300' : 'text-faint' }}">{{ $label }}</span>
                    </div>
                    @unless ($i === count($steps)-1)
                        <div class="mx-2 h-0.5 flex-1 {{ $done ? 'bg-emerald-500/40' : 'surface-2' }}"></div>
                    @endunless
                </div>
            @endforeach
        </div>

        @if ($funding->status->value === 'manual_review')
            <div class="mt-6 rounded-xl border border-amber-400/30 bg-amber-500/10 p-3 text-sm text-amber-100">
                <p class="font-semibold">{{ __('Under manual review') }}</p>
                <p class="mt-1">{{ $funding->manual_review_reason ?? 'Our team is verifying this transaction and will complete it shortly.' }}</p>
            </div>
        @endif
        @if ($funding->status->value === 'refunded')
            <div class="mt-6 rounded-xl border border-violet-400/30 bg-violet-500/10 p-3 text-sm text-violet-100">This request was refunded to your wallet. {{ $funding->notes }}</div>
        @endif
    </x-glass-card>

    <x-glass-card>
        <h3 class="font-semibold text-strong">{{ __('Breakdown') }}</h3>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs text-faint">{{ __('You sent') }}</dt><dd class="text-body">{{ money($funding->source_amount, $funding->source_currency) }}</dd></div>
            <div><dt class="text-xs text-faint">{{ __('Fee') }}</dt><dd class="text-body">{{ money($funding->fee, $funding->source_currency) }}</dd></div>
            <div><dt class="text-xs text-faint">{{ __('Total charged') }}</dt><dd class="text-body">{{ money($funding->total_charged, $funding->source_currency) }}</dd></div>
            <div><dt class="text-xs text-faint">{{ __('Exchange rate') }}</dt><dd class="text-body">{{ rtrim(rtrim(number_format($funding->exchange_rate,6),'0'),'.') }}</dd></div>
            <div><dt class="text-xs text-faint">{{ __('Paid via') }}</dt><dd class="text-body">{{ ucfirst(str_replace('_',' ', $funding->funding_source)) }}</dd></div>
            <div><dt class="text-xs text-faint">{{ __('Created') }}</dt><dd class="text-body">{{ $funding->created_at->format('M j, Y H:i') }}</dd></div>
            @if ($funding->provider_reference)<div><dt class="text-xs text-faint">{{ __('Provider ref') }}</dt><dd class="font-mono text-xs text-body">{{ $funding->provider_reference }}</dd></div>@endif
        </dl>
        @if ($funding->notes && $funding->status->value === 'funding_successful')
            <div class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-100">{{ $funding->notes }}</div>
        @endif
    </x-glass-card>
</div>
@endsection
