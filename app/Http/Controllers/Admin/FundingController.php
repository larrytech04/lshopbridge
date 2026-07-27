<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FundingStatus;
use App\Http\Controllers\Controller;
use App\Models\FundingRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Funding\FundingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FundingController extends Controller
{
    public function index(Request $request, FundingService $service): View
    {
        $tab = $request->query('tab', 'all');
        $items = $this->filteredQuery($request, $tab)->paginate(20)->withQueryString();

        return view('admin.funding.index', [
            'items' => $items,
            'tab' => $tab,
            'q' => $request->query('q', ''),
            'filters' => $request->only('status', 'q'),
            'counts' => $service->tabCounts(),
            'summary' => $this->summary(),
            'countries' => \App\Models\Country::orderBy('name')->get(['id', 'name']),
            'reviewers' => User::whereIn('role', ['admin', 'super_admin'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(FundingRequest $funding): View
    {
        return view('admin.funding.show', ['funding' => $funding->load('user', 'beneficiary', 'deposit', 'walletTransactions', 'processedBy')]);
    }

    public function rowDetail(FundingRequest $funding, FundingService $service, AuditLogger $audit)
    {
        $funding->load(['user.country', 'beneficiary', 'deposit', 'processedBy', 'assignedTo', 'walletTransactions', 'intents', 'webhookEvents', 'events.actor', 'riskFlags', 'disputes']);
        $audit->log('funding.viewed', "Viewed funding {$funding->reference}", $funding);

        $previousSuccessful = $funding->beneficiary_account_id
            ? FundingRequest::where('beneficiary_account_id', $funding->beneficiary_account_id)->where('status', 'funding_successful')->count()
            : 0;
        $lastSuccessful = $funding->beneficiary_account_id
            ? FundingRequest::where('beneficiary_account_id', $funding->beneficiary_account_id)->where('status', 'funding_successful')->latest('processed_at')->value('processed_at')
            : null;

        $level = $funding->user ? \App\Models\KycLevel::where('level', $funding->user->kyc_level)->first() : null;
        $dailyUsed = $funding->user ? (float) FundingRequest::where('user_id', $funding->user->id)
            ->whereNotIn('status', ['refunded', 'funding_failed', 'cancelled'])
            ->where('created_at', '>=', now()->startOfDay())->sum('total_charged') : 0;
        $monthlyUsed = $funding->user ? (float) FundingRequest::where('user_id', $funding->user->id)
            ->whereNotIn('status', ['refunded', 'funding_failed', 'cancelled'])
            ->where('created_at', '>=', now()->startOfMonth())->sum('total_charged') : 0;

        $automationType = $funding->status->value === 'funding_successful'
            ? ($funding->processed_by ? 'Manual' : 'Automated')
            : 'N/A';

        return response()->json([
            'funding' => [
                'id' => $funding->id,
                'reference' => $funding->reference,
                'provider_reference' => $funding->provider_reference,
                'status' => $funding->status->value,
                'status_label' => $funding->status->label(),
                'customer' => $funding->user?->name,
                'email' => $funding->user?->email,
                'user_id' => $funding->user_id,
                'country' => $funding->user?->country?->name,
                'app_type' => $funding->app_type->label(),
                'recipient_name' => $funding->recipient_name,
                'recipient_masked' => $this->maskIdentifier($funding->recipient_account),
                'source_amount' => (float) $funding->source_amount,
                'source_currency' => $funding->source_currency,
                'exchange_rate' => (float) $funding->exchange_rate,
                'fee' => (float) $funding->fee,
                'total_charged' => (float) $funding->total_charged,
                'target_amount' => (float) $funding->target_amount,
                'target_currency' => $funding->target_currency,
                'funding_source' => $funding->funding_source,
                'automation_type' => $automationType,
                'created' => $funding->created_at->format('M j, Y g:ia'),
                'processed' => $funding->processed_at?->format('M j, Y g:ia'),
                'updated' => $funding->updated_at->format('M j, Y g:ia'),
                'assigned_to' => $funding->assigned_to,
                'assigned_to_name' => $funding->assignedTo?->name,
                'risk_flagged' => $funding->risk_flagged,
                'manual_review_reason' => $funding->manual_review_reason,
                'flagged_for_investigation' => $funding->flagged_for_investigation,
                'reconciliation_status' => $funding->reconciliation_status ?? $service->computeReconciliationStatus($funding),
                'admin_notes' => $funding->admin_notes,
                'notes' => $funding->notes,
                'receipt_url' => $funding->receipt_path ? "/files/funding-receipt/{$funding->id}" : null,
                'can_refund' => $funding->status->canBeRefunded(),
                'can_mark_failed' => ! $funding->status->isTerminal(),
                'can_cancel' => $funding->status->isOpen(),
                'is_settled' => $funding->status->isTerminal(),
                'deposit_reference' => $funding->deposit?->reference,
            ],
            'recipient' => [
                'app_type' => $funding->beneficiary?->app_type?->label(),
                'account_name' => $funding->beneficiary?->account_name,
                'status' => $funding->beneficiary?->status?->value,
                'is_default' => $funding->beneficiary?->is_default,
                'previous_successful' => $previousSuccessful,
                'last_successful' => $lastSuccessful?->format('M j, Y'),
            ],
            'limits' => [
                'per_transaction' => $level ? (float) $level->per_transaction_limit : null,
                'daily_limit' => $level ? (float) $level->daily_limit : null,
                'daily_used' => $dailyUsed,
                'monthly_limit' => $level ? (float) $level->monthly_limit : null,
                'monthly_used' => $monthlyUsed,
                'kyc_level' => $funding->user?->kyc_level,
            ],
            'wallet_transactions' => $funding->walletTransactions->map(fn ($t) => [
                'reference' => $t->reference, 'type' => $t->type, 'amount' => (float) $t->amount,
                'balance_after' => (float) $t->balance_after, 'currency' => $t->currency, 'at' => $t->created_at->format('M j, Y g:ia'),
            ]),
            'intents' => $funding->intents->map(fn ($i) => [
                'reference' => $i->reference, 'status' => $i->status->value, 'provider_reference' => $i->provider_reference,
                'attempts' => $i->attempts, 'last_error' => $i->last_error, 'at' => $i->created_at->format('M j, Y g:ia'),
            ]),
            'webhook_events' => $funding->webhookEvents->map(fn ($w) => [
                'event_type' => $w->event_type, 'status' => $w->status->value, 'signature_valid' => $w->signature_valid,
                'at' => $w->created_at->format('M j, Y g:ia'),
            ]),
            'risk_flags' => $funding->riskFlags->map(fn ($f) => ['rule' => $f->rule_code, 'severity' => $f->severity, 'reason' => $f->reason, 'status' => $f->status]),
            'disputes' => $funding->disputes->map(fn ($d) => ['id' => $d->id, 'status' => $d->status->value, 'category' => $d->category]),
            'duplicates' => $service->findDuplicates($funding),
            'events' => $funding->events->map(fn ($e) => [
                'event' => $e->event, 'from' => $e->from_status, 'to' => $e->to_status, 'reason' => $e->reason,
                'actor' => $e->actor?->name ?? 'System', 'at' => $e->created_at->format('M j, Y g:ia'),
            ]),
        ]);
    }

    public function complete(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $receiptPath = $request->hasFile('receipt')
            ? $request->file('receipt')->store('funding/receipts', 'private')
            : null;

        $service->completeManually($funding, $request->user(), $receiptPath, $data['note'] ?? null);

        return back()->with('success', 'Funding marked complete.');
    }

    public function retry(FundingRequest $funding, FundingService $service)
    {
        $service->retry($funding);

        return back()->with('success', 'Funding retried through the provider.');
    }

    public function refund(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $service->refund($funding, $request->user(), $data['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Funding refunded to the user wallet.');
    }

    public function markFailed(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $service->markFailed($funding, $request->user(), $data['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Funding marked failed and refunded.');
    }

    public function cancel(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $service->cancel($funding, $request->user(), $data['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Funding cancelled and refunded.');
    }

    public function placeUnderReview(Request $request, FundingRequest $funding, FundingService $service)
    {
        $service->placeUnderReview($funding, $request->user(), $request->input('reason'));

        return back()->with('success', 'Funding placed under review.');
    }

    public function requestInfo(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:1000']]);
        $service->requestInfo($funding, $request->user(), $data['message']);

        return back()->with('success', 'Request sent to the customer.');
    }

    public function escalate(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $service->escalate($funding, $request->user(), $data['reason']);

        return back()->with('success', 'Funding escalated.');
    }

    public function markForInvestigation(Request $request, FundingRequest $funding, FundingService $service)
    {
        $service->markForInvestigation($funding, $request->user(), $request->input('reason'));

        return back()->with('success', 'Funding flagged for investigation.');
    }

    public function assign(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate(['reviewer_id' => ['nullable', 'exists:users,id']]);
        $reviewer = ! empty($data['reviewer_id']) ? User::find($data['reviewer_id']) : null;
        $service->assign($funding, $reviewer, $request->user());

        return back()->with('success', $reviewer ? "Assigned to {$reviewer->name}." : 'Unassigned.');
    }

    public function addNote(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);
        $service->addNote($funding, $request->user(), $data['note']);

        return back()->with('success', 'Note saved.');
    }

    public function requery(Request $request, FundingRequest $funding, FundingService $service)
    {
        $service->requeryKnownState($funding, $request->user());

        return back()->with('success', 'Refreshed known provider state.');
    }

    public function reconcile(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate([
            'status' => ['required', 'in:matched,unmatched,amount_mismatch,recipient_mismatch,provider_pending,manually_reconciled,requires_investigation'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $service->reconcile($funding, $request->user(), $data['status'], $data['note'] ?? null);

        return back()->with('success', 'Reconciliation status updated.');
    }

    public function bulkAction(Request $request, FundingService $service, AuditLogger $audit)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['assign', 'export', 'investigate', 'requery', 'reconciliation_batch'])],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
        ]);

        $reviewer = ! empty($data['reviewer_id']) ? User::find($data['reviewer_id']) : null;
        $rows = FundingRequest::whereIn('id', $data['ids'])->get();

        foreach ($rows as $funding) {
            match ($data['action']) {
                'assign' => $service->assign($funding, $reviewer, $request->user()),
                'investigate' => $service->markForInvestigation($funding, $request->user(), 'Bulk flagged for investigation.'),
                'requery' => $service->requeryKnownState($funding, $request->user()),
                'reconciliation_batch' => $funding->update(['reconciliation_status' => 'requires_investigation']),
                default => null,
            };
        }

        $audit->log('funding.bulk_'.$data['action'], "Bulk {$data['action']} on ".$rows->count().' funding request(s)');

        return back()->with('success', ucfirst($data['action']).' applied to '.$rows->count().' request(s).');
    }

    public function exportCsv(Request $request, AuditLogger $audit): StreamedResponse
    {
        $rows = $this->filteredQuery($request, $request->query('tab', 'all'))->get();
        $audit->log('funding.exported', 'Exported '.$rows->count().' funding request(s) to CSV');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Customer', 'Recipient', 'Wallet app', 'Source amount', 'CNY delivered', 'Status', 'Created', 'Completed']);
            foreach ($rows as $f) {
                fputcsv($out, [
                    $f->reference, $f->user?->name, $f->recipient_name, $f->app_type->label(),
                    $f->source_amount, $f->target_amount, $f->status->label(),
                    $f->created_at->toDateTimeString(), $f->processed_at?->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, 'funding-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request, string $tab): Builder
    {
        $query = FundingRequest::with('user', 'beneficiary');

        match ($tab) {
            'all' => null,
            'pending' => $query->whereIn('status', ['payment_pending', 'payment_successful']),
            'under_review' => $query->where('status', 'manual_review'),
            'processing' => $query->where('status', 'funding_processing'),
            'completed' => $query->where('status', 'funding_successful'),
            default => $query->where('status', $tab),
        };

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $w) use ($search) {
                $w->where('reference', 'like', "%{$search}%")
                    ->orWhere('provider_reference', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_account', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('deposit', fn ($d) => $d->where('reference', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if ($appType = $request->query('app_type')) {
            $query->where('app_type', $appType);
        }
        if ($currency = $request->query('currency')) {
            $query->where('target_currency', $currency);
        }
        if ($country = $request->query('country_id')) {
            $query->whereHas('user', fn ($u) => $u->where('country_id', $country));
        }
        if ($min = $request->query('amount_min')) {
            $query->where('source_amount', '>=', $min);
        }
        if ($max = $request->query('amount_max')) {
            $query->where('source_amount', '<=', $max);
        }
        if ($method = $request->query('funding_source')) {
            $query->where('funding_source', $method);
        }
        if ($automation = $request->query('automation')) {
            $automation === 'manual' ? $query->whereNotNull('processed_by') : $query->whereNull('processed_by');
        }
        if ($risk = $request->query('risk')) {
            $risk === 'flagged' ? $query->where('risk_flagged', true) : $query->where('risk_flagged', false);
        }
        if ($reconciliation = $request->query('reconciliation_status')) {
            $query->where('reconciliation_status', $reconciliation);
        }
        if ($reviewer = $request->query('assigned_to')) {
            $query->where('assigned_to', $reviewer);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return match ($request->query('sort', 'newest')) {
            'oldest' => $query->oldest('created_at'),
            'amount_desc' => $query->orderByDesc('source_amount'),
            'amount_asc' => $query->orderBy('source_amount'),
            default => $query->latest('created_at'),
        };
    }

    private function summary(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $deliveredToday = (float) FundingRequest::where('status', 'funding_successful')->where('processed_at', '>=', $today)->sum('target_amount');
        $deliveredYesterday = (float) FundingRequest::where('status', 'funding_successful')->whereBetween('processed_at', [$yesterday, $today])->sum('target_amount');

        return [
            'total' => FundingRequest::count(),
            'today' => FundingRequest::where('created_at', '>=', $today)->count(),
            'processing' => FundingRequest::where('status', 'funding_processing')->count(),
            'under_review' => FundingRequest::where('status', 'manual_review')->count(),
            'completed' => FundingRequest::where('status', 'funding_successful')->count(),
            'failed' => FundingRequest::where('status', 'funding_failed')->count(),
            'cancelled' => FundingRequest::where('status', 'cancelled')->count(),
            'refunded' => FundingRequest::where('status', 'refunded')->count(),
            'delivered_today' => $deliveredToday,
            'delivered_today_change' => $deliveredYesterday > 0 ? round((($deliveredToday - $deliveredYesterday) / $deliveredYesterday) * 100, 1) : null,
            'pending_value' => (float) FundingRequest::whereIn('status', ['payment_pending', 'payment_successful', 'funding_processing', 'manual_review'])->sum('target_amount'),
        ];
    }

    private function maskIdentifier(?string $value): string
    {
        if (! $value) {
            return '—';
        }
        $len = strlen($value);

        return $len <= 4 ? str_repeat('*', $len) : substr($value, 0, 3).str_repeat('*', max(2, $len - 5)).substr($value, -2);
    }
}
