<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BeneficiaryAccount;
use App\Services\Admin\BeneficiaryReviewService;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BeneficiaryController extends Controller
{
    public function index(Request $request, BeneficiaryReviewService $svc): View
    {
        $tab = $request->query('tab', 'all');
        $items = $this->filteredQuery($request, $tab)->paginate(20)->withQueryString();

        return view('admin.beneficiaries.index', [
            'items' => $items,
            'tab' => $tab,
            'q' => $request->query('q', ''),
            'counts' => $svc->tabCounts(),
            'appTypes' => config('funding.apps', []),
            'countries' => \App\Models\Country::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function rowDetail(BeneficiaryAccount $beneficiary, BeneficiaryReviewService $svc)
    {
        $beneficiary->load(['user.country', 'reviewedBy', 'events.actor']);

        $history = BeneficiaryAccount::where('user_id', $beneficiary->user_id)
            ->where('id', '!=', $beneficiary->id)
            ->latest()
            ->get();

        return response()->json([
            'account' => [
                'id' => $beneficiary->id,
                'customer' => $beneficiary->user?->name,
                'email' => $beneficiary->user?->email,
                'phone' => $this->maskPhone($beneficiary->user?->phone),
                'user_id' => $beneficiary->user_id,
                'country' => $beneficiary->user?->country?->name,
                'kyc_level' => $beneficiary->user?->kyc_level,
                'app_type' => $beneficiary->app_type->label(),
                'account_name' => $beneficiary->account_name,
                'account_id_masked' => $this->maskIdentifier($beneficiary->account_id),
                'is_default' => $beneficiary->is_default,
                'submitted' => $beneficiary->created_at->format('M j, Y'),
                'status' => $beneficiary->status->value,
                'status_label' => $beneficiary->status->label(),
                'rejection_reason' => $beneficiary->rejection_reason,
                'resubmission_allowed' => $beneficiary->resubmission_allowed,
                'admin_notes' => $beneficiary->admin_notes,
                'name_match' => $beneficiary->nameMatch(),
                'has_qr' => (bool) $beneficiary->qr_path,
                'checklist' => [
                    'identity_verified' => $beneficiary->checklistItem('identity_verified'),
                    'name_matches' => $beneficiary->checklistItem('name_matches'),
                    'identifier_provided' => $beneficiary->checklistItem('identifier_provided'),
                    'qr_readable' => $beneficiary->checklistItem('qr_readable'),
                    'qr_matches_app' => $beneficiary->checklistItem('qr_matches_app'),
                    'duplicate_check' => $beneficiary->checklistItem('duplicate_check'),
                    'not_linked_elsewhere' => $beneficiary->checklistItem('not_linked_elsewhere'),
                    'supporting_info' => $beneficiary->checklistItem('supporting_info'),
                ],
            ],
            'duplicates' => $svc->findDuplicates($beneficiary),
            'funding' => $beneficiary->status->value === 'approved' ? $svc->fundingSummary($beneficiary) : null,
            'history' => $history->map(fn ($h) => [
                'submitted' => $h->created_at->format('M j, Y'),
                'app_type' => $h->app_type->label(),
                'status' => $h->status->label(),
            ]),
            'events' => $beneficiary->events->map(fn ($e) => [
                'event' => $e->event,
                'reason' => $e->reason,
                'actor' => $e->actor?->name ?? 'System',
                'at' => $e->created_at->format('M j, Y g:ia'),
            ]),
        ]);
    }

    public function approve(BeneficiaryAccount $beneficiary, BeneficiaryReviewService $svc, Request $request)
    {
        $svc->approve($beneficiary, $request->user());

        return back()->with('success', 'China wallet approved.');
    }

    public function reject(Request $request, BeneficiaryAccount $beneficiary, BeneficiaryReviewService $svc)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:60'],
            'resubmission_allowed' => ['nullable', 'boolean'],
        ]);
        $svc->reject($beneficiary, $request->user(), $data['reason'], $data['category'] ?? null, $request->boolean('resubmission_allowed'));

        return back()->with('success', 'China wallet rejected.');
    }

    public function suspend(Request $request, BeneficiaryAccount $beneficiary, BeneficiaryReviewService $svc)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $svc->suspend($beneficiary, $request->user(), $data['reason']);

        return back()->with('success', 'China wallet suspended.');
    }

    public function restore(Request $request, BeneficiaryAccount $beneficiary, BeneficiaryReviewService $svc)
    {
        $svc->restore($beneficiary, $request->user());

        return back()->with('success', 'China wallet restored.');
    }

    public function requestInfo(Request $request, BeneficiaryAccount $beneficiary, BeneficiaryReviewService $svc)
    {
        $data = $request->validate([
            'reason_key' => ['required', 'in:name_missing,identifier_missing,qr_unclear,wrong_app,name_mismatch,duplicate,screenshot_required,custom'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
        $svc->requestInfo($beneficiary, $request->user(), $data['reason_key'], $data['message'] ?? null);

        return back()->with('success', 'Request sent to the customer.');
    }

    public function reviewCheck(Request $request, BeneficiaryAccount $beneficiary, BeneficiaryReviewService $svc)
    {
        $data = $request->validate([
            'key' => ['required', 'in:identity_verified,name_matches,identifier_provided,qr_readable,qr_matches_app,duplicate_check,not_linked_elsewhere,supporting_info'],
            'status' => ['required', 'in:passed,warning,failed,not_checked'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $svc->updateChecklistItem($beneficiary, $data['key'], $data['status'], $data['notes'] ?? null, $request->user());

        return back()->with('success', 'Checklist updated.');
    }

    public function updateNotes(Request $request, BeneficiaryAccount $beneficiary, BeneficiaryReviewService $svc)
    {
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:5000']]);
        $svc->addNote($beneficiary, $request->user(), $data['admin_notes'] ?? '');

        return back()->with('success', 'Note saved.');
    }

    public function revealField(Request $request, BeneficiaryAccount $beneficiary, AuditLogger $audit)
    {
        $audit->log('admin.beneficiary.field_revealed', "Revealed wallet identifier for #{$beneficiary->id}", $beneficiary);

        return response()->json(['account_id' => $beneficiary->account_id]);
    }

    public function bulkAction(Request $request, BeneficiaryReviewService $svc, AuditLogger $audit)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['assign', 'export', 'suspend', 'restore', 'archive'])],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $accounts = BeneficiaryAccount::whereIn('id', $data['ids'])->get();

        foreach ($accounts as $account) {
            match ($data['action']) {
                'assign' => $account->update(['status' => 'in_review']),
                'suspend' => $svc->suspend($account, $request->user(), $data['reason'] ?? 'Bulk suspension.'),
                'restore' => $svc->restore($account, $request->user()),
                'archive' => $account->delete(),
                default => null,
            };
            $audit->log('admin.beneficiary.bulk_'.$data['action'], "Bulk {$data['action']} on wallet #{$account->id}", $account);
        }

        return back()->with('success', ucfirst($data['action']).' applied to '.$accounts->count().' account(s).');
    }

    public function destroy(Request $request, BeneficiaryAccount $beneficiary, AuditLogger $audit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $audit->log('admin.beneficiary.archived', "Archived China wallet {$beneficiary->account_id}", $beneficiary, $data);
        $beneficiary->delete();

        return redirect()->route('admin.beneficiaries.index')->with('success', 'Wallet account archived.');
    }

    public function exportCsv(Request $request, AuditLogger $audit): StreamedResponse
    {
        $rows = $this->filteredQuery($request, $request->query('tab', 'all'))->get();
        $audit->log('admin.beneficiary.exported', 'Exported '.$rows->count().' China wallet account(s) to CSV');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Customer', 'Email', 'Wallet app', 'Account name', 'Account identifier', 'Status', 'Submitted']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id, $r->user?->name, $r->user?->email, $r->app_type->label(),
                    $r->account_name, $this->maskIdentifier($r->account_id), $r->status->label(), $r->created_at->toDateString(),
                ]);
            }
            fclose($out);
        }, 'china-wallets-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request, string $tab): Builder
    {
        $query = BeneficiaryAccount::with('user');

        match ($tab) {
            'pending', 'approved', 'rejected', 'suspended' => $query->where('status', $tab),
            default => null,
        };

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $w) use ($search) {
                $w->where('account_name', 'like', "%{$search}%")
                    ->orWhere('account_id', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if ($appType = $request->query('app_type')) {
            $query->where('app_type', $appType);
        }
        if ($country = $request->query('country_id')) {
            $query->whereHas('user', fn ($u) => $u->where('country_id', $country));
        }
        if ($level = $request->query('kyc_level')) {
            $query->whereHas('user', fn ($u) => $u->where('kyc_level', $level));
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->latest();
    }

    private function maskIdentifier(?string $value): string
    {
        if (! $value) {
            return '—';
        }
        $len = strlen($value);

        return $len <= 4 ? str_repeat('*', $len) : substr($value, 0, 2).str_repeat('*', max(2, $len - 4)).substr($value, -2);
    }

    private function maskPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $len = strlen($phone);

        return $len <= 4 ? $phone : str_repeat('*', $len - 3).substr($phone, -3);
    }
}
