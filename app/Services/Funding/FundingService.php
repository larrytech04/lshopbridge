<?php

namespace App\Services\Funding;

use App\Enums\FundingStatus;
use App\Enums\PaymentIntentStatus;
use App\Models\BeneficiaryAccount;
use App\Models\ChinaWalletType;
use App\Models\FundingRequest;
use App\Models\PaymentIntent;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Notifications\AdminMessage;
use App\Notifications\FundingCompleted;
use App\Notifications\FundingNeedsAttention;
use App\Services\Audit\AuditLogger;
use App\Services\Kyc\LimitService;
use App\Services\Payments\DTO\WebhookResult;
use App\Services\Payments\PaymentManager;
use App\Services\Risk\RiskEngine;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The China-wallet funding orchestrator and AUTOMATIC FUNDING ENGINE.
 *
 * Happy path (fully automated):
 *   quote -> charge/debit -> payment_successful -> auto-submit to funding
 *   provider -> funding_successful, all without admin involvement.
 *
 * Manual review is the safe fallback, triggered ONLY when:
 *   payment fails, webhook mismatch, account not verified, amount over limit,
 *   a risk rule trips, funding automation is disabled, or the funding API fails.
 *
 * Note on wallet safety: the wallet is debited immediately at request creation
 * (an optimistic debit), not reserved-then-settled — WalletService::hold()/
 * release() exist but are not used anywhere in this codebase. That create-time
 * behavior is preserved as-is here; every admin-triggered action added below
 * (refund/markFailed/cancel) is careful to give that debit back exactly once
 * whenever a request will never be delivered.
 */
class FundingService
{
    public function __construct(
        private WalletService $wallet,
        private FeeCalculator $fees,
        private RateService $rates,
        private LimitService $limits,
        private RiskEngine $risk,
        private FundingManager $funding,
        private PaymentManager $payments,
        private AuditLogger $audit,
    ) {}

    /** @return array<string, mixed> */
    public function quote(float $amount, ?string $appType = null, ?User $user = null): array
    {
        $base = config('platform.base_currency', 'XAF');
        $target = config('platform.target_currency', 'CNY');
        $rateQuote = $this->rates->quote($amount, $base, $target);
        $rate = $rateQuote['effective_rate'];
        $breakdown = $this->fees->quote($amount, 'funding', $appType, $user);
        $fee = (float) $breakdown['calculated_fee'];

        return [
            'source_amount' => round($amount, 2),
            'source_currency' => $base,
            'fee' => $fee,
            'fee_id' => $breakdown['matched_fee_id'],
            'fee_snapshot' => $breakdown,
            'total_charged' => round($amount + $fee, 2),
            'exchange_rate' => $rate,
            'base_rate' => $rateQuote['base_rate'],
            'margin_amount' => $rateQuote['margin_amount'],
            'rate_updated_at' => $rateQuote['rate_updated_at'],
            'rate_available' => $rateQuote['rate_available'],
            'target_amount' => round($amount * $rate, 2),
            'target_currency' => $target,
        ];
    }

    /**
     * Create a funding request paid instantly from the wallet balance.
     */
    public function createFromWallet(User $user, BeneficiaryAccount $beneficiary, float $amount): FundingRequest
    {
        $q = $this->quote($amount, $beneficiary->app_type->value, $user);
        $this->assertWithinLimits($user, $q['total_charged'], $beneficiary);

        $funding = DB::transaction(function () use ($user, $beneficiary, $q) {
            $funding = $this->newRequest($user, $beneficiary, $q, 'wallet', FundingStatus::PaymentPending);

            $assessment = $this->risk->evaluate($user, $q['total_charged'], 'funding', $funding, [
                'recipient_name' => $beneficiary->account_name,
            ]);

            // Wallet debit = instant payment.
            $wallet = $user->primaryWallet($q['source_currency']);
            $this->wallet->debit($wallet, (float) $q['total_charged'], 'funding', $funding, "Funding {$funding->reference}");

            $funding->update([
                'status' => FundingStatus::PaymentSuccessful,
                'risk_flagged' => $assessment['requires_review'],
                'manual_review_reason' => $assessment['requires_review'] ? implode(' ', $assessment['reasons']) : null,
            ]);

            return $funding->fresh();
        });

        $this->audit->log('funding.created', "Wallet funding {$funding->reference}", $funding, ['amount' => $amount]);
        $this->recordEvent($funding, 'created', null, null, FundingStatus::PaymentSuccessful);

        $this->maybeAutoFund($funding);

        return $funding->fresh();
    }

    /**
     * Create a funding request paid by a fresh direct payment (charge -> webhook).
     *
     * @return array{funding: FundingRequest, intent: PaymentIntent, charge: \App\Services\Payments\DTO\ChargeResult}
     */
    public function createWithDirectPayment(User $user, BeneficiaryAccount $beneficiary, float $amount, PaymentMethod $method, array $context = []): array
    {
        $q = $this->quote($amount, $beneficiary->app_type->value, $user);
        $this->assertWithinLimits($user, $q['total_charged'], $beneficiary);

        return DB::transaction(function () use ($user, $beneficiary, $amount, $method, $q, $context) {
            $funding = $this->newRequest($user, $beneficiary, $q, 'direct_payment', FundingStatus::PaymentPending);

            $assessment = $this->risk->evaluate($user, $q['total_charged'], 'funding', $funding, [
                'recipient_name' => $beneficiary->account_name,
            ]);
            $funding->update([
                'risk_flagged' => $assessment['requires_review'],
                'manual_review_reason' => $assessment['requires_review'] ? implode(' ', $assessment['reasons']) : null,
                'provider_code' => $method->provider_code,
            ]);
            $this->recordEvent($funding, 'created', null, null, FundingStatus::PaymentPending);

            $intent = PaymentIntent::create([
                'reference' => reference('PB-INT'),
                'user_id' => $user->id,
                'provider_code' => $method->provider_code,
                'method_code' => $method->code,
                'purpose' => 'funding',
                'amount' => $q['total_charged'],
                'currency' => $q['source_currency'],
                'status' => PaymentIntentStatus::Processing,
                'funding_request_id' => $funding->id,
                'attempts' => 1,
            ]);

            $charge = $this->payments->driver($method->provider_code)->charge($intent, $context);

            $intent->update([
                'provider_reference' => $charge->providerReference,
                'redirect_url' => $charge->redirectUrl,
                'status' => $charge->failed() ? PaymentIntentStatus::Failed : PaymentIntentStatus::Processing,
                'last_error' => $charge->failed() ? $charge->message : null,
            ]);

            if ($charge->failed()) {
                $this->setManualReview($funding, 'Could not start the payment: '.$charge->message);
            }

            return ['funding' => $funding->fresh(), 'intent' => $intent, 'charge' => $charge];
        });
    }

    /** Called by the webhook pipeline once a direct payment is confirmed. */
    public function settlePaymentFromWebhook(PaymentIntent $intent, WebhookResult $result): void
    {
        $funding = $intent->fundingRequest;
        if (! $funding) {
            return;
        }

        if ($result->succeeded()) {
            $intent->update(['status' => PaymentIntentStatus::Succeeded]);
            $this->markPaymentSuccessful($funding);
        } else {
            $intent->update(['status' => PaymentIntentStatus::Failed]);
            // Payment failure is an explicit manual-review trigger.
            $this->setManualReview($funding, 'Payment failed at provider.');
        }
    }

    public function markPaymentSuccessful(FundingRequest $funding): void
    {
        if ($funding->status !== FundingStatus::PaymentPending) {
            return; // idempotent
        }

        $funding->update(['status' => FundingStatus::PaymentSuccessful]);
        $this->recordEvent($funding, 'payment_successful', null, FundingStatus::PaymentPending, FundingStatus::PaymentSuccessful);
        $this->maybeAutoFund($funding->fresh());
    }

    /**
     * Decide whether to auto-fund or fall back to manual review.
     */
    public function maybeAutoFund(FundingRequest $funding): void
    {
        if ($funding->status !== FundingStatus::PaymentSuccessful) {
            return;
        }

        if ($funding->risk_flagged) {
            $this->setManualReview($funding, $funding->manual_review_reason ?? 'Flagged by risk rules.');

            return;
        }

        if (! config('platform.automation.funding', true) || ! setting('funding_automation_enabled', true)) {
            $this->setManualReview($funding, 'Funding automation is disabled, queued for manual processing.');

            return;
        }

        $this->triggerFunding($funding);
    }

    /** Submit the payout to the funding provider (the auto-funding engine core). */
    public function triggerFunding(FundingRequest $funding): void
    {
        $funding->update(['status' => FundingStatus::FundingProcessing, 'provider_code' => config('funding.default', 'alipay')]);
        $this->recordEvent($funding, 'submitted', null, FundingStatus::PaymentSuccessful, FundingStatus::FundingProcessing);

        try {
            $result = $this->funding->provider()->submit($funding);
        } catch (\Throwable $e) {
            report($e);
            $this->setManualReview($funding, 'Funding provider error: '.$e->getMessage());

            return;
        }

        match (true) {
            $result->succeeded() => $this->markFundingSuccessful($funding, $result->providerReference, $result->receipt),
            $result->needsManual() => $this->setManualReview($funding, $result->message ?? 'Funding requires manual handling.'),
            $result->failed() => $this->setManualReview($funding, 'Funding API failed: '.($result->message ?? 'unknown error')),
            default => $funding->update(['provider_reference' => $result->providerReference]), // processing: await webhook
        };
    }

    public function markFundingSuccessful(FundingRequest $funding, ?string $providerRef = null, ?string $receipt = null): FundingRequest
    {
        if ($funding->status === FundingStatus::FundingSuccessful) {
            return $funding;
        }

        $fromStatus = $funding->status;

        $funding->update([
            'status' => FundingStatus::FundingSuccessful,
            'provider_reference' => $providerRef ?? $funding->provider_reference,
            'processed_at' => now(),
            'notes' => $receipt ?? $funding->notes,
        ]);

        $this->audit->log('funding.completed', "Funding {$funding->reference} delivered", $funding);
        $this->recordEvent($funding, 'completed', null, $fromStatus, FundingStatus::FundingSuccessful);
        $funding->user->notify(new FundingCompleted($funding));

        return $funding;
    }

    /** Admin or system routes the request to the manual queue. */
    public function setManualReview(FundingRequest $funding, string $reason): FundingRequest
    {
        $fromStatus = $funding->status;

        $funding->update([
            'status' => FundingStatus::ManualReview,
            'risk_flagged' => true,
            'manual_review_reason' => $reason,
        ]);

        $this->audit->log('funding.manual_review', "Funding {$funding->reference} → manual review", $funding, ['reason' => $reason]);
        $this->recordEvent($funding, 'manual_review', null, $fromStatus, FundingStatus::ManualReview, $reason);

        return $funding;
    }

    /**
     * Admin completes a funding by hand (e.g. paid the recipient manually).
     * Idempotent and row-locked — an already-completed or refunded request can
     * never be "completed" again on top of itself.
     */
    public function completeManually(FundingRequest $funding, User $admin, ?string $receiptPath = null, ?string $note = null): FundingRequest
    {
        $result = DB::transaction(function () use ($funding, $admin, $receiptPath, $note) {
            $locked = FundingRequest::whereKey($funding->id)->lockForUpdate()->firstOrFail();

            if ($locked->status->isTerminal()) {
                return null;
            }

            $locked->update([
                'status' => FundingStatus::FundingSuccessful,
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'receipt_path' => $receiptPath ?? $locked->receipt_path,
                'notes' => $note ?? $locked->notes,
            ]);

            return $locked;
        });

        if (! $result) {
            return $funding->fresh();
        }

        $funding = $result->fresh();
        $this->audit->log('funding.completed_manual', "Funding {$funding->reference} completed by admin", $funding);
        $this->recordEvent($funding, 'completed', $admin, null, FundingStatus::FundingSuccessful, $note);
        $funding->user->notify(new FundingCompleted($funding));

        return $funding;
    }

    /**
     * Refund the charged amount back to the user's wallet. Row-locked and
     * guarded against refunding a request that was already delivered
     * (FundingSuccessful) or already refunded/cancelled — a delivered request
     * refunded on top would let the customer keep both the CNY and their XAF.
     */
    public function refund(FundingRequest $funding, ?User $admin = null, string $reason = 'Refunded'): FundingRequest
    {
        if (! $funding->status->canBeRefunded()) {
            throw new \RuntimeException('This funding request cannot be refunded from its current status ('.$funding->status->label().'). Delivered, already-refunded, or cancelled requests are never refunded again.');
        }

        $fromStatus = $funding->status;

        DB::transaction(function () use ($funding, $admin, $reason) {
            $locked = FundingRequest::whereKey($funding->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canBeRefunded()) {
                throw new \RuntimeException('This funding request cannot be refunded from its current status ('.$locked->status->label().').');
            }

            $wallet = $locked->user->primaryWallet($locked->source_currency);
            $this->wallet->credit($wallet, (float) $locked->total_charged, 'refund', $locked, "Refund for {$locked->reference}: {$reason}");
            $locked->update(['status' => FundingStatus::Refunded, 'processed_by' => $admin?->id, 'processed_at' => now(), 'notes' => $reason]);
        });

        $funding = $funding->fresh();
        $this->audit->log('funding.refunded', "Funding {$funding->reference} refunded", $funding, ['reason' => $reason]);
        $this->recordEvent($funding, 'refund_completed', $admin, $fromStatus, FundingStatus::Refunded, $reason);
        $funding->user->notify(new FundingNeedsAttention($funding, 'Your funding request was refunded to your wallet.'));

        return $funding;
    }

    public function retry(FundingRequest $funding): void
    {
        if (in_array($funding->status, [FundingStatus::ManualReview, FundingStatus::FundingFailed], true)) {
            $funding->update(['status' => FundingStatus::PaymentSuccessful, 'risk_flagged' => false, 'manual_review_reason' => null]);
            $this->recordEvent($funding, 'submitted', null, $funding->status, FundingStatus::PaymentSuccessful, 'Retried');
            $this->triggerFunding($funding->fresh());
        }
    }

    /**
     * Admin marks a request permanently failed and gives the customer's money
     * back — this platform's automatic engine never reaches FundingFailed on
     * its own (provider failures route to ManualReview for human judgment
     * instead), so this is an explicit, admin-only, audited decision.
     */
    public function markFailed(FundingRequest $funding, User $admin, string $reason): FundingRequest
    {
        if ($funding->status->isTerminal()) {
            throw new \RuntimeException('A completed, refunded, or cancelled request cannot be marked failed.');
        }

        $fromStatus = $funding->status;

        DB::transaction(function () use ($funding, $admin, $reason) {
            $locked = FundingRequest::whereKey($funding->id)->lockForUpdate()->firstOrFail();

            if ($locked->status->isTerminal()) {
                throw new \RuntimeException('A completed, refunded, or cancelled request cannot be marked failed.');
            }

            $wallet = $locked->user->primaryWallet($locked->source_currency);
            $this->wallet->credit($wallet, (float) $locked->total_charged, 'refund', $locked, "Funding {$locked->reference} failed, refunded: {$reason}");
            $locked->update(['status' => FundingStatus::FundingFailed, 'processed_by' => $admin->id, 'processed_at' => now(), 'notes' => $reason]);
        });

        $funding = $funding->fresh();
        $this->audit->log('funding.failed', "Funding {$funding->reference} marked failed", $funding, ['reason' => $reason]);
        $this->recordEvent($funding, 'failed', $admin, $fromStatus, FundingStatus::FundingFailed, $reason);
        $funding->user->notify(new AdminMessage('Your funding request failed', "Your funding request {$funding->reference} could not be completed and has been refunded to your wallet. Reason: {$reason}", true));

        return $funding;
    }

    /** Admin cancels an in-flight request (e.g. wrong recipient) and refunds it. */
    public function cancel(FundingRequest $funding, User $admin, string $reason): FundingRequest
    {
        if (! $funding->status->isOpen()) {
            throw new \RuntimeException('Only a pending, processing, or under-review request can be cancelled.');
        }

        $fromStatus = $funding->status;

        DB::transaction(function () use ($funding, $admin, $reason) {
            $locked = FundingRequest::whereKey($funding->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isOpen()) {
                throw new \RuntimeException('Only a pending, processing, or under-review request can be cancelled.');
            }

            $wallet = $locked->user->primaryWallet($locked->source_currency);
            $this->wallet->credit($wallet, (float) $locked->total_charged, 'refund', $locked, "Funding {$locked->reference} cancelled: {$reason}");
            $locked->update([
                'status' => FundingStatus::Cancelled,
                'cancelled_by' => $admin->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
        });

        $funding = $funding->fresh();
        $this->audit->log('funding.cancelled', "Funding {$funding->reference} cancelled", $funding, ['reason' => $reason]);
        $this->recordEvent($funding, 'cancelled', $admin, $fromStatus, FundingStatus::Cancelled, $reason);
        $funding->user->notify(new AdminMessage('Your funding request was cancelled', "Your funding request {$funding->reference} was cancelled and refunded to your wallet. Reason: {$reason}", true));

        return $funding;
    }

    public function placeUnderReview(FundingRequest $funding, User $admin, ?string $reason = null): FundingRequest
    {
        $fromStatus = $funding->status;
        $this->setManualReview($funding, $reason ?? 'Placed under review by an administrator.');
        $this->recordEvent($funding, 'manual_review', $admin, $fromStatus, FundingStatus::ManualReview, $reason);

        return $funding->fresh();
    }

    public function requestInfo(FundingRequest $funding, User $admin, string $message): FundingRequest
    {
        $this->audit->log('funding.info_requested', "Requested more information for funding {$funding->reference}", $funding, ['message' => $message]);
        $this->recordEvent($funding, 'note_added', $admin, $funding->status, $funding->status, $message);
        $funding->user->notify(new AdminMessage('More information needed for your funding request', $message, true));

        return $funding;
    }

    public function escalate(FundingRequest $funding, User $admin, string $reason): FundingRequest
    {
        $this->audit->log('funding.escalated', "Funding {$funding->reference} escalated", $funding, ['reason' => $reason]);
        $this->recordEvent($funding, 'escalated', $admin, $funding->status, $funding->status, $reason);

        return $funding;
    }

    public function markForInvestigation(FundingRequest $funding, User $admin, ?string $reason = null): FundingRequest
    {
        $funding->update(['flagged_for_investigation' => true]);
        $this->audit->log('funding.flagged_for_investigation', "Funding {$funding->reference} flagged for investigation", $funding, ['reason' => $reason]);
        $this->recordEvent($funding, 'flagged_for_investigation', $admin, $funding->status, $funding->status, $reason);

        return $funding;
    }

    public function assign(FundingRequest $funding, ?User $reviewer, User $admin): FundingRequest
    {
        $funding->update(['assigned_to' => $reviewer?->id]);
        $this->audit->log('funding.assigned', $reviewer ? "Funding {$funding->reference} assigned to {$reviewer->email}" : "Funding {$funding->reference} unassigned", $funding);
        $this->recordEvent($funding, 'assigned', $admin, $funding->status, $funding->status, $reviewer?->name);

        return $funding;
    }

    public function addNote(FundingRequest $funding, User $admin, string $note): FundingRequest
    {
        $funding->update(['admin_notes' => $note]);
        $this->recordEvent($funding, 'note_added', $admin, $funding->status, $funding->status, $note);

        return $funding;
    }

    /**
     * Re-surface what this platform already knows about the provider side
     * (payment intents + webhook history). No funding driver in this codebase
     * exposes a live payout-status query API, so this refreshes recorded
     * state rather than making a live provider round-trip.
     */
    public function requeryKnownState(FundingRequest $funding, User $admin): array
    {
        $this->audit->log('funding.requeried', "Requeried known provider state for funding {$funding->reference}", $funding);
        $this->recordEvent($funding, 'requeried', $admin, $funding->status, $funding->status);

        return [
            'intents' => $funding->intents()->latest()->get(),
            'webhook_events' => $funding->webhookEvents()->latest()->get(),
        ];
    }

    public function reconcile(FundingRequest $funding, User $admin, string $status, ?string $note = null): FundingRequest
    {
        $funding->update([
            'reconciliation_status' => $status,
            'reconciled_at' => now(),
            'reconciled_by' => $admin->id,
            'reconciliation_note' => $note,
        ]);
        $this->audit->log('funding.reconciled', "Funding {$funding->reference} marked {$status}", $funding, ['note' => $note]);
        $this->recordEvent($funding, 'reconciled', $admin, $funding->status, $funding->status, $note);

        return $funding;
    }

    /**
     * Computed proxy only — no external settlement feed exists in this
     * platform to diff against.
     */
    public function computeReconciliationStatus(FundingRequest $funding): string
    {
        if ($funding->status->isOpen()) {
            return 'provider_pending';
        }

        if (in_array($funding->status, [FundingStatus::FundingFailed, FundingStatus::Cancelled], true)) {
            return 'unmatched';
        }

        if ($funding->status === FundingStatus::FundingSuccessful) {
            return $funding->processed_by ? 'manually_reconciled' : 'matched';
        }

        return 'requires_investigation';
    }

    /**
     * Live duplicate check — never stored, always computed fresh. Warns only.
     *
     * @return array<int, array{funding_request_id:int, reference:string, match:string}>
     */
    public function findDuplicates(FundingRequest $funding): array
    {
        $matches = collect();

        if ($funding->provider_reference) {
            $matches = $matches->concat(
                FundingRequest::where('id', '!=', $funding->id)
                    ->where('provider_reference', $funding->provider_reference)
                    ->get()
                    ->map(fn ($f) => ['funding_request_id' => $f->id, 'reference' => $f->reference, 'match' => 'Same provider reference'])
            );
        }

        $recentSameRecipientAmount = FundingRequest::where('id', '!=', $funding->id)
            ->where('user_id', $funding->user_id)
            ->where('beneficiary_account_id', $funding->beneficiary_account_id)
            ->where('source_amount', $funding->source_amount)
            ->where('created_at', '>=', $funding->created_at?->copy()->subMinutes(10))
            ->where('created_at', '<=', $funding->created_at?->copy()->addMinutes(10))
            ->get()
            ->map(fn ($f) => ['funding_request_id' => $f->id, 'reference' => $f->reference, 'match' => 'Same recipient and amount from the same user within 10 minutes']);

        $matches = $matches->concat($recentSameRecipientAmount);

        return $matches->unique('funding_request_id')->values()->all();
    }

    public function tabCounts(): array
    {
        $counts = FundingRequest::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $out = ['all' => FundingRequest::count()];
        foreach (FundingStatus::cases() as $case) {
            $out[$case->value] = (int) ($counts[$case->value] ?? 0);
        }

        return $out;
    }

    /* -------------------------------------------------- helpers */

    private function newRequest(User $user, BeneficiaryAccount $beneficiary, array $q, string $source, FundingStatus $status): FundingRequest
    {
        return FundingRequest::create([
            'reference' => reference('PB-FND'),
            'user_id' => $user->id,
            'beneficiary_account_id' => $beneficiary->id,
            'app_type' => $beneficiary->app_type->value,
            'recipient_name' => $beneficiary->account_name,
            'recipient_account' => $beneficiary->account_id,
            'source_amount' => $q['source_amount'],
            'source_currency' => $q['source_currency'],
            'exchange_rate' => $q['exchange_rate'],
            'target_amount' => $q['target_amount'],
            'target_currency' => $q['target_currency'],
            'fee' => $q['fee'],
            'fee_id' => $q['fee_id'],
            'fee_snapshot' => $q['fee_snapshot'],
            'total_charged' => $q['total_charged'],
            'funding_source' => $source,
            'status' => $status,
        ]);
    }

    private function assertWithinLimits(User $user, float $total, ?BeneficiaryAccount $beneficiary = null): void
    {
        $check = $this->limits->check($user, $total);

        if (! $check['ok']) {
            throw ValidationException::withMessages(['amount' => $check['reason']]);
        }

        if ($beneficiary) {
            $this->assertWithinWalletTypeLimits($user, $total, $beneficiary);
        }
    }

    /**
     * Additive check against the matched China wallet type's own configured
     * limits (see the Platform Configuration > China Wallet Types page). Only
     * enforced when an active, matching row exists — a wallet type with no
     * configured row imposes no extra restriction beyond LimitService's.
     */
    private function assertWithinWalletTypeLimits(User $user, float $total, BeneficiaryAccount $beneficiary): void
    {
        $walletType = ChinaWalletType::active()->where('code', $beneficiary->app_type->value)->first();

        if (! $walletType) {
            return;
        }

        if (! $walletType->allowsCountry($user->country?->iso2)) {
            throw ValidationException::withMessages(['amount' => "{$walletType->name} is not available from your country."]);
        }

        if ($walletType->min_kyc_level !== null && (int) $user->kyc_level < $walletType->min_kyc_level) {
            throw ValidationException::withMessages(['amount' => "{$walletType->name} requires a higher verification level."]);
        }

        if ($walletType->min_funding_amount !== null && $total < (float) $walletType->min_funding_amount) {
            throw ValidationException::withMessages(['amount' => "The minimum amount for {$walletType->name} is ".money((float) $walletType->min_funding_amount, config('platform.base_currency', 'XAF')).'.']);
        }

        if ($walletType->max_funding_amount !== null && $total > (float) $walletType->max_funding_amount) {
            throw ValidationException::withMessages(['amount' => "The maximum amount for {$walletType->name} is ".money((float) $walletType->max_funding_amount, config('platform.base_currency', 'XAF')).'.']);
        }

        if ($walletType->daily_limit !== null) {
            $usedToday = (float) FundingRequest::where('user_id', $user->id)
                ->where('app_type', $beneficiary->app_type->value)
                ->whereDate('created_at', today())
                ->whereNotIn('status', [FundingStatus::Refunded, FundingStatus::Cancelled, FundingStatus::FundingFailed])
                ->sum('total_charged');

            if ($usedToday + $total > (float) $walletType->daily_limit) {
                throw ValidationException::withMessages(['amount' => "This would exceed the daily limit for {$walletType->name}."]);
            }
        }

        if ($walletType->monthly_limit !== null) {
            $usedThisMonth = (float) FundingRequest::where('user_id', $user->id)
                ->where('app_type', $beneficiary->app_type->value)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->whereNotIn('status', [FundingStatus::Refunded, FundingStatus::Cancelled, FundingStatus::FundingFailed])
                ->sum('total_charged');

            if ($usedThisMonth + $total > (float) $walletType->monthly_limit) {
                throw ValidationException::withMessages(['amount' => "This would exceed the monthly limit for {$walletType->name}."]);
            }
        }
    }

    private function recordEvent(FundingRequest $funding, string $event, ?User $actor, ?FundingStatus $from = null, ?FundingStatus $to = null, ?string $reason = null): void
    {
        $funding->events()->create([
            'actor_id' => $actor?->id,
            'event' => $event,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'reason' => $reason,
        ]);
    }
}
