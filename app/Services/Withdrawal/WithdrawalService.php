<?php

namespace App\Services\Withdrawal;

use App\Enums\WithdrawalStatus;
use App\Models\KycLevel;
use App\Models\SavedPaymentMethod;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\Audit\AuditLogger;
use App\Services\Funding\FeeCalculator;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Withdraw Funds: a real payout workflow, not a same-request debit. Money is
 * only *held* (WalletService::hold) when the customer submits — it leaves the
 * wallet for good only once an admin actually approves the payout, via
 * approve()/reject() below. This mirrors how Deposits/Funding already work
 * (manual review before money moves), and reuses the dormant hold()/release()
 * pair on WalletService that nothing else in the app was using yet.
 */
class WithdrawalService
{
    public function __construct(
        private WalletService $wallet,
        private FeeCalculator $fees,
        private AuditLogger $audit,
    ) {}

    public function quote(User $user, float $amount): array
    {
        $breakdown = $this->fees->quote($amount, 'withdrawal', null, $user);
        $fee = (float) $breakdown['calculated_fee'];

        return [
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => round($amount - $fee, 2),
            'fee_breakdown' => $breakdown,
        ];
    }

    /**
     * @return array{ok: bool, reason: ?string}
     */
    public function checkLimits(User $user, float $amount): array
    {
        $level = KycLevel::where('level', $user->kyc_level)->first();

        if (! $level || ! $level->is_active) {
            return ['ok' => false, 'reason' => 'Your verification level does not allow withdrawals yet.'];
        }

        if ($level->per_transaction_limit > 0 && $amount > (float) $level->per_transaction_limit) {
            return ['ok' => false, 'reason' => 'Amount exceeds your per-transaction limit of '.money($level->per_transaction_limit, $level->currency).'.'];
        }

        $dailyUsed = $this->withdrawnSince($user, Carbon::today());
        if ($level->daily_limit > 0 && ($dailyUsed + $amount) > (float) $level->daily_limit) {
            return ['ok' => false, 'reason' => 'This would exceed your daily withdrawal limit of '.money($level->daily_limit, $level->currency).'.'];
        }

        $monthlyUsed = $this->withdrawnSince($user, Carbon::now()->startOfMonth());
        if ($level->monthly_limit > 0 && ($monthlyUsed + $amount) > (float) $level->monthly_limit) {
            return ['ok' => false, 'reason' => 'This would exceed your monthly withdrawal limit of '.money($level->monthly_limit, $level->currency).'.'];
        }

        return ['ok' => true, 'reason' => null];
    }

    /** Withdrawals in flight or already paid — held/spent either way, so both count. */
    private function withdrawnSince(User $user, Carbon $since): float
    {
        return (float) $user->withdrawalRequests()
            ->where('created_at', '>=', $since)
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->sum('amount');
    }

    public function create(User $user, float $amount, SavedPaymentMethod $destination, string $pin): WithdrawalRequest
    {
        abort_unless($destination->user_id === $user->id, 403);

        if (! $user->hasTransactionPin()) {
            throw ValidationException::withMessages(['pin' => 'Set a transaction PIN in Security & Devices before withdrawing.']);
        }

        if (! Hash::check($pin, $user->transaction_pin)) {
            throw ValidationException::withMessages(['pin' => 'Incorrect PIN.']);
        }

        $limitCheck = $this->checkLimits($user, $amount);
        if (! $limitCheck['ok']) {
            throw ValidationException::withMessages(['amount' => $limitCheck['reason']]);
        }

        $quote = $this->quote($user, $amount);
        $wallet = $user->primaryWallet();

        return DB::transaction(function () use ($user, $amount, $destination, $wallet, $quote) {
            // Reserves the funds without moving them out of the wallet yet —
            // an admin still has to approve before the balance actually drops.
            $this->wallet->hold($wallet, $amount);

            $withdrawal = WithdrawalRequest::create([
                'reference' => reference('PB-WDR'),
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'saved_payment_method_id' => $destination->id,
                'destination_label' => $destination->label,
                'destination_account_ref' => $destination->account_ref,
                'amount' => $amount,
                'fee' => $quote['fee'],
                'net_amount' => $quote['net_amount'],
                'currency' => $wallet->currency,
                'status' => WithdrawalStatus::Pending,
                'pin_confirmed_at' => now(),
            ]);

            $this->audit->log('withdrawal.requested', "Withdrawal {$withdrawal->reference} requested for ".money($amount, $wallet->currency), $withdrawal, [], $user->id);

            return $withdrawal;
        });
    }

    public function cancel(WithdrawalRequest $withdrawal, User $actor): WithdrawalRequest
    {
        abort_unless($withdrawal->status === WithdrawalStatus::Pending, 422);

        return DB::transaction(function () use ($withdrawal, $actor) {
            $this->wallet->release($withdrawal->wallet, (float) $withdrawal->amount);
            $withdrawal->update(['status' => WithdrawalStatus::Cancelled]);
            $this->audit->log('withdrawal.cancelled', "Withdrawal {$withdrawal->reference} cancelled", $withdrawal, [], $actor->id);

            return $withdrawal->fresh();
        });
    }

    public function approve(WithdrawalRequest $withdrawal, User $admin): WithdrawalRequest
    {
        abort_unless($withdrawal->status === WithdrawalStatus::Pending, 422);

        $withdrawal->update(['status' => WithdrawalStatus::Approved, 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
        $this->audit->log('withdrawal.approved', "Withdrawal {$withdrawal->reference} approved", $withdrawal, [], $admin->id);

        return $withdrawal->fresh();
    }

    public function reject(WithdrawalRequest $withdrawal, string $reason, User $admin): WithdrawalRequest
    {
        abort_unless(in_array($withdrawal->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true), 422);

        return DB::transaction(function () use ($withdrawal, $reason, $admin) {
            $this->wallet->release($withdrawal->wallet, (float) $withdrawal->amount);
            $withdrawal->update([
                'status' => WithdrawalStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);
            $this->audit->log('withdrawal.rejected', "Withdrawal {$withdrawal->reference} rejected: {$reason}", $withdrawal, [], $admin->id);

            return $withdrawal->fresh();
        });
    }

    /** Admin confirms the payout actually landed — the only point the wallet balance drops. */
    public function markPaid(WithdrawalRequest $withdrawal, string $payoutReference, User $admin): WithdrawalRequest
    {
        abort_unless($withdrawal->status === WithdrawalStatus::Approved, 422);

        return DB::transaction(function () use ($withdrawal, $payoutReference, $admin) {
            $this->wallet->release($withdrawal->wallet, (float) $withdrawal->amount);
            $this->wallet->debit($withdrawal->wallet, (float) $withdrawal->amount, 'withdrawal', $withdrawal, "Withdrawal {$withdrawal->reference} paid out");

            $withdrawal->update([
                'status' => WithdrawalStatus::Paid,
                'payout_reference' => $payoutReference,
                'paid_at' => now(),
            ]);
            $this->audit->log('withdrawal.paid', "Withdrawal {$withdrawal->reference} paid out ({$payoutReference})", $withdrawal, [], $admin->id);

            return $withdrawal->fresh();
        });
    }
}
