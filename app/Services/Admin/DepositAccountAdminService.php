<?php

namespace App\Services\Admin;

use App\Models\BankAccount;
use App\Models\CryptoWallet;
use App\Models\MomoNumber;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Unifies the 3 manual-deposit account types (MoMo numbers, crypto wallets,
 * bank accounts) behind one admin surface. Sensitive fields (number/address/
 * account_number/iban) are masked everywhere except the explicit reveal
 * action, which is itself gated by password re-confirmation and audited —
 * mirroring the existing KYC/Beneficiary revealField pattern.
 */
class DepositAccountAdminService
{
    public const MODELS = [
        'momo' => MomoNumber::class,
        'crypto' => CryptoWallet::class,
        'bank' => BankAccount::class,
    ];

    /** Field(s) that hold the sensitive value per type, and its masked accessor. */
    private const SENSITIVE_FIELD = [
        'momo' => ['number', 'maskedNumber'],
        'crypto' => ['address', 'maskedAddress'],
        'bank' => ['account_number', 'maskedAccountNumber'],
    ];

    public function __construct(private AuditLogger $audit) {}

    public function modelClass(string $type): string
    {
        abort_unless(isset(self::MODELS[$type]), 404);

        return self::MODELS[$type];
    }

    public function create(string $type, array $data, User $admin): Model
    {
        $model = $this->modelClass($type)::create($data + ['updated_by' => $admin->id]);
        $this->audit->log('admin.deposit_account.created', ucfirst($type)." account created ({$model->getKey()})", $model, [], $admin->id);

        return $model;
    }

    public function update(string $type, Model $account, array $data, User $admin): Model
    {
        $account->update($data + ['updated_by' => $admin->id]);
        $this->audit->log('admin.deposit_account.updated', ucfirst($type)." account updated ({$account->getKey()})", $account, [], $admin->id);

        return $account->fresh();
    }

    public function setActive(string $type, Model $account, bool $active, User $admin): Model
    {
        $account->update(['is_active' => $active, 'updated_by' => $admin->id]);
        $this->audit->log('admin.deposit_account.'.($active ? 'activated' : 'deactivated'), ucfirst($type)." account ".($active ? 'activated' : 'deactivated'), $account, [], $admin->id);

        return $account->fresh();
    }

    /** Archive-not-delete: soft-deletes so accounts referenced by historical deposits are never actually removed. */
    public function archive(string $type, Model $account, User $admin): void
    {
        $account->update(['is_active' => false, 'updated_by' => $admin->id]);
        $this->audit->log('admin.deposit_account.archived', ucfirst($type)." account archived ({$account->getKey()})", $account, [], $admin->id);
        $account->delete();
    }

    public function restore(string $type, Model $account, User $admin): Model
    {
        $account->restore();
        $account->update(['updated_by' => $admin->id]);
        $this->audit->log('admin.deposit_account.restored', ucfirst($type)." account restored ({$account->getKey()})", $account, [], $admin->id);

        return $account->fresh();
    }

    /** Returns the real, unmasked sensitive value. Caller's route must require password.confirm — this call is always audited. */
    public function reveal(string $type, Model $account, User $admin): string
    {
        [$field] = self::SENSITIVE_FIELD[$type];
        $this->audit->log('admin.deposit_account.revealed', ucfirst($type)." account value revealed ({$account->getKey()})", $account, [], $admin->id);

        return (string) $account->{$field};
    }

    public function summary(): array
    {
        $momo = MomoNumber::withTrashed()->get();
        $crypto = CryptoWallet::withTrashed()->get();
        $bank = BankAccount::withTrashed()->get();
        $all = $momo->concat($crypto)->concat($bank);

        return [
            'total' => $all->count(),
            'active' => $all->where('is_active', true)->count(),
            'momo' => $momo->count(),
            'crypto' => $crypto->count(),
            'bank' => $bank->count(),
        ];
    }
}
