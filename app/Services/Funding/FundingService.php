<?php

namespace App\Services\Funding;

use App\Enums\FundingStatus;
use App\Enums\PaymentIntentStatus;
use App\Models\BeneficiaryAccount;
use App\Models\FundingRequest;
use App\Models\PaymentIntent;
use App\Models\PaymentMethod;
use App\Models\User;
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
    public function quote(float $amount, ?string $appType = null): array
    {
        $base = config('platform.base_currency', 'XAF');
        $target = config('platform.target_currency', 'CNY');
        $rate = $this->rates->rate($base, $target);
        $fee = $this->fees->feeFor($amount, 'funding', $appType);

        return [
            'source_amount' => round($amount, 2),
            'source_currency' => $base,
            'fee' => $fee,
            'total_charged' => round($amount + $fee, 2),
            'exchange_rate' => $rate,
            'target_amount' => round($amount * $rate, 2),
            'target_currency' => $target,
        ];
    }

    /**
     * Create a funding request paid instantly from the wallet balance.
     */
    public function createFromWallet(User $user, BeneficiaryAccount $beneficiary, float $amount): FundingRequest
    {
        $q = $this->quote($amount, $beneficiary->app_type->value);
        $this->assertWithinLimits($user, $q['total_charged']);

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
        $q = $this->quote($amount, $beneficiary->app_type->value);
        $this->assertWithinLimits($user, $q['total_charged']);

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

        $funding->update([
            'status' => FundingStatus::FundingSuccessful,
            'provider_reference' => $providerRef ?? $funding->provider_reference,
            'processed_at' => now(),
            'notes' => $receipt ?? $funding->notes,
        ]);

        $this->audit->log('funding.completed', "Funding {$funding->reference} delivered", $funding);
        $funding->user->notify(new FundingCompleted($funding));

        return $funding;
    }

    /** Admin or system routes the request to the manual queue. */
    public function setManualReview(FundingRequest $funding, string $reason): FundingRequest
    {
        $funding->update([
            'status' => FundingStatus::ManualReview,
            'risk_flagged' => true,
            'manual_review_reason' => $reason,
        ]);

        $this->audit->log('funding.manual_review', "Funding {$funding->reference} → manual review", $funding, ['reason' => $reason]);

        return $funding;
    }

    /** Admin completes a funding by hand (e.g. paid the recipient manually). */
    public function completeManually(FundingRequest $funding, User $admin, ?string $receiptPath = null, ?string $note = null): FundingRequest
    {
        $funding->update([
            'status' => FundingStatus::FundingSuccessful,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'receipt_path' => $receiptPath ?? $funding->receipt_path,
            'notes' => $note ?? $funding->notes,
        ]);

        $this->audit->log('funding.completed_manual', "Funding {$funding->reference} completed by admin", $funding);
        $funding->user->notify(new FundingCompleted($funding));

        return $funding;
    }

    /** Refund the charged amount back to the user's wallet. */
    public function refund(FundingRequest $funding, ?User $admin = null, string $reason = 'Refunded'): FundingRequest
    {
        if ($funding->status === FundingStatus::Refunded) {
            return $funding;
        }

        DB::transaction(function () use ($funding, $reason) {
            $wallet = $funding->user->primaryWallet($funding->source_currency);
            $this->wallet->credit($wallet, (float) $funding->total_charged, 'refund', $funding, "Refund for {$funding->reference}: {$reason}");
            $funding->update(['status' => FundingStatus::Refunded, 'processed_at' => now(), 'notes' => $reason]);
        });

        $this->audit->log('funding.refunded', "Funding {$funding->reference} refunded", $funding, ['reason' => $reason]);
        $funding->user->notify(new FundingNeedsAttention($funding, 'Your funding request was refunded to your wallet.'));

        return $funding->fresh();
    }

    public function retry(FundingRequest $funding): void
    {
        if (in_array($funding->status, [FundingStatus::ManualReview, FundingStatus::FundingFailed], true)) {
            $funding->update(['status' => FundingStatus::PaymentSuccessful, 'risk_flagged' => false, 'manual_review_reason' => null]);
            $this->triggerFunding($funding->fresh());
        }
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
            'total_charged' => $q['total_charged'],
            'funding_source' => $source,
            'status' => $status,
        ]);
    }

    private function assertWithinLimits(User $user, float $total): void
    {
        $check = $this->limits->check($user, $total);

        if (! $check['ok']) {
            throw ValidationException::withMessages(['amount' => $check['reason']]);
        }
    }
}
