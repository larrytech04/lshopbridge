@extends('layouts.admin')
@section('page-title', 'Withdrawals')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-strong">Withdrawals</h1>
        <form method="GET" class="flex items-center gap-2">
            <select name="status" onchange="this.form.requestSubmit()" class="field">
                <option value="">All statuses</option>
                @foreach (['pending', 'approved', 'processing', 'paid', 'rejected', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="mt-6 overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[1000px] text-left text-sm">
            <thead class="border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Destination</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Requested</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @forelse ($withdrawals as $w)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-strong">{{ $w->reference }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-strong">{{ $w->user->name }}</p>
                            <p class="text-xs text-muted">{{ $w->user->email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-body">{{ $w->destination_label }}</p>
                            <p class="text-xs text-muted">{{ $w->maskedDestinationRef() }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-strong">{{ money($w->amount, $w->currency) }}</p>
                            <p class="text-xs text-muted">Fee {{ money($w->fee, $w->currency) }} &middot; Net {{ money($w->net_amount, $w->currency) }}</p>
                        </td>
                        <td class="px-4 py-3"><x-status-badge :status="$w->status" /></td>
                        <td class="px-4 py-3 text-xs text-muted">{{ $w->created_at->format('M j, Y g:ia') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @if ($w->status->value === 'pending')
                                    <form method="POST" action="{{ route('admin.withdrawals.approve', $w) }}">@csrf<button class="btn btn-success !py-1 !px-2.5 text-xs">Approve</button></form>
                                    <form method="POST" action="{{ route('admin.withdrawals.reject', $w) }}" class="flex items-center gap-1" onsubmit="const r = prompt('Reason for rejecting this withdrawal?'); if (!r) return false; this.reason.value = r;">
                                        @csrf<input type="hidden" name="reason"><button type="submit" class="btn btn-danger !py-1 !px-2.5 text-xs">Reject</button>
                                    </form>
                                @elseif ($w->status->value === 'approved')
                                    <form method="POST" action="{{ route('admin.withdrawals.mark-paid', $w) }}" class="flex items-center gap-1" onsubmit="const r = prompt('Payout reference (mobile money / bank transaction id)?'); if (!r) return false; this.payout_reference.value = r;">
                                        @csrf<input type="hidden" name="payout_reference"><button type="submit" class="btn btn-success !py-1 !px-2.5 text-xs">Mark paid</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.withdrawals.reject', $w) }}" class="flex items-center gap-1" onsubmit="const r = prompt('Reason for rejecting this withdrawal?'); if (!r) return false; this.reason.value = r;">
                                        @csrf<input type="hidden" name="reason"><button type="submit" class="btn btn-danger !py-1 !px-2.5 text-xs">Reject</button>
                                    </form>
                                @else
                                    <span class="text-xs text-faint">&mdash;</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-muted">No withdrawal requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $withdrawals->links() }}</div>
</div>
@endsection
