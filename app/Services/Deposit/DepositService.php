<?php

namespace App\Services\Deposit;

use App\Enums\DepositStatus;
use App\Enums\PaymentIntentStatus;
use App\Models\Deposit;
use App\Models\PaymentIntent;
use App\Models\PaymentMethod;
use App\Models\User;
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

    /** @return array{amount: float, fee: float, net: float} */
    public function quote(float $amount, ?string $methodCode = null): array
    {
        $fee = $this->fees->feeFor($amount, 'deposit', $methodCode);

        return ['amount' => $amount, 'fee' => $fee, 'net' => round($amount - $fee, 2)];
    }

    /**
     * Manual deposit: the user pays out-of-band and (optionally) uploads proof.
     * An admin confirms before the wallet is credited.
     */
    public function createManual(User $user, PaymentMethod $method, float $amount, ?UploadedFile $proof = null, array $payer = []): Deposit
    {
        $q = $this->quote($amount, $method->code);

        $deposit = new Deposit([
            'reference' => reference('PB-DEP'),
            'amount' => $q['amount'],
            'fee' => $q['fee'],
            'net_amount' => $q['net'],
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
        $q = $this->quote($amount, $method->code);
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
            $this->confirm($deposit, null, automated: true);
        } else {
            $intent->update(['status' => PaymentIntentStatus::Failed, 'last_error' => 'Provider reported failure']);
            $this->markFailed($deposit, 'Payment failed at provider.');
        }
    }

    /** Credit the wallet and mark the deposit confirmed (idempotent). */
    public function confirm(Deposit $deposit, ?User $admin = null, bool $automated = false): Deposit
    {
        if ($deposit->status === DepositStatus::Confirmed) {
            return $deposit;
        }

        DB::transaction(function () use ($deposit, $admin, $automated) {
            $wallet = $deposit->user->primaryWallet($deposit->currency);

            $this->wallet->credit(
                $wallet,
                (float) $deposit->net_amount,
                'deposit',
                $deposit,
                "Deposit {$deposit->reference} confirmed",
            );

            $deposit->update([
                'status' => DepositStatus::Confirmed,
                'confirmed_by' => $admin?->id,
                'confirmed_at' => now(),
            ]);
        });

        $this->audit->log('deposit.confirmed', "Deposit {$deposit->reference} confirmed".($automated ? ' (auto)' : ''), $deposit);
        $deposit->user->notify(new DepositConfirmed($deposit));

        return $deposit;
    }

    public function reject(Deposit $deposit, string $reason, ?User $admin = null): Deposit
    {
        $deposit->update([
            'status' => DepositStatus::Rejected,
            'rejection_reason' => $reason,
            'confirmed_by' => $admin?->id,
            'confirmed_at' => now(),
        ]);

        $this->audit->log('deposit.rejected', "Deposit {$deposit->reference} rejected", $deposit, ['reason' => $reason]);
        $deposit->user->notify(new DepositRejected($deposit, $reason));

        return $deposit;
    }

    public function markFailed(Deposit $deposit, string $reason): Deposit
    {
        $deposit->update(['status' => DepositStatus::Failed, 'rejection_reason' => $reason]);
        $this->audit->log('deposit.failed', "Deposit {$deposit->reference} failed", $deposit, ['reason' => $reason]);

        return $deposit;
    }
}
