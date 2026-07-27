<?php

namespace App\Services\Admin;

use App\Models\BeneficiaryAccount;
use App\Models\User;
use App\Notifications\AdminMessage;
use App\Notifications\BeneficiaryReviewed;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Review/decision logic for China wallet accounts. Deliberately leaner than the
 * KYC workspace's service (no case locking/assignment/templates) since this page
 * is intentionally the simplest of the admin review pages.
 */
class BeneficiaryReviewService
{
    public function __construct(private AuditLogger $audit)
    {
    }

    public function recordEvent(BeneficiaryAccount $account, string $event, ?User $actor, ?string $reason = null): void
    {
        $account->events()->create([
            'actor_id' => $actor?->id,
            'event' => $event,
            'reason' => $reason,
        ]);
    }

    public function approve(BeneficiaryAccount $account, User $actor): void
    {
        $account->update(['status' => 'approved', 'reviewed_by' => $actor->id, 'reviewed_at' => now()]);
        $this->recordEvent($account, 'approved', $actor);
        $this->audit->log('admin.beneficiary.approved', "Approved China wallet {$account->account_id}", $account);
        $account->user?->notify(new BeneficiaryReviewed($account, true));
    }

    public function reject(BeneficiaryAccount $account, User $actor, string $reason, ?string $category, bool $resubmissionAllowed): void
    {
        $account->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejection_category' => $category,
            'resubmission_allowed' => $resubmissionAllowed,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);
        $this->recordEvent($account, 'rejected', $actor, $reason);
        $this->audit->log('admin.beneficiary.rejected', "Rejected China wallet {$account->account_id}", $account, ['reason' => $reason, 'category' => $category]);
        $account->user?->notify(new BeneficiaryReviewed($account, false, $reason));
    }

    public function suspend(BeneficiaryAccount $account, User $actor, string $reason): void
    {
        $account->update(['status' => 'suspended']);
        $this->recordEvent($account, 'suspended', $actor, $reason);
        $this->audit->log('admin.beneficiary.suspended', "Suspended China wallet {$account->account_id}", $account, ['reason' => $reason]);
        $account->user?->notify(new AdminMessage('Your China wallet account has been suspended', $reason, true));
    }

    public function restore(BeneficiaryAccount $account, User $actor): void
    {
        $account->update(['status' => 'approved']);
        $this->recordEvent($account, 'restored', $actor);
        $this->audit->log('admin.beneficiary.restored', "Restored China wallet {$account->account_id}", $account);
        $account->user?->notify(new AdminMessage('Your China wallet account has been restored', 'Your wallet account is active again and can be used for funding.', true));
    }

    public function requestInfo(BeneficiaryAccount $account, User $actor, string $reasonKey, ?string $customMessage): void
    {
        $labels = [
            'name_missing' => 'The wallet account name is missing.',
            'identifier_missing' => 'The wallet account identifier is missing.',
            'qr_unclear' => 'The submitted QR code is not clear enough to read.',
            'wrong_app' => 'The wrong wallet app appears to be selected.',
            'name_mismatch' => 'The wallet account name does not match your verified name.',
            'duplicate' => 'This wallet account appears to already be linked to another user.',
            'screenshot_required' => 'An additional screenshot is required.',
            'custom' => $customMessage ?: 'Please provide more information.',
        ];
        $message = $labels[$reasonKey] ?? ($customMessage ?: 'Please provide more information.');

        $account->update(['status' => 'more_info_requested']);
        $this->recordEvent($account, 'info_requested', $actor, $message);
        $this->audit->log('admin.beneficiary.info_requested', "Requested more info for China wallet {$account->account_id}", $account, ['reason' => $reasonKey]);
        $account->user?->notify(new AdminMessage('More information needed for your China wallet', $message, true));
    }

    public function updateChecklistItem(BeneficiaryAccount $account, string $key, string $status, ?string $notes, User $actor): void
    {
        $checklist = $account->review_checklist ?? [];
        $checklist[$key] = array_filter([
            'status' => $status,
            'notes' => $notes,
            'checked_by' => $actor->id,
            'checked_at' => now()->toIso8601String(),
        ], fn ($v) => $v !== null);
        $account->update(['review_checklist' => $checklist]);
    }

    public function addNote(BeneficiaryAccount $account, User $actor, string $note): void
    {
        $account->update(['admin_notes' => $note]);
        $this->recordEvent($account, 'note_added', $actor, $note);
    }

    /**
     * Live duplicate check — never stored, always computed fresh. Flags but never
     * auto-rejects; the admin always makes the final call.
     *
     * @return array<int, array{beneficiary_account_id:int, user:string, match:string}>
     */
    public function findDuplicates(BeneficiaryAccount $account): array
    {
        $matches = collect();

        $byIdentifier = BeneficiaryAccount::with('user')
            ->where('id', '!=', $account->id)
            ->where('user_id', '!=', $account->user_id)
            ->where('account_id', $account->account_id)
            ->where('app_type', $account->app_type)
            ->get()
            ->map(fn ($m) => ['beneficiary_account_id' => $m->id, 'user' => $m->user?->name ?? 'Unknown', 'match' => 'Same wallet account identifier']);

        $matches = $matches->concat($byIdentifier);

        if ($account->user?->phone) {
            $byPhone = BeneficiaryAccount::with('user')
                ->where('id', '!=', $account->id)
                ->where('user_id', '!=', $account->user_id)
                ->whereHas('user', fn ($q) => $q->where('phone', $account->user->phone))
                ->get()
                ->map(fn ($m) => ['beneficiary_account_id' => $m->id, 'user' => $m->user?->name ?? 'Unknown', 'match' => 'Same phone number on file']);

            $matches = $matches->concat($byPhone);
        }

        return $matches->unique('beneficiary_account_id')->values()->all();
    }

    /**
     * @return array{total:int, successful:int, pending:int, failed:int, total_funded:float, last_request:?string, last_successful:?string}
     */
    public function fundingSummary(BeneficiaryAccount $account): array
    {
        $requests = $account->fundingRequests();

        return [
            'total' => (clone $requests)->count(),
            'successful' => (clone $requests)->where('status', 'funding_successful')->count(),
            'pending' => (clone $requests)->whereIn('status', ['payment_pending', 'payment_successful', 'funding_processing', 'manual_review'])->count(),
            'failed' => (clone $requests)->whereIn('status', ['funding_failed', 'refunded'])->count(),
            'total_funded' => (float) (clone $requests)->where('status', 'funding_successful')->sum('target_amount'),
            'last_request' => (clone $requests)->latest()->value('created_at'),
            'last_successful' => (clone $requests)->where('status', 'funding_successful')->latest()->value('created_at'),
        ];
    }

    public function tabCounts(): array
    {
        $counts = BeneficiaryAccount::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');

        return [
            'all' => BeneficiaryAccount::count(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'in_review' => (int) ($counts['in_review'] ?? 0),
            'more_info_requested' => (int) ($counts['more_info_requested'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'suspended' => (int) ($counts['suspended'] ?? 0),
            'needs_update' => (int) ($counts['more_info_requested'] ?? 0),
        ];
    }
}
