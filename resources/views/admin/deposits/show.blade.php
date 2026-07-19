@extends('layouts.admin')
@section('page-title', 'Deposit '.$deposit->reference)

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('admin.deposits.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← Deposits</a>

    <x-glass-card>
        <div class="flex items-center justify-between">
            <div><p class="text-sm text-muted">{{ $deposit->reference }}</p><p class="mt-1 text-2xl font-bold text-strong">{{ money($deposit->net_amount, $deposit->currency) }}</p></div>
            <x-status-badge :status="$deposit->status" />
        </div>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2 text-sm">
            <div><dt class="text-faint">User</dt><dd class="text-body">{{ $deposit->user->name }} ({{ $deposit->user->email }})</dd></div>
            <div><dt class="text-faint">Method</dt><dd class="text-body">{{ $deposit->paymentMethod->name ?? '-' }}</dd></div>
            <div><dt class="text-faint">Gross / Fee</dt><dd class="text-body">{{ money($deposit->amount,$deposit->currency) }} / {{ money($deposit->fee,$deposit->currency) }}</dd></div>
            <div><dt class="text-faint">Provider ref</dt><dd class="font-mono text-xs text-body">{{ $deposit->provider_reference ?? '-' }}</dd></div>
        </dl>
        @if ($deposit->proof_path)
            <a href="{{ route('files.show', ['kind'=>'deposit-proof','id'=>$deposit->id]) }}" target="_blank" class="mt-4 inline-flex items-center gap-1 text-sm text-brand-300"><x-icon name="eye" class="h-4 w-4" /> View proof of payment</a>
        @endif
    </x-glass-card>

    @if (! $deposit->status->isSettled())
        <div class="grid gap-4 sm:grid-cols-2">
            <form method="POST" action="{{ route('admin.deposits.confirm', $deposit) }}"><x-glass-card>@csrf
                <h3 class="font-semibold text-emerald-300">Confirm</h3>
                <p class="mt-1 text-sm text-muted">Credits {{ money($deposit->net_amount,$deposit->currency) }} to the user's wallet.</p>
                <button class="btn btn-success mt-4 w-full"><x-icon name="check" class="h-4 w-4" /> Confirm & credit</button>
            </x-glass-card></form>
            <form method="POST" action="{{ route('admin.deposits.reject', $deposit) }}"><x-glass-card>@csrf
                <h3 class="font-semibold text-rose-300">Reject</h3>
                <input name="reason" required class="field mt-3" placeholder="Reason">
                <button class="btn btn-danger mt-3 w-full"><x-icon name="x" class="h-4 w-4" /> Reject</button>
            </x-glass-card></form>
        </div>
    @endif
</div>
@endsection
