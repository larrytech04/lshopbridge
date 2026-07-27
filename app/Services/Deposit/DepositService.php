<?php

namespace App\Services\Deposit;

use App\Enums\DepositStatus;
use App\Enums\PaymentIntentStatus;
use App\Exceptions\InsufficientFundsException;
use App\Models\Deposit;
use App\Models\PaymentIntent;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Notifications\AdminMessage;
use App\Notifications\DepositConfirmed;
use App\Notifications\DepositRejected;
use App\Services\Audit\AuditLogger;
use App\Services\Funding\FeeCalculator;
use App\Services\Payments\DTO\WebhookResult;
use App\Services\Payments\PaymentManager;
use App\Services\Risk\RiskEngine;
use App\Services\Wallet\WalletService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DepositService
{
    public function __construct(
        private WalletService $wallet,
        private FeeCalculator $fees,
        private RiskEngine $risk,
        private PaymentManager $payments,
        private AuditLogger $audit,
    ) {}

    /** @return array{amount: float, fee: float, net: float, fee_id: ?int, fee_snapshot: array} */
    public function quote(float $amount, ?string $methodCode = null, ?User $user = null): array
    {
        $breakdown = $this->fees->quote($amount, 'deposit', $methodCode, $user);

        return [
            'amount' => $amount,
            'fee' => (float) $breakdown['calculated_fee'],
            'net' => round($amount - (float) $breakdown['calculated_fee'], 2),
            'fee_id' => $breakdown['matched_fee_id'],
            'fee_snapshot' => $breakdown,
        ];
    }

    /**
     * Manual deposit: the user pays out-of-band and (optionally) uploads proof.
     * An admin confirms before the wallet is credited.
     */
    public function createManual(User $user, PaymentMethod $method, float $amount, ?UploadedFile $proof = null, array $payer = []): Deposit
    {
        $q = $this->quote($amount, $method->code, $user);

        $deposit = new Deposit([
            'reference' => reference('PB-DEP'),
            'amount' => $q['amount'],
            'fee' => $q['fee'],
            'net_amount' => $q['net'],
            'fee_id' => $q['fee_id'],
            'fee_snapshot' => $q['fee_snapshot'],
            'currency' => $method->currency ?? config('platform.base_currency', 'XAF'),
            'status' => DepositStatus::UnderReview,
            'is_automated' => false,
            'payer_details' => $payer ?: null,
        ]);
        $deposit->user()->associate($user);
        $deposit->paymentMethod()->associate($method);

        if ($proof) {
            $deposit->proof_path = $proof->store('deposits/proofs', 'private');
        }

        $deposit->save();

        $assessment = $this->risk->evaluate($user, $amount, 'deposit', $deposit);
        if ($assessment['flags']) {
            $deposit->update(['risk_flagged' => true]);
        }

        $this->audit->log('deposit.created', "Manual deposit {$deposit->reference}", $deposit, ['amount' => $amount]);
        $this->recordEvent($deposit, 'created', null, null, $deposit->status);

        return $deposit;
    }

    /**
     * Automated deposit: charge the user through the provider API. The wallet is
     * credited automatically when the provider's webhook confirms success.
     *
     * @return array{deposit: Deposit, intent: PaymentIntent, charge: \App\Services\Payments\DTO\ChargeResult}
     */
    public function createAutomated(User $user, PaymentMethod $method, float $amount, array $context = []): array
    {
        $q = $this->quote($amount, $method->code, $user);
        $currency = $method->currency ?? config('platform.base_currency', 'XAF');

        return DB::transaction(function () use ($user, $method, $amount, $q, $currency, $context) {
            $deposit = Deposit::create([
                'reference' => reference('PB-DEP'),
                'user_id' => $user->id,
                'payment_method_id' => $method->id,
                'provider_code' => $method->provider_code,
                'amount' => $q['amount'],
                'fee' => $q['fee'],
                'net_amount' => $q['net'],
                'fee_id' => $q['fee_id'],
                'fee_snapshot' => $q['fee_snapshot'],
                'currency' => $currency,
                'status' => DepositStatus::Processing,
                'is_automated' => true,
                'payer_details' => $context ?: null,
            ]);

            $intent = PaymentIntent::create([
                'reference' => reference('PB-INT'),
                'user_id' => $user->id,
                'provider_code' => $method->provider_code,
                'method_code' => $method->code,
                'purpose' => 'deposit',
                'amount' => $amount,
                'currency' => $currency,
                'status' => PaymentIntentStatus::Processing,
                'deposit_id' => $deposit->id,
                'attempts' => 1,
            ]);

            $charge = $this->payments->driver($method->provider_code)->charge($intent, $context);

            $intent->update([
                'provider_reference' => $charge->providerReference,
                'redirect_url' => $charge->redirectUrl,
                'status' => $charge->failed() ? PaymentIntentStatus::Failed : PaymentIntentStatus::Processing,
                'last_error' => $charge->failed() ? $charge->message : null,
                'payload' => $charge->raw ?: null,
            ]);

            $deposit->update(['provider_reference' => $charge->providerReference]);

            if ($charge->failed()) {
                $deposit->update(['status' => DepositStatus::Failed, 'rejection_reason' => $charge->message]);
            }

            $this->audit->log('deposit.charge_initiated', "Automated deposit {$deposit->reference}", $deposit, [
                'provider' => $method->provider_code, 'status' => $charge->status,
            ]);
            $this->recordEvent($deposit, 'created', null, null, $deposit->status);

            return ['deposit' => $deposit, 'intent' => $intent, 'charge' => $charge];
        });
    }

    /** Settle an automated deposit from a verified provider webhook. */
    public function settleFromWebhook(PaymentIntent $intent, WebhookResult $result): void
    {
        $deposit = $intent->deposit;
        if (! $deposit) {
            return;
        }

        if ($result->succeeded()) {
            $intent->update(['status' => PaymentIntentStatus::Succeeded, 'provider_reference' => $result->providerReference ?? $intent->provider_reference]);

            // A method flagged requires_manual_review never auto-credits, even
            // once the provider confirms payment — an admin must confirm it.
            if ($deposit->paymentMethod?->requires_manual_review) {
                $this->placeUnderReview($deposit, null, 'Provider confirmed payment; method requires manual review before crediting.');
            } else {
                $this->confirm($deposit, null, automated: true);
            }
        } else {
            $intent->update(['status' => PaymentIntentStatus::Failed, 'last_error' => 'Provider reported failure']);
            $this->markFailed($deposit, 'Payment failed at provider.');
        }
    }

    /**
     * Credit the wallet and mark the deposit confirmed. Idempotent AND safe under
     * concurrent calls (e.g. an admin click racing a webhook): the deposit row is
     * locked for the duration of the check-and-credit, so only the first caller to
     * acquire the lock can ever see status !== Confirmed and perform the credit.
     */
    public function confirm(Deposit $deposit, ?User $admin = null, bool $automated = false): Deposit
    {
        $alreadyConfirmed = false;
        $fromStatus = $deposit->status;

        $deposit = DB::transaction(function () use ($deposit, $admin, &$alreadyConfirmed) {
            $locked = Deposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === DepositStatus::Confirmed) {
                $alreadyConfirmed = true;

                return $locked;
            }

            $wallet = $locked->user->primaryWallet($locked->currency);

            $this->wallet->credit(
                $wallet,
                (float) $locked->net_amount,
                'deposit',
                $locked,
                "Deposit {$locked->reference} confirmed",
            );

            $locked->update([
                'status' => DepositStatus::Confirmed,
                'confirmed_by' => $admin?->id,
                'confirmed_at' => now(),
            ]);

            return $locked;
        });

        if ($alreadyConfirmed) {
            return $deposit;
        }

        $this->audit->log('deposit.confirmed', "Deposit {$deposit->reference} confirmed".($automated ? ' (auto)' : ''), $deposit);
        $this->recordEvent($deposit, 'confirmed', $admin, $fromStatus, DepositStatus::Confirmed);
        $deposit->user->notify(new DepositConfirmed($deposit));

        return $deposit;
    }

    public function reject(Deposit $deposit, string $reason, ?User $admin = null): Deposit
    {
        $fromStatus = $deposit->status;

        $deposit->update([
            'status' => DepositStatus::Rejected,
            'rejection_reason' => $reason,
            'confirmed_by' => $admin?->id,
            'confirmed_at' => now(),
        ]);

        $this->audit->log('deposit.rejected', "Deposit {$deposit->reference} rejected", $deposit, ['reason' => $reason]);
        $this->recordEvent($deposit, 'rejected', $admin, $fromStatus, DepositStatus::Rejected, $reason);
        $deposit->user->notify(new DepositRejected($deposit, $reason));

        return $deposit;
    }

    public function markFailed(Deposit $deposit, string $reason): Deposit
    {
        $fromStatus = $deposit->status;
        $deposit->update(['status' => DepositStatus::Failed, 'rejection_reason' => $reason]);
        $this->audit->log('deposit.failed', "Deposit {$deposit->reference} failed", $deposit, ['reason' => $reason]);
        $this->recordEvent($deposit, 'failed', null, $fromStatus, DepositStatus::Failed, $reason);

        return $deposit;
    }

    public function placeUnderReview(Deposit $deposit, ?User $admin = null, ?string $reason = null): Deposit
    {
        $fromStatus = $deposit->status;
        $deposit->update(['status' => DepositStatus::UnderReview]);
        $this->audit->log('deposit.under_review', "Deposit {$deposit->reference} placed under review", $deposit, ['reason' => $reason]);
        $this->recordEvent($deposit, 'placed_under_review', $admin, $fromStatus, DepositStatus::UnderReview, $reason);

        return $deposit;
    }

    public function requestInfo(Deposit $deposit, User $admin, string $message): Deposit
    {
        $this->audit->log('deposit.info_requested', "Requested more information for deposit {$deposit->reference}", $deposit, ['message' => $message]);
        $this->recordEvent($deposit, 'info_requested', $admin, $deposit->status, $deposit->status, $message);
        $deposit->user->notify(new AdminMessage('More information needed for your deposit', $message, true));

        return $deposit;
    }

    public function escalate(Deposit $deposit, User $admin, string $reason): Deposit
    {
        $this->audit->log('deposit.escalated', "Deposit {$deposit->reference} escalated", $deposit, ['reason' => $reason]);
        $this->recordEvent($deposit, 'escalated', $admin, $deposit->status, $deposit->status, $reason);

        return $deposit;
    }

    public function markForInvestigation(Deposit $deposit, User $admin, ?string $reason = null): Deposit
    {
        $deposit->update(['flagged_for_investigation' => true]);
        $this->audit->log('deposit.flagged_for_investigation', "Deposit {$deposit->reference} flagged for investigation", $deposit, ['reason' => $reason]);
        $this->recordEvent($deposit, 'flagged_for_investigation', $admin, $deposit->status, $deposit->status, $reason);

        return $deposit;
    }

    public function assign(Deposit $deposit, ?User $reviewer, User $admin): Deposit
    {
        $deposit->update(['assigned_to' => $reviewer?->id]);
        $this->audit->log('deposit.assigned', $reviewer ? "Deposit {$deposit->reference} assigned to {$reviewer->email}" : "Deposit {$deposit->reference} unassigned", $deposit);
        $this->recordEvent($deposit, 'assigned', $admin, $deposit->status, $deposit->status, $reviewer?->name);

        return $deposit;
    }

    public function addNote(Deposit $deposit, User $admin, string $note): Deposit
    {
        $deposit->update(['admin_notes' => $note]);
        $this->recordEvent($deposit, 'note_added', $admin, $deposit->status, $deposit->status, $note);

        return $deposit;
    }

    /**
     * Refund: money is returned to the customer through the original provider
     * (outside this platform), so the wallet credit that was given must be taken
     * back. Only ever possible on a Confirmed deposit, and never twice.
     *
     * @throws \RuntimeException if the wallet doesn't have enough available balance
     *         to safely debit (the customer has already spent the credited funds) —
     *         this platform has no debt/hold ledger yet, so that case is surfaced
     *         to the administrator instead of silently going negative.
     */
    public function refund(Deposit $deposit, User $admin, string $reason, ?string $providerRefundReference = null): Deposit
    {
        if (! $deposit->status->canBeRefundedOrReversed()) {
            throw new \RuntimeException('Only a confirmed deposit can be refunded.');
        }

        if ($deposit->paymentMethod && ! $deposit->paymentMethod->refund_support) {
            throw new \RuntimeException("The {$deposit->paymentMethod->name} payment method does not support refunds.");
        }

        $fromStatus = $deposit->status;

        DB::transaction(function () use ($deposit, $admin, $reason, $providerRefundReference) {
            $locked = Deposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canBeRefundedOrReversed()) {
                throw new \RuntimeException('Only a confirmed deposit can be refunded.');
            }

            $wallet = $locked->user->primaryWallet($locked->currency);

            if ($wallet->availableBalance() < (float) $locked->net_amount) {
                throw new InsufficientFundsException('Customer wallet balance is insufficient to safely refund this deposit. The funds have likely already been spent — use the debt/escalation workflow instead of refunding directly.');
            }

            $this->wallet->debit(
                $wallet,
                (float) $locked->net_amount,
                'refund',
                $locked,
                "Refund for deposit {$locked->reference}: {$reason}",
            );

            $locked->update([
                'status' => DepositStatus::Refunded,
                'refund_reference' => $providerRefundReference,
                'refund_reason' => $reason,
                'refunded_at' => now(),
                'refunded_by' => $admin->id,
            ]);
        });

        $deposit->refresh();
        $this->audit->log('deposit.refunded', "Deposit {$deposit->reference} refunded", $deposit, ['reason' => $reason, 'provider_reference' => $providerRefundReference]);
        $this->recordEvent($deposit, 'refund_completed', $admin, $fromStatus, DepositStatus::Refunded, $reason);
        $deposit->user->notify(new AdminMessage('Your deposit was refunded', "Your deposit {$deposit->reference} was refunded. Reason: {$reason}", true));

        return $deposit;
    }

    /**
     * Reversal: the deposit itself turned out to be invalid (provider chargeback,
     * processing error) so the wallet credit is undone. Same safety guard as
     * refund() — never permits a silent negative balance.
     */
    public function reverse(Deposit $deposit, User $admin, string $reason): Deposit
    {
        if (! $deposit->status->canBeRefundedOrReversed()) {
            throw new \RuntimeException('Only a confirmed deposit can be reversed.');
        }

        $fromStatus = $deposit->status;

        DB::transaction(function () use ($deposit, $admin, $reason) {
            $locked = Deposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canBeRefundedOrReversed()) {
                throw new \RuntimeException('Only a confirmed deposit can be reversed.');
            }

            $wallet = $locked->user->primaryWallet($locked->currency);

            if ($wallet->availableBalance() < (float) $locked->net_amount) {
                throw new InsufficientFundsException('Customer wallet balance is insufficient to safely reverse this deposit without going negative. Use the debt/escalation workflow instead.');
            }

            $this->wallet->debit(
                $wallet,
                (float) $locked->net_amount,
                'reversal',
                $locked,
                "Reversal of deposit {$locked->reference}: {$reason}",
            );

            $locked->update([
                'status' => DepositStatus::Reversed,
                'reversal_reason' => $reason,
                'reversed_at' => now(),
                'reversed_by' => $admin->id,
            ]);
        });

        $deposit->refresh();
        $this->audit->log('deposit.reversed', "Deposit {$deposit->reference} reversed", $deposit, ['reason' => $reason]);
        $this->recordEvent($deposit, 'reversed', $admin, $fromStatus, DepositStatus::Reversed, $reason);
        $deposit->user->notify(new AdminMessage('A deposit on your account was reversed', "Your deposit {$deposit->reference} was reversed. Reason: {$reason}", true));

        return $deposit;
    }

    /**
     * Re-surface what this platform already knows about the provider side of an
     * automated deposit (its payment intent + webhook history). No payment driver
     * in this codebase currently exposes a live transaction-status API, so this
     * is a "refresh known data" action, not a live provider round-trip.
     */
    public function requeryKnownState(Deposit $deposit, User $admin): array
    {
        $this->audit->log('deposit.requeried', "Requeried known provider state for deposit {$deposit->reference}", $deposit);
        $this->recordEvent($deposit, 'requeried', $admin, $deposit->status, $deposit->status);

        return [
            'intents' => $deposit->intents()->latest()->get(),
            'webhook_events' => $deposit->webhookEvents()->latest()->get(),
        ];
    }

    public function reconcile(Deposit $deposit, User $admin, string $status, ?string $note = null): Deposit
    {
        $deposit->update([
            'reconciliation_status' => $status,
            'reconciled_at' => now(),
            'reconciled_by' => $admin->id,
            'reconciliation_note' => $note,
        ]);
        $this->audit->log('deposit.reconciled', "Deposit {$deposit->reference} marked {$status}", $deposit, ['note' => $note]);
        $this->recordEvent($deposit, 'reconciled', $admin, $deposit->status, $deposit->status, $note);

        return $deposit;
    }

    /**
     * Computed proxy only — no external bank/settlement feed exists in this
     * platform to diff against. "Matched" means the provider's own webhook
     * confirmed the intent; "manually_reconciled" means an admin confirmed a
     * manual (bank/proof) deposit themselves.
     */
    public function computeReconciliationStatus(Deposit $deposit): string
    {
        if (! $deposit->status->isSettled()) {
            return 'provider_pending';
        }

        if (in_array($deposit->status, [DepositStatus::Rejected, DepositStatus::Failed, DepositStatus::Cancelled], true)) {
            return 'unmatched';
        }

        if ($deposit->status === DepositStatus::Confirmed && ! $deposit->is_automated) {
            return 'manually_reconciled';
        }

        if ($deposit->status === DepositStatus::Confirmed && $deposit->is_automated) {
            $succeededIntent = $deposit->intents()->where('status', PaymentIntentStatus::Succeeded->value)->exists();

            return $succeededIntent ? 'matched' : 'requires_investigation';
        }

        return 'requires_investigation';
    }

    /**
     * Live duplicate check — never stored, always computed fresh. Warns only;
     * never auto-rejects.
     *
     * @return array<int, array{deposit_id:int, reference:string, match:string}>
     */
    public function findDuplicates(Deposit $deposit): array
    {
        $matches = collect();

        if ($deposit->provider_reference) {
            $matches = $matches->concat(
                Deposit::where('id', '!=', $deposit->id)
                    ->where('provider_reference', $deposit->provider_reference)
                    ->get()
                    ->map(fn ($d) => ['deposit_id' => $d->id, 'reference' => $d->reference, 'match' => 'Same provider reference'])
            );
        }

        $payerAccount = $deposit->payer_details['account'] ?? $deposit->payer_details['phone'] ?? null;
        if ($payerAccount) {
            $matches = $matches->concat(
                Deposit::where('id', '!=', $deposit->id)
                    ->where('user_id', '!=', $deposit->user_id)
                    ->where(fn ($q) => $q->whereJsonContains('payer_details->account', $payerAccount)
                        ->orWhereJsonContains('payer_details->phone', $payerAccount))
                    ->get()
                    ->map(fn ($d) => ['deposit_id' => $d->id, 'reference' => $d->reference, 'match' => 'Same payment source used by another user'])
            );
        }

        $recentSameAmount = Deposit::where('id', '!=', $deposit->id)
            ->where('user_id', $deposit->user_id)
            ->where('amount', $deposit->amount)
            ->where('created_at', '>=', $deposit->created_at?->copy()->subMinutes(10))
            ->where('created_at', '<=', $deposit->created_at?->copy()->addMinutes(10))
            ->get()
            ->map(fn ($d) => ['deposit_id' => $d->id, 'reference' => $d->reference, 'match' => 'Same amount from the same user within 10 minutes']);

        $matches = $matches->concat($recentSameAmount);

        return $matches->unique('deposit_id')->values()->all();
    }

    private function recordEvent(Deposit $deposit, string $event, ?User $actor, ?DepositStatus $from = null, ?DepositStatus $to = null, ?string $reason = null): void
    {
        $deposit->events()->create([
            'actor_id' => $actor?->id,
            'event' => $event,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'reason' => $reason,
        ]);
    }
}
