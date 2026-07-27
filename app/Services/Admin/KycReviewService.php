<?php

namespace App\Services\Admin;

use App\Enums\KycDecisionType;
use App\Enums\KycPriority;
use App\Enums\KycVerificationStatus;
use App\Models\KycDecision;
use App\Models\KycVerification;
use App\Models\RiskFlag;
use App\Models\User;
use App\Notifications\KycReviewed;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Case-management and decision logic for the KYC review workspace. Locking,
 * assignment, decisions and analytics all live here so the controller stays a
 * thin HTTP layer, matching the DashboardReportService pattern used elsewhere
 * in the admin panel.
 */
class KycReviewService
{
    const LOCK_MINUTES = 15;

    public function __construct(private AuditLogger $audit)
    {
    }

    // ---------------------------------------------------------------- locking

    public function acquireLock(KycVerification $kyc, User $actor): bool
    {
        if ($kyc->lockedByOther($actor->id)) {
            return false;
        }

        $kyc->forceFill(['locked_by' => $actor->id, 'locked_at' => now()])->save();

        return true;
    }

    public function releaseLock(KycVerification $kyc, User $actor): void
    {
        if ($kyc->locked_by === $actor->id) {
            $kyc->forceFill(['locked_by' => null, 'locked_at' => null])->save();
        }
    }

    public function heartbeat(KycVerification $kyc, User $actor): bool
    {
        if ($kyc->locked_by !== null && $kyc->locked_by !== $actor->id && $kyc->isLocked()) {
            return false;
        }

        $kyc->forceFill(['locked_by' => $actor->id, 'locked_at' => now()])->save();

        return true;
    }

    // ------------------------------------------------------------- assignment

    public function assign(KycVerification $kyc, ?User $assignee, User $actor): void
    {
        $kyc->forceFill(['assigned_to' => $assignee?->id])->save();

        $this->audit->log(
            $assignee ? 'admin.kyc.assigned' : 'admin.kyc.unassigned',
            $assignee ? "Assigned KYC case #{$kyc->id} to {$assignee->email}" : "Unassigned KYC case #{$kyc->id}",
            $kyc,
            [],
            $actor->id,
        );
    }

    public function bulkAssign(array $kycIds, ?User $assignee, User $actor): int
    {
        $cases = KycVerification::whereIn('id', $kycIds)->get();
        foreach ($cases as $kyc) {
            $this->assign($kyc, $assignee, $actor);
        }

        return $cases->count();
    }

    public function setPriority(KycVerification $kyc, ?KycPriority $priority, User $actor): void
    {
        $kyc->forceFill(['priority' => $priority])->save();
        $this->audit->log('admin.kyc.priority_set', "Set priority to ".($priority?->value ?? 'auto')." for case #{$kyc->id}", $kyc, [], $actor->id);
    }

    // ------------------------------------------------------------- decisions

    /**
     * @param  array{internal_reason?:?string, customer_message?:?string, reason_template_id?:?int, metadata?:array}  $data
     */
    public function recordDecision(KycVerification $kyc, KycDecisionType $type, User $actor, array $data = []): KycDecision
    {
        return DB::transaction(function () use ($kyc, $type, $actor, $data) {
            $decision = KycDecision::create([
                'kyc_verification_id' => $kyc->id,
                'actor_id' => $actor->id,
                'decision_type' => $type,
                'reason_template_id' => $data['reason_template_id'] ?? null,
                'internal_reason' => $data['internal_reason'] ?? null,
                'customer_message' => $data['customer_message'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->applyDecisionEffects($kyc, $type, $actor, $data, $decision);

            $this->audit->log('admin.kyc.'.$type->value, "KYC case #{$kyc->id} decision: {$type->label()}", $kyc, [
                'internal_reason' => $data['internal_reason'] ?? null,
            ], $actor->id);

            $this->releaseLock($kyc, $actor);

            return $decision;
        });
    }

    private function applyDecisionEffects(KycVerification $kyc, KycDecisionType $type, User $actor, array $data, KycDecision $decision): void
    {
        $status = $type->resultingStatus();
        $user = $kyc->user;

        if ($status !== null) {
            $kyc->forceFill(['status' => $status]);

            if ($type->isFinalIdentityDecision()) {
                $kyc->forceFill(['reviewed_by' => $actor->id, 'reviewed_at' => now()]);
            }
            if ($type === KycDecisionType::Reject) {
                $kyc->forceFill(['rejection_reason' => $data['customer_message'] ?? $data['internal_reason'] ?? null]);
            }

            $kyc->save();
        }

        match ($type) {
            KycDecisionType::Approve => $this->approve($user, $kyc),
            KycDecisionType::ApproveLimited => $user?->update([
                'kyc_status' => 'approved',
                'kyc_level' => max($user->kyc_level, min($kyc->target_level, 1)),
            ]),
            KycDecisionType::Reject => $user?->update(['kyc_status' => 'rejected']),
            KycDecisionType::RequestMoreInfo, KycDecisionType::ReturnForCorrection => $user?->update(['kyc_status' => 'pending']),
            KycDecisionType::FlagSuspicious => $this->flagSuspicious($kyc, $data),
            KycDecisionType::FreezeAccount => $this->freezeAccount($user, $actor),
            default => null,
        };

        if (in_array($type, [KycDecisionType::Approve, KycDecisionType::ApproveLimited, KycDecisionType::Reject, KycDecisionType::RequestMoreInfo, KycDecisionType::ReturnForCorrection], true) && $user) {
            $user->notify(new KycReviewed($kyc, in_array($type, [KycDecisionType::Approve, KycDecisionType::ApproveLimited], true), $data['customer_message'] ?? $data['internal_reason'] ?? null, $type));
        }
    }

    private function approve(?User $user, KycVerification $kyc): void
    {
        if (! $user) {
            return;
        }

        $wasVerified = $user->kyc_level >= 2;

        $user->update([
            'kyc_status' => 'approved',
            'kyc_level' => max($user->kyc_level, $kyc->target_level),
        ]);

        // Referral payout, once, the first time this user reaches full (L2) verification.
        if (! $wasVerified && $user->kyc_level >= 2 && $user->referred_by) {
            $user->increment('points', config('platform.referrals.referred_points'));
            User::whereKey($user->referred_by)->increment('points', config('platform.referrals.referrer_points'));
        }
    }

    private function flagSuspicious(KycVerification $kyc, array $data): void
    {
        $flag = new RiskFlag([
            'rule_code' => 'manual_kyc_review',
            'severity' => $data['severity'] ?? 'high',
            'reason' => $data['internal_reason'] ?? 'Flagged suspicious during KYC review.',
            'status' => 'open',
            'context' => ['kyc_verification_id' => $kyc->id],
        ]);
        $flag->user()->associate($kyc->user);
        $flag->flaggable()->associate($kyc);
        $flag->save();
    }

    private function freezeAccount(?User $user, User $actor): void
    {
        if (! $user) {
            return;
        }

        $wallet = $user->primaryWallet();
        if ($wallet && $wallet->status !== 'frozen') {
            $wallet->status = 'frozen';
            $wallet->save();
            $this->audit->log('admin.wallet.frozen', "Wallet frozen for {$user->email} (from KYC review)", $user, [], $actor->id);
        }
    }

    // ----------------------------------------------------------- review checks

    public function updateReviewCheck(KycVerification $kyc, string $key, array $payload, User $actor): void
    {
        $checks = $kyc->review_checks ?? [];
        $checks[$key] = array_merge($payload, [
            'checked_by' => $actor->id,
            'checked_at' => now()->toIso8601String(),
        ]);
        $kyc->forceFill(['review_checks' => $checks])->save();

        $this->audit->log('admin.kyc.review_check', "Recorded {$key} check for case #{$kyc->id}", $kyc, ['key' => $key, 'status' => $payload['status'] ?? null], $actor->id);
    }

    // --------------------------------------------------------------- helpers

    public function waitingHours(KycVerification $kyc): int
    {
        $since = $kyc->status->isOpen() ? $kyc->created_at : ($kyc->reviewed_at ?? $kyc->updated_at);

        return (int) $since->diffInHours(now());
    }

    public function effectivePriority(KycVerification $kyc): KycPriority
    {
        if ($kyc->priority) {
            return $kyc->priority;
        }

        if ($kyc->is_pep || $kyc->riskFlags()->open()->exists()) {
            return KycPriority::High;
        }

        $hours = $this->waitingHours($kyc);
        if ($hours >= KycPriority::High->slaHours()) {
            return KycPriority::High;
        }

        return KycPriority::Medium;
    }

    public function slaBreached(KycVerification $kyc): bool
    {
        if (! $kyc->status->isOpen()) {
            return false;
        }

        return $this->waitingHours($kyc) >= $this->effectivePriority($kyc)->slaHours();
    }

    // ------------------------------------------------------------- analytics

    public function queueCounts(): array
    {
        $counts = KycVerification::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');

        $out = [];
        foreach (KycVerificationStatus::cases() as $case) {
            $out[$case->value] = (int) ($counts[$case->value] ?? 0);
        }
        $out['unassigned'] = KycVerification::whereNull('assigned_to')->whereIn('status', ['pending', 'in_review'])->count();
        $out['all_open'] = KycVerification::whereIn('status', array_map(fn ($c) => $c->value, array_filter(KycVerificationStatus::cases(), fn ($c) => $c->isOpen())))->count();
        $out['all'] = KycVerification::count();
        $out['approved_today'] = KycDecision::whereIn('decision_type', ['approve', 'approve_limited'])->whereDate('created_at', now()->toDateString())->count();
        $out['rejected_today'] = KycDecision::where('decision_type', 'reject')->whereDate('created_at', now()->toDateString())->count();
        $out['expiring_soon'] = $this->expiringDocuments(30)->count();
        $out['sla_breaches'] = $this->countSlaBreaches();
        $out['avg_review_hours'] = $this->avgReviewHours(30);

        return $out;
    }

    public function countSlaBreaches(): int
    {
        return KycVerification::with('riskFlags')
            ->whereIn('status', array_map(fn ($c) => $c->value, array_filter(KycVerificationStatus::cases(), fn ($c) => $c->isOpen())))
            ->get()
            ->filter(fn ($k) => $this->slaBreached($k))
            ->count();
    }

    public function avgReviewHours(int $days): ?float
    {
        $decisions = KycDecision::with('kycVerification')
            ->whereIn('decision_type', ['approve', 'approve_limited', 'reject'])
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $hours = $decisions->map(fn ($d) => $d->kycVerification ? $d->kycVerification->created_at->diffInHours($d->created_at) : null)->filter(fn ($h) => $h !== null);

        return $hours->count() ? round($hours->avg(), 1) : null;
    }

    public function reviewerPerformance(Carbon $since): array
    {
        return KycDecision::with('actor')
            ->where('created_at', '>=', $since)
            ->whereIn('decision_type', ['approve', 'approve_limited', 'reject'])
            ->get()
            ->groupBy('actor_id')
            ->map(function ($decisions, $actorId) {
                $actor = $decisions->first()->actor;
                $approved = $decisions->whereIn('decision_type', [KycDecisionType::Approve, KycDecisionType::ApproveLimited])->count();
                $rejected = $decisions->where('decision_type', KycDecisionType::Reject)->count();

                $avgHours = $decisions->map(function ($d) {
                    $kyc = KycVerification::find($d->kyc_verification_id);

                    return $kyc ? $kyc->created_at->diffInHours($d->created_at) : null;
                })->filter(fn ($h) => $h !== null);

                return [
                    'reviewer' => $actor?->name ?? 'Unknown',
                    'decisions' => $decisions->count(),
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'avg_hours' => $avgHours->count() ? round($avgHours->avg(), 1) : null,
                ];
            })
            ->values()
            ->all();
    }

    public function expiringDocuments(int $withinDays = 30): \Illuminate\Support\Collection
    {
        return KycVerification::with('user')
            ->whereNotNull('document_expiry_date')
            ->whereDate('document_expiry_date', '<=', now()->addDays($withinDays))
            ->orderBy('document_expiry_date')
            ->get();
    }

    public function backlogTrend(int $days = 14): array
    {
        $rows = KycVerification::select(DB::raw("date(created_at) as d"), DB::raw('count(*) as c'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('d')
            ->pluck('c', 'd');

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $out[] = ['date' => $date, 'count' => (int) ($rows[$date] ?? 0)];
        }

        return $out;
    }
}
