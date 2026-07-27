<?php

namespace App\Services\Security;

use App\Models\SpamReviewCase;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Security\DTO\ContentRiskAssessment;

/**
 * Medium-confidence submissions land here instead of being silently
 * discarded or auto-accepted. Releasing a case as legitimate is handled by
 * the caller (e.g. ContactController::releaseFromReview) since only the
 * caller knows how to recreate the real destination record (Dispute,
 * Review, ...) for that form type.
 */
class SpamReviewService
{
    public function __construct(private AuditLogger $audit) {}

    /** @param  array<string, mixed>  $safePayload */
    public function hold(string $formType, array $safePayload, ContentRiskAssessment $risk, array $meta = []): SpamReviewCase
    {
        return SpamReviewCase::create([
            'form_type' => $formType,
            'status' => 'pending_review',
            'risk_level' => $risk->level,
            'risk_score' => $risk->score,
            'triggered_rules' => $risk->triggeredRules,
            'sender_name' => $safePayload['name'] ?? null,
            'sender_email' => $safePayload['email'] ?? null,
            'safe_payload' => $safePayload,
            'ip_hash' => $meta['ip_hash'] ?? null,
            'country' => $meta['country'] ?? null,
            'user_agent' => $meta['user_agent'] ?? null,
            'fingerprint_hash' => $meta['fingerprint_hash'] ?? null,
        ]);
    }

    public function markLegitimate(SpamReviewCase $case, User $reviewer, ?string $reason = null): void
    {
        $case->update(['status' => 'legitimate', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'decision_reason' => $reason]);
        $this->audit->log('security.spam_review_marked_legitimate', "Spam review case {$case->reference} marked legitimate", $case, ['reviewer' => $reviewer->id]);
    }

    public function markSpam(SpamReviewCase $case, User $reviewer, ?string $reason = null): void
    {
        $case->update(['status' => 'spam', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'decision_reason' => $reason]);
        $this->audit->log('security.spam_review_marked_spam', "Spam review case {$case->reference} marked spam", $case, ['reviewer' => $reviewer->id]);
    }

    public function archive(SpamReviewCase $case, User $reviewer): void
    {
        $case->update(['status' => 'archived', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);
    }
}
