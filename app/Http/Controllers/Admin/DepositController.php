<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Deposit\DepositService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepositController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'all');
        $items = $this->filteredQuery($request, $tab)->paginate(20)->withQueryString();

        return view('admin.deposits.index', [
            'items' => $items,
            'tab' => $tab,
            'q' => $request->query('q', ''),
            'filters' => $request->only('status', 'q'),
            'counts' => $this->tabCounts(),
            'summary' => $this->summary(),
            'methods' => PaymentMethod::orderBy('name')->get(['id', 'code', 'name']),
            'countries' => \App\Models\Country::orderBy('name')->get(['id', 'name']),
            'reviewers' => User::whereIn('role', ['admin', 'super_admin'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Deposit $deposit): View
    {
        return view('admin.deposits.show', ['deposit' => $deposit->load('user', 'paymentMethod', 'walletTransactions', 'confirmedBy')]);
    }

    public function rowDetail(Deposit $deposit, DepositService $deposits, AuditLogger $audit)
    {
        $deposit->load(['user.country', 'paymentMethod', 'confirmedBy', 'assignedTo', 'walletTransactions', 'intents', 'webhookEvents', 'events.actor', 'riskFlags', 'disputes']);
        $audit->log('deposit.viewed', "Viewed deposit {$deposit->reference}", $deposit);

        $baseCurrency = config('platform.base_currency', 'XAF');

        return response()->json([
            'deposit' => [
                'id' => $deposit->id,
                'reference' => $deposit->reference,
                'provider_reference' => $deposit->provider_reference,
                'status' => $deposit->status->value,
                'status_label' => $deposit->status->label(),
                'customer' => $deposit->user?->name,
                'email' => $deposit->user?->email,
                'user_id' => $deposit->user_id,
                'country' => $deposit->user?->country?->name,
                'method' => $deposit->paymentMethod?->name,
                'provider_code' => $deposit->provider_code,
                'amount' => (float) $deposit->amount,
                'fee' => (float) $deposit->fee,
                'net_amount' => (float) $deposit->net_amount,
                'currency' => $deposit->currency,
                'reporting_amount' => $deposit->currency === $baseCurrency ? (float) $deposit->amount : null,
                'base_currency' => $baseCurrency,
                'created' => $deposit->created_at->format('M j, Y g:ia'),
                'confirmed' => $deposit->confirmed_at?->format('M j, Y g:ia'),
                'updated' => $deposit->updated_at->format('M j, Y g:ia'),
                'is_automated' => $deposit->is_automated,
                'assigned_to' => $deposit->assigned_to,
                'assigned_to_name' => $deposit->assignedTo?->name,
                'risk_flagged' => $deposit->risk_flagged,
                'flagged_for_investigation' => $deposit->flagged_for_investigation,
                'reconciliation_status' => $deposit->reconciliation_status ?? $deposits->computeReconciliationStatus($deposit),
                'admin_notes' => $deposit->admin_notes,
                'rejection_reason' => $deposit->rejection_reason,
                'refund_reference' => $deposit->refund_reference,
                'refund_reason' => $deposit->refund_reason,
                'reversal_reason' => $deposit->reversal_reason,
                'proof_url' => $deposit->proof_path ? "/files/deposit-proof/{$deposit->id}" : null,
                'payer_details' => $deposit->payer_details,
                'can_refund_or_reverse' => $deposit->status->canBeRefundedOrReversed(),
                'is_open' => $deposit->status->isOpen(),
                'is_settled' => $deposit->status->isSettled(),
            ],
            'wallet_transactions' => $deposit->walletTransactions->map(fn ($t) => [
                'reference' => $t->reference, 'type' => $t->type, 'amount' => (float) $t->amount,
                'balance_after' => (float) $t->balance_after, 'currency' => $t->currency, 'at' => $t->created_at->format('M j, Y g:ia'),
            ]),
            'intents' => $deposit->intents->map(fn ($i) => [
                'reference' => $i->reference, 'status' => $i->status->value, 'provider_reference' => $i->provider_reference,
                'attempts' => $i->attempts, 'last_error' => $i->last_error, 'at' => $i->created_at->format('M j, Y g:ia'),
            ]),
            'webhook_events' => $deposit->webhookEvents->map(fn ($w) => [
                'event_type' => $w->event_type, 'status' => $w->status->value, 'signature_valid' => $w->signature_valid,
                'at' => $w->created_at->format('M j, Y g:ia'),
            ]),
            'risk_flags' => $deposit->riskFlags->map(fn ($f) => ['rule' => $f->rule_code, 'severity' => $f->severity, 'reason' => $f->reason, 'status' => $f->status]),
            'disputes' => $deposit->disputes->map(fn ($d) => ['id' => $d->id, 'status' => $d->status->value, 'category' => $d->category]),
            'duplicates' => $deposits->findDuplicates($deposit),
            'events' => $deposit->events->map(fn ($e) => [
                'event' => $e->event, 'from' => $e->from_status, 'to' => $e->to_status, 'reason' => $e->reason,
                'actor' => $e->actor?->name ?? 'System', 'at' => $e->created_at->format('M j, Y g:ia'),
            ]),
        ]);
    }

    public function confirm(Deposit $deposit, DepositService $deposits)
    {
        $deposits->confirm($deposit, auth()->user());

        return back()->with('success', 'Deposit confirmed and wallet credited.');
    }

    public function reject(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $deposits->reject($deposit, $data['reason'], auth()->user());

        return back()->with('success', 'Deposit rejected.');
    }

    public function placeUnderReview(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $deposits->placeUnderReview($deposit, $request->user(), $request->input('reason'));

        return back()->with('success', 'Deposit placed under review.');
    }

    public function requestInfo(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:1000']]);
        $deposits->requestInfo($deposit, $request->user(), $data['message']);

        return back()->with('success', 'Request sent to the customer.');
    }

    public function escalate(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $deposits->escalate($deposit, $request->user(), $data['reason']);

        return back()->with('success', 'Deposit escalated.');
    }

    public function markForInvestigation(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $deposits->markForInvestigation($deposit, $request->user(), $request->input('reason'));

        return back()->with('success', 'Deposit flagged for investigation.');
    }

    public function assign(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $data = $request->validate(['reviewer_id' => ['nullable', 'exists:users,id']]);
        $reviewer = ! empty($data['reviewer_id']) ? User::find($data['reviewer_id']) : null;
        $deposits->assign($deposit, $reviewer, $request->user());

        return back()->with('success', $reviewer ? "Assigned to {$reviewer->name}." : 'Unassigned.');
    }

    public function addNote(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);
        $deposits->addNote($deposit, $request->user(), $data['note']);

        return back()->with('success', 'Note saved.');
    }

    public function refund(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'provider_refund_reference' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $deposits->refund($deposit, $request->user(), $data['reason'], $data['provider_refund_reference'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Deposit refunded.');
    }

    public function reverse(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        try {
            $deposits->reverse($deposit, $request->user(), $data['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Deposit reversed.');
    }

    public function requery(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $deposits->requeryKnownState($deposit, $request->user());

        return back()->with('success', 'Refreshed known provider state.');
    }

    public function reconcile(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $data = $request->validate([
            'status' => ['required', 'in:matched,unmatched,amount_mismatch,provider_pending,manually_reconciled,requires_investigation'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $deposits->reconcile($deposit, $request->user(), $data['status'], $data['note'] ?? null);

        return back()->with('success', 'Reconciliation status updated.');
    }

    public function bulkAction(Request $request, DepositService $deposits, AuditLogger $audit)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['assign', 'export', 'investigate', 'requery', 'reconciliation_batch'])],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
        ]);

        $reviewer = ! empty($data['reviewer_id']) ? User::find($data['reviewer_id']) : null;
        $rows = Deposit::whereIn('id', $data['ids'])->get();

        foreach ($rows as $deposit) {
            match ($data['action']) {
                'assign' => $deposits->assign($deposit, $reviewer, $request->user()),
                'investigate' => $deposits->markForInvestigation($deposit, $request->user(), 'Bulk flagged for investigation.'),
                'requery' => $deposits->requeryKnownState($deposit, $request->user()),
                'reconciliation_batch' => $deposit->update(['reconciliation_status' => 'requires_investigation']),
                default => null,
            };
        }

        $audit->log('deposit.bulk_'.$data['action'], "Bulk {$data['action']} on ".$rows->count().' deposit(s)');

        return back()->with('success', ucfirst($data['action']).' applied to '.$rows->count().' deposit(s).');
    }

    public function exportCsv(Request $request, AuditLogger $audit): StreamedResponse
    {
        $rows = $this->filteredQuery($request, $request->query('tab', 'all'))->get();
        $audit->log('deposit.exported', 'Exported '.$rows->count().' deposit(s) to CSV');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Customer', 'Method', 'Amount', 'Fee', 'Net', 'Currency', 'Automated', 'Status', 'Created', 'Confirmed']);
            foreach ($rows as $d) {
                fputcsv($out, [
                    $d->reference, $d->user?->name, $d->paymentMethod?->name, $d->amount, $d->fee, $d->net_amount,
                    $d->currency, $d->is_automated ? 'Yes' : 'No', $d->status->label(),
                    $d->created_at->toDateTimeString(), $d->confirmed_at?->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, 'deposits-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request, string $tab): Builder
    {
        $query = Deposit::with('user', 'paymentMethod');

        $openStatuses = ['pending', 'processing', 'under_review'];
        match ($tab) {
            'all' => null,
            'open' => $query->whereIn('status', $openStatuses),
            default => $query->where('status', $tab),
        };

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $w) use ($search) {
                $w->where('reference', 'like', "%{$search}%")
                    ->orWhere('provider_reference', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if ($method = $request->query('payment_method_id')) {
            $query->where('payment_method_id', $method);
        }
        if ($provider = $request->query('provider_code')) {
            $query->where('provider_code', $provider);
        }
        if ($currency = $request->query('currency')) {
            $query->where('currency', $currency);
        }
        if ($country = $request->query('country_id')) {
            $query->whereHas('user', fn ($u) => $u->where('country_id', $country));
        }
        if ($min = $request->query('amount_min')) {
            $query->where('amount', '>=', $min);
        }
        if ($max = $request->query('amount_max')) {
            $query->where('amount', '<=', $max);
        }
        if ($automation = $request->query('automation')) {
            $query->where('is_automated', $automation === 'automated');
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
            'amount_desc' => $query->orderByDesc('amount'),
            'amount_asc' => $query->orderBy('amount'),
            default => $query->latest('created_at'),
        };
    }

    private function tabCounts(): array
    {
        $counts = Deposit::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $out = ['all' => Deposit::count()];
        foreach (DepositStatus::cases() as $case) {
            $out[$case->value] = (int) ($counts[$case->value] ?? 0);
        }

        return $out;
    }

    private function summary(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $valueToday = (float) Deposit::where('status', 'confirmed')->where('confirmed_at', '>=', $today)->sum('net_amount');
        $valueYesterday = (float) Deposit::where('status', 'confirmed')->whereBetween('confirmed_at', [$yesterday, $today])->sum('net_amount');

        return [
            'total' => Deposit::count(),
            'confirmed' => Deposit::where('status', 'confirmed')->count(),
            'pending' => Deposit::where('status', 'pending')->count(),
            'under_review' => Deposit::where('status', 'under_review')->count(),
            'failed' => Deposit::where('status', 'failed')->count(),
            'reversed' => Deposit::where('status', 'reversed')->count(),
            'refunded' => Deposit::where('status', 'refunded')->count(),
            'value_today' => $valueToday,
            'value_today_change' => $valueYesterday > 0 ? round((($valueToday - $valueYesterday) / $valueYesterday) * 100, 1) : null,
            'pending_value' => (float) Deposit::whereIn('status', ['pending', 'processing', 'under_review'])->sum('net_amount'),
            'average_amount' => (float) Deposit::where('status', 'confirmed')->avg('net_amount'),
        ];
    }
}
