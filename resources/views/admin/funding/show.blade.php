@extends('layouts.admin')
@section('page-title', 'Funding '.$funding->reference)

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('admin.funding.index') }}" class="text-sm text-brand-600 hover:text-brand-700">← Funding</a>

    <x-glass-card>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-muted">{{ $funding->reference }}</p>
                <p class="mt-1 text-2xl font-bold text-strong">{{ number_format((float) $funding->target_amount, 2) }} {{ $funding->target_currency }}</p>
                <p class="text-sm text-muted">to {{ $funding->recipient_name }} · {{ $funding->recipient_account }} ({{ $funding->app_type->label() }})</p>
            </div>
            <x-status-badge :status="$funding->status" />
        </div>

        @if ($funding->risk_flagged && $funding->manual_review_reason)
            <div class="mt-4 rounded-xl border border-amber-400/30 bg-amber-500/10 p-3 text-sm text-amber-700"><span class="font-semibold">Risk:</span> {{ $funding->manual_review_reason }}</div>
        @endif

        <dl class="mt-5 grid gap-4 sm:grid-cols-2 text-sm">
            <div><dt class="text-faint">User</dt><dd class="text-body">{{ $funding->user->name }} ({{ $funding->user->email }})</dd></div>
            <div><dt class="text-faint">Paid via</dt><dd class="text-body">{{ ucfirst(str_replace('_',' ',$funding->funding_source)) }}</dd></div>
            <div><dt class="text-faint">Charged</dt><dd class="text-body">{{ money($funding->total_charged, $funding->source_currency) }} (fee {{ money($funding->fee,$funding->source_currency) }})</dd></div>
            <div><dt class="text-faint">Rate</dt><dd class="text-body">{{ rtrim(rtrim(number_format($funding->exchange_rate,6),'0'),'.') }}</dd></div>
            <div><dt class="text-faint">Provider ref</dt><dd class="font-mono text-xs text-body">{{ $funding->provider_reference ?? '-' }}</dd></div>
            <div><dt class="text-faint">Created</dt><dd class="text-body">{{ $funding->created_at->format('M j, Y H:i') }}</dd></div>
            <div><dt class="text-faint">Processed by</dt><dd class="text-body">{{ $funding->processedBy?->name ?? '-' }} {{ $funding->processed_at?->diffForHumans() }}</dd></div>
            <div><dt class="text-faint">Notes</dt><dd class="text-body">{{ $funding->notes ?? '-' }}</dd></div>
        </dl>
        @if ($funding->receipt_path)<a href="{{ route('files.show', ['kind'=>'funding-receipt','id'=>$funding->id]) }}" target="_blank" class="mt-3 inline-flex items-center gap-1 text-sm text-brand-600"><x-icon name="eye" class="h-4 w-4" /> Receipt</a>@endif
    </x-glass-card>

    @unless ($funding->status->isTerminal())
        <div class="grid gap-4 sm:grid-cols-2">
            <form method="POST" action="{{ route('admin.funding.complete', $funding) }}" enctype="multipart/form-data"><x-glass-card>@csrf
                <h3 class="font-semibold text-emerald-600">Complete manually</h3>
                <p class="mt-1 text-xs text-muted">Requires evidence — attach a receipt or provider confirmation.</p>
                <input type="file" name="receipt" class="field mt-3 text-xs" accept=".jpg,.jpeg,.png,.pdf">
                <input name="note" class="field mt-2 text-xs" placeholder="Note (optional)">
                <button class="btn btn-success mt-3 w-full">Mark complete</button>
            </x-glass-card></form>

            <form method="POST" action="{{ route('admin.funding.retry', $funding) }}"><x-glass-card>@csrf
                <h3 class="font-semibold text-sky-600">Retry engine</h3>
                <p class="mt-1 text-sm text-muted">Re-submit to the funding provider.</p>
                <button class="btn btn-ghost mt-3 w-full"><x-icon name="refresh" class="h-4 w-4" /> Retry funding</button>
            </x-glass-card></form>

            <form method="POST" action="{{ route('admin.funding.mark-failed', $funding) }}"><x-glass-card>@csrf
                <h3 class="font-semibold text-rose-600">Mark failed</h3>
                <p class="mt-1 text-xs text-muted">Refunds the wallet debit automatically.</p>
                <textarea name="reason" required rows="2" class="field mt-2 text-xs" placeholder="Failure reason"></textarea>
                <button class="btn btn-danger mt-3 w-full">Mark failed &amp; refund</button>
            </x-glass-card></form>

            <form method="POST" action="{{ route('admin.funding.cancel', $funding) }}"><x-glass-card>@csrf
                <h3 class="font-semibold text-strong">Cancel request</h3>
                <p class="mt-1 text-xs text-muted">Refunds the wallet debit automatically.</p>
                <textarea name="reason" required rows="2" class="field mt-2 text-xs" placeholder="Cancellation reason"></textarea>
                <button class="btn btn-ghost mt-3 w-full">Cancel &amp; refund</button>
            </x-glass-card></form>
        </div>
    @endunless

    @if ($funding->status->canBeRefunded())
        <form method="POST" action="{{ route('admin.funding.refund', $funding) }}"><x-glass-card>@csrf
            <h3 class="font-semibold text-teal-600">Refund</h3>
            <textarea name="reason" required rows="2" class="field mt-3 text-sm" placeholder="Refund reason"></textarea>
            <button class="btn btn-danger mt-3 w-full">Refund to wallet</button>
        </x-glass-card></form>
    @endif

    @if ($funding->walletTransactions->isNotEmpty())
        <x-glass-card padding="p-0">
            <h3 class="p-5 font-semibold text-strong">Wallet ledger entries</h3>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-y border-app text-muted"><tr><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Balance after</th></tr></thead>
                <tbody class="divide-y divide-app">@foreach ($funding->walletTransactions as $t)<tr><td class="px-5 py-3 font-mono text-xs text-body">{{ $t->reference }}</td><td class="px-5 py-3 text-body">{{ ucfirst($t->type) }}</td><td class="px-5 py-3 text-body">{{ money($t->amount, $t->currency) }}</td><td class="px-5 py-3 text-body">{{ money($t->balance_after, $t->currency) }}</td></tr>@endforeach</tbody>
            </table></div>
        </x-glass-card>
    @endif

    <p class="text-center text-xs text-faint">For the full review workspace (recipient verification, limits, reconciliation, duplicate detection, timeline), use the <a href="{{ route('admin.funding.index') }}" class="text-brand-600 hover:text-brand-700">China Wallet Funding</a> table and open this request's drawer.</p>
</div>
@endsection
