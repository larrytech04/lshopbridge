@extends('layouts.admin')
@section('page-title', 'Deposit '.$deposit->reference)

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('admin.deposits.index') }}" class="text-sm text-brand-600 hover:text-brand-700">← Deposits</a>

    <x-glass-card>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-muted">{{ $deposit->reference }}</p>
                <p class="mt-1 text-2xl font-bold text-strong">{{ money($deposit->net_amount, $deposit->currency) }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($deposit->risk_flagged)<span class="pill bg-rose-500/15 text-rose-600 text-[10px]">Risk flagged</span>@endif
                <x-status-badge :status="$deposit->status" />
            </div>
        </div>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2 text-sm">
            <div><dt class="text-faint">User</dt><dd class="text-body">{{ $deposit->user->name }} ({{ $deposit->user->email }})</dd></div>
            <div><dt class="text-faint">Method</dt><dd class="text-body">{{ $deposit->paymentMethod->name ?? '-' }} · {{ $deposit->is_automated ? 'Automated' : 'Manual' }}</dd></div>
            <div><dt class="text-faint">Gross / Fee</dt><dd class="text-body">{{ money($deposit->amount,$deposit->currency) }} / {{ money($deposit->fee,$deposit->currency) }}</dd></div>
            <div><dt class="text-faint">Provider ref</dt><dd class="font-mono text-xs text-body">{{ $deposit->provider_reference ?? '-' }}</dd></div>
            <div><dt class="text-faint">Confirmed by</dt><dd class="text-body">{{ $deposit->confirmedBy?->name ?? '-' }} {{ $deposit->confirmed_at?->diffForHumans() }}</dd></div>
            <div><dt class="text-faint">Rejection reason</dt><dd class="text-body">{{ $deposit->rejection_reason ?? '-' }}</dd></div>
        </dl>
        @if ($deposit->proof_path)
            <a href="{{ route('files.show', ['kind'=>'deposit-proof','id'=>$deposit->id]) }}" target="_blank" class="mt-4 inline-flex items-center gap-1 text-sm text-brand-600"><x-icon name="eye" class="h-4 w-4" /> View proof of payment</a>
        @endif
    </x-glass-card>

    @if (! $deposit->status->isSettled())
        <div class="grid gap-4 sm:grid-cols-2">
            <form method="POST" action="{{ route('admin.deposits.confirm', $deposit) }}" onsubmit="return confirm('Confirm this deposit and credit the wallet?')"><x-glass-card>@csrf
                <h3 class="font-semibold text-emerald-600">Confirm</h3>
                <p class="mt-1 text-sm text-muted">Credits {{ money($deposit->net_amount,$deposit->currency) }} to the user's wallet.</p>
                <button class="btn btn-success mt-4 w-full"><x-icon name="check" class="h-4 w-4" /> Confirm & credit</button>
            </x-glass-card></form>
            <form method="POST" action="{{ route('admin.deposits.reject', $deposit) }}"><x-glass-card>@csrf
                <h3 class="font-semibold text-rose-600">Reject</h3>
                <input name="reason" required class="field mt-3" placeholder="Reason">
                <button class="btn btn-danger mt-3 w-full"><x-icon name="x" class="h-4 w-4" /> Reject</button>
            </x-glass-card></form>
        </div>
    @endif

    @if ($deposit->status->canBeRefundedOrReversed())
        <div class="grid gap-4 sm:grid-cols-2">
            <form method="POST" action="{{ route('admin.deposits.refund', $deposit) }}"><x-glass-card>@csrf
                <h3 class="font-semibold text-teal-600">Refund</h3>
                <p class="mt-1 text-xs text-muted">Money is returned through the original provider; the wallet credit is taken back.</p>
                <textarea name="reason" required rows="2" class="field mt-2" placeholder="Refund reason"></textarea>
                <button class="btn btn-danger mt-3 w-full">Confirm refund</button>
            </x-glass-card></form>
            <form method="POST" action="{{ route('admin.deposits.reverse', $deposit) }}"><x-glass-card>@csrf
                <h3 class="font-semibold text-purple-600">Reverse</h3>
                <p class="mt-1 text-xs text-muted">Use when the deposit itself was invalid — the wallet credit is undone.</p>
                <textarea name="reason" required rows="2" class="field mt-2" placeholder="Reversal reason"></textarea>
                <button class="btn btn-danger mt-3 w-full">Confirm reversal</button>
            </x-glass-card></form>
        </div>
    @endif

    @if ($deposit->walletTransactions->isNotEmpty())
        <x-glass-card padding="p-0">
            <h3 class="p-5 font-semibold text-strong">Wallet ledger entries</h3>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-y border-app text-muted"><tr><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Balance after</th></tr></thead>
                <tbody class="divide-y divide-app">@foreach ($deposit->walletTransactions as $t)<tr><td class="px-5 py-3 font-mono text-xs text-body">{{ $t->reference }}</td><td class="px-5 py-3 text-body">{{ ucfirst($t->type) }}</td><td class="px-5 py-3 text-body">{{ money($t->amount, $t->currency) }}</td><td class="px-5 py-3 text-body">{{ money($t->balance_after, $t->currency) }}</td></tr>@endforeach</tbody>
            </table></div>
        </x-glass-card>
    @endif

    <p class="text-center text-xs text-faint">For the full review workspace (timeline, risk, reconciliation, duplicate detection), use the <a href="{{ route('admin.deposits.index') }}" class="text-brand-600 hover:text-brand-700">Deposit Management</a> table and open this deposit's drawer.</p>
</div>
@endsection
