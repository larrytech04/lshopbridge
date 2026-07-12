@extends('layouts.app')
@section('page-title', 'Deposit '.$deposit->reference)

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('deposit.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← {{ __('Back to deposits') }}</a>

    <x-glass-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-muted">{{ $deposit->reference }}</p>
                <p class="mt-1 text-3xl font-bold text-strong">{{ money($deposit->net_amount, $deposit->currency) }}</p>
            </div>
            <x-status-badge :status="$deposit->status" class="text-sm" />
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs text-faint">{{ __('Method') }}</dt><dd class="text-body">{{ $deposit->paymentMethod->name ?? '—' }}</dd></div>
            <div><dt class="text-xs text-faint">{{ __('Gross amount') }}</dt><dd class="text-body">{{ money($deposit->amount, $deposit->currency) }}</dd></div>
            <div><dt class="text-xs text-faint">{{ __('Fee') }}</dt><dd class="text-body">{{ money($deposit->fee, $deposit->currency) }}</dd></div>
            <div><dt class="text-xs text-faint">{{ __('Credited') }}</dt><dd class="text-body">{{ money($deposit->net_amount, $deposit->currency) }}</dd></div>
            <div><dt class="text-xs text-faint">{{ __('Created') }}</dt><dd class="text-body">{{ $deposit->created_at->format('M j, Y H:i') }}</dd></div>
            <div><dt class="text-xs text-faint">{{ __('Type') }}</dt><dd class="text-body">{{ $deposit->is_automated ? 'Automated' : 'Manual' }}</dd></div>
        </dl>

        @if ($deposit->rejection_reason)
            <div class="mt-4 rounded-xl border border-rose-400/30 bg-rose-500/10 p-3 text-sm text-rose-200">{{ $deposit->rejection_reason }}</div>
        @endif
    </x-glass-card>

    @if (in_array($deposit->status->value, ['pending','under_review']) && ! $deposit->is_automated)
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Upload proof of payment') }}</h3>
            <p class="mt-1 text-sm text-muted">{{ __('Speed up confirmation by attaching your payment receipt.') }}</p>
            <form method="POST" action="{{ route('deposit.proof', $deposit) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-center gap-3">
                @csrf
                <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required class="field max-w-sm">
                <button class="btn btn-primary"><x-icon name="upload" class="h-4 w-4" /> {{ __('Upload') }}</button>
            </form>
            @if ($deposit->proof_path)
                <a href="{{ route('files.show', ['kind' => 'deposit-proof', 'id' => $deposit->id]) }}" target="_blank" class="mt-3 inline-flex items-center gap-1 text-sm text-brand-300 hover:text-brand-200"><x-icon name="eye" class="h-4 w-4" /> {{ __('View uploaded proof') }}</a>
            @endif
        </x-glass-card>
    @endif
</div>
@endsection
