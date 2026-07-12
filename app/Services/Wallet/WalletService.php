<?php

namespace App\Services\Wallet;

use App\Exceptions\InsufficientFundsException;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The ONLY way wallet balances are allowed to change. Every mutation is wrapped
 * in a DB transaction with a row lock and writes an immutable ledger entry to
 * wallet_transactions, so balances are always reconstructable + auditable.
 *
 * NOTE: amounts are stored as decimal(18,2). For very high volume you'd switch
 * to integer minor units — the service boundary makes that a localised change.
 */
class WalletService
{
    public function credit(Wallet $wallet, float $amount, string $category, ?Model $source = null, string $description = '', array $meta = []): WalletTransaction
    {
        return $this->mutate($wallet, 'credit', $amount, $category, $source, $description, $meta);
    }

    public function debit(Wallet $wallet, float $amount, string $category, ?Model $source = null, string $description = '', array $meta = []): WalletTransaction
    {
        return $this->mutate($wallet, 'debit', $amount, $category, $source, $description, $meta);
    }

    /** Move funds from available into the locked bucket (e.g. while under review). */
    public function hold(Wallet $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $locked = $wallet->lockForUpdate()->find($wallet->id);
            if ($locked->availableBalance() < $amount) {
                throw new InsufficientFundsException;
            }
            $locked->increment('locked_balance', $this->round($amount));
        });
    }

    public function release(Wallet $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $locked = $wallet->lockForUpdate()->find($wallet->id);
            $locked->decrement('locked_balance', min((float) $locked->locked_balance, $this->round($amount)));
        });
    }

    private function mutate(Wallet $wallet, string $type, float $amount, string $category, ?Model $source, string $description, array $meta): WalletTransaction
    {
        $amount = $this->round($amount);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transaction amount must be positive.');
        }

        return DB::transaction(function () use ($wallet, $type, $amount, $category, $source, $description, $meta) {
            /** @var Wallet $locked */
            $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ($type === 'debit') {
                if ($locked->availableBalance() < $amount) {
                    throw new InsufficientFundsException;
                }
                $locked->balance = $this->round((float) $locked->balance - $amount);
            } else {
                $locked->balance = $this->round((float) $locked->balance + $amount);
            }

            $locked->save();

            $tx = new WalletTransaction([
                'reference' => reference('PB-TXN'),
                'user_id' => $locked->user_id,
                'type' => $type,
                'category' => $category,
                'amount' => $amount,
                'balance_after' => $locked->balance,
                'currency' => $locked->currency,
                'description' => $description,
                'meta' => $meta ?: null,
            ]);
            $tx->wallet()->associate($locked);

            if ($source) {
                $tx->source()->associate($source);
            }

            $tx->save();

            return $tx;
        });
    }

    private function round(float $amount): float
    {
        return round($amount, 2);
    }
}
