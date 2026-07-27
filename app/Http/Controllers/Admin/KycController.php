<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycDecisionType;
use App\Enums\KycPriority;
use App\Enums\KycVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Deposit;
use App\Models\FundingRequest;
use App\Models\KycDecisionTemplate;
use App\Models\KycVerification;
use App\Models\User;
use App\Services\Admin\KycReviewService;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KycController extends Controller
{
    public function index(Request $request, KycReviewService $svc): View
    {
        $status = $request->query('status', 'open');
        $items = $this->filteredQuery($request, $status)
            ->paginate(20)
            ->withQueryString();

        $view = $request->query('view', 'queue');

        return view('admin.kyc.index', [
            'items' => $items,
            'status' => $status,
            'q' => $request->query('q', ''),
            'counts' => $svc->queueCounts(),
            'countries' => Country::orderBy('name')->get(['id', 'name']),
            'view' => $view,
            'analytics' => $view === 'analytics' ? [
                'reviewers' => $svc->reviewerPerformance(now()->subDays(30)),
                'trend' => $svc->backlogTrend(14),
                'expiring' => $svc->expiringDocuments(30),
            ] : null,
        ]);
    }

    public function show(KycVerification $kyc, KycReviewService $svc): View
    {
        $kyc->load(['user', 'country', 'reviewedBy', 'assignedTo', 'lockedBy', 'decisions.actor', 'decisions.reasonTemplate', 'notes.user', 'riskFlags']);

        $history = KycVerification::with('country')
            ->where('user_id', $kyc->user_id)
            ->where('id', '!=', $kyc->id)
            ->latest()
            ->get();

        $timeline = collect()
            ->concat($kyc->decisions->map(fn ($d) => [
                'type' => 'decision',
                'at' => $d->created_at,
                'label' => $d->decision_type->label(),
                'actor' => $d->actor?->name ?? 'System',
                'detail' => $d->internal_reason,
            ]))
            ->concat($kyc->notes->map(fn ($n) => [
                'type' => 'note',
                'at' => $n->created_at,
                'label' => 'Reviewer note',
                'actor' => $n->user?->name ?? 'Unknown',
                'detail' => $n->body,
            ]))
            ->push([
                'type' => 'submitted',
                'at' => $kyc->created_at,
                'label' => 'Documents submitted',
                'actor' => $kyc->user?->name ?? 'Applicant',
                'detail' => null,
            ])
            ->sortByDesc('at')
            ->values();

        $templates = KycDecisionTemplate::active()->orderBy('name')->get()->groupBy(fn ($t) => $t->decision_type->value);

        $riskSignals = [
            'account_age_days' => $kyc->user ? (int) $kyc->user->created_at->diffInDays(now()) : null,
            'previous_submissions' => $history->count(),
            'previous_rejections' => $history->where('status', KycVerificationStatus::Rejected)->count(),
            'open_risk_flags' => $kyc->riskFlags->where('status', 'open')->count(),
            'country_mismatch' => (bool) ($kyc->user && $kyc->user->country_id && $kyc->country_id && $kyc->user->country_id !== $kyc->country_id),
            'lifetime_deposits' => (float) Deposit::where('user_id', $kyc->user_id)->where('status', 'confirmed')->sum('net_amount'),
            'lifetime_funding' => (float) FundingRequest::where('user_id', $kyc->user_id)->where('status', 'funding_successful')->sum('target_amount'),
        ];

        return view('admin.kyc.show', [
            'kyc' => $kyc,
            'history' => $history,
            'timeline' => $timeline,
            'templates' => $templates,
            'riskSignals' => $riskSignals,
            'waitingHours' => $svc->waitingHours($kyc),
            'priority' => $svc->effectivePriority($kyc),
            'slaBreached' => $svc->slaBreached($kyc),
            'lockedByOther' => $kyc->lockedByOther(Auth::id()),
            'reviewers' => User::whereIn('role', ['admin', 'super_admin'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function decide(Request $request, KycVerification $kyc, KycReviewService $svc)
    {
        abort_if($kyc->lockedByOther($request->user()->id), 423, 'This case is currently locked by another reviewer.');

        $data = $request->validate([
            'decision_type' => ['required', Rule::enum(KycDecisionType::class)],
            'reason_template_id' => ['nullable', 'exists:kyc_decision_templates,id'],
            'internal_reason' => ['nullable', 'string', 'max:2000'],
            'customer_message' => ['nullable', 'string', 'max:2000'],
            'severity' => ['nullable', 'in:low,medium,high,critical'],
        ]);

        $type = KycDecisionType::from($data['decision_type']);

        if ($type->requiresCustomerMessage() && empty($data['customer_message'])) {
            return back()->withErrors(['customer_message' => 'A customer-facing message is required for this decision.'])->withInput();
        }
        if (in_array($type, [KycDecisionType::Reject, KycDecisionType::Escalate, KycDecisionType::FlagSuspicious], true) && empty($data['internal_reason'])) {
            return back()->withErrors(['internal_reason' => 'An internal reason is required for this decision.'])->withInput();
        }

        $svc->recordDecision($kyc, $type, $request->user(), $data);

        return redirect()->route('admin.kyc.index')->with('success', 'Decision recorded: '.$type->label());
    }

    public function assign(Request $request, KycVerification $kyc, KycReviewService $svc)
    {
        $data = $request->validate(['assignee_id' => ['nullable', 'exists:users,id']]);
        $assignee = ! empty($data['assignee_id']) ? User::find($data['assignee_id']) : null;
        $svc->assign($kyc, $assignee, $request->user());

        return back()->with('success', $assignee ? "Assigned to {$assignee->name}." : 'Unassigned.');
    }

    public function bulkAssign(Request $request, KycReviewService $svc)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'assignee_id' => ['nullable', 'exists:users,id'],
        ]);
        $assignee = ! empty($data['assignee_id']) ? User::find($data['assignee_id']) : null;
        $count = $svc->bulkAssign($data['ids'], $assignee, $request->user());

        return back()->with('success', "Assigned {$count} case(s).");
    }

    public function bulkPriority(Request $request, KycReviewService $svc)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'priority' => ['required', Rule::enum(KycPriority::class)],
        ]);

        $priority = KycPriority::from($data['priority']);
        foreach (KycVerification::whereIn('id', $data['ids'])->get() as $kyc) {
            $svc->setPriority($kyc, $priority, $request->user());
        }

        return back()->with('success', 'Priority updated for '.count($data['ids']).' case(s).');
    }

    public function setPriority(Request $request, KycVerification $kyc, KycReviewService $svc)
    {
        $data = $request->validate(['priority' => ['nullable', Rule::enum(KycPriority::class)]]);
        $svc->setPriority($kyc, ! empty($data['priority']) ? KycPriority::from($data['priority']) : null, $request->user());

        return back()->with('success', 'Priority updated.');
    }

    public function lock(Request $request, KycVerification $kyc, KycReviewService $svc)
    {
        $ok = $svc->acquireLock($kyc, $request->user());

        return response()->json(['locked' => $ok, 'locked_by' => $ok ? null : $kyc->fresh()->lockedBy?->name]);
    }

    public function unlock(Request $request, KycVerification $kyc, KycReviewService $svc)
    {
        $svc->releaseLock($kyc, $request->user());

        return response()->json(['ok' => true]);
    }

    public function heartbeat(Request $request, KycVerification $kyc, KycReviewService $svc)
    {
        return response()->json(['ok' => $svc->heartbeat($kyc, $request->user())]);
    }

    public function storeNote(Request $request, KycVerification $kyc)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);
        $kyc->notes()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        return back()->with('success', 'Note added.');
    }

    public function setDocumentExpiry(Request $request, KycVerification $kyc, AuditLogger $audit)
    {
        $data = $request->validate(['document_expiry_date' => ['nullable', 'date']]);
        $kyc->update($data);
        $audit->log('admin.kyc.expiry_recorded', "Recorded document expiry for case #{$kyc->id}", $kyc, $data);

        return back()->with('success', 'Document expiry recorded.');
    }

    public function reviewCheck(Request $request, KycVerification $kyc, KycReviewService $svc)
    {
        $data = $request->validate([
            'key' => ['required', 'in:document_authenticity,face_match,address_verification,aml_screening'],
            'status' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $svc->updateReviewCheck($kyc, $data['key'], collect($data)->except('key')->filter(fn ($v) => $v !== null)->toArray(), $request->user());

        return back()->with('success', 'Review check recorded.');
    }

    public function revealField(Request $request, KycVerification $kyc, AuditLogger $audit)
    {
        $data = $request->validate(['field' => ['required', 'in:document_number,full_name,address']]);
        $audit->log('admin.kyc.field_revealed', "Revealed {$data['field']} for KYC case #{$kyc->id}", $kyc);

        return response()->json(['ok' => true]);
    }

    public function exportCsv(Request $request, AuditLogger $audit): StreamedResponse
    {
        $rows = $this->filteredQuery($request, $request->query('status', 'open'))->get();
        $audit->log('admin.kyc.exported', 'Exported '.$rows->count().' KYC case(s) to CSV');

        $filename = 'kyc-queue-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'User', 'Email', 'Status', 'Priority', 'Document type', 'Document number', 'Country', 'Target level', 'Submitted', 'Assigned to']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->user?->name,
                    $r->user?->email,
                    $r->status->label(),
                    $r->priority?->label() ?? 'Auto',
                    $r->document_type,
                    $this->maskDocNumber($r->document_number),
                    $r->country?->name,
                    $r->target_level,
                    $r->created_at->toDateTimeString(),
                    $r->assignedTo?->name,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request, string $status): Builder
    {
        $query = KycVerification::with(['user', 'country', 'assignedTo', 'reviewedBy']);

        if ($status === 'open') {
            $open = array_map(fn ($c) => $c->value, array_filter(KycVerificationStatus::cases(), fn ($c) => $c->isOpen()));
            $query->whereIn('status', $open);
        } elseif ($status === 'unassigned') {
            $query->whereNull('assigned_to')->whereIn('status', ['pending', 'in_review']);
        } elseif ($status === 'mine') {
            $query->where('assigned_to', $request->user()->id);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $w) use ($search) {
                $w->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhereHas('country', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($country = $request->query('country_id')) {
            $query->where('country_id', $country);
        }
        if ($docType = $request->query('document_type')) {
            $query->where('document_type', $docType);
        }
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }
        if ($request->boolean('is_pep')) {
            $query->where('is_pep', true);
        }
        if ($request->boolean('unassigned_only')) {
            $query->whereNull('assigned_to');
        }
        if ($request->boolean('has_risk_flag')) {
            $query->whereHas('riskFlags', fn ($r) => $r->where('status', 'open'));
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($level = $request->query('target_level')) {
            $query->where('target_level', $level);
        }

        return match ($request->query('sort', 'oldest')) {
            'newest' => $query->latest('created_at'),
            'priority' => $query->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")->oldest('created_at'),
            default => $query->oldest('created_at'),
        };
    }

    private function maskDocNumber(?string $number): string
    {
        if (! $number) {
            return '';
        }
        $len = strlen($number);

        return $len <= 4 ? str_repeat('*', $len) : str_repeat('*', $len - 4).substr($number, -4);
    }
}
