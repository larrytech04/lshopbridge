<?php

namespace App\Services\Admin;

use App\Models\ChinaWalletType;
use App\Models\User;
use App\Services\Audit\AuditLogger;

/**
 * Configures limits/instructions/provider-linkage for the wallet types this
 * platform already delivers to (alipay/wechat/other — see AppType). Does not
 * let an admin invent a new payment rail; `code` is fixed once created.
 */
class ChinaWalletTypeAdminService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(array $data, User $admin): ChinaWalletType
    {
        $wallet = ChinaWalletType::create($this->normalize($data) + ['updated_by' => $admin->id]);
        $this->audit->log('admin.china_wallet_type.created', "Created wallet type {$wallet->name}", $wallet, [], $admin->id);

        return $wallet;
    }

    public function update(ChinaWalletType $wallet, array $data, User $admin): ChinaWalletType
    {
        unset($data['code']);
        $wallet->update($this->normalize($data) + ['updated_by' => $admin->id]);
        $this->audit->log('admin.china_wallet_type.updated', "Updated wallet type {$wallet->name}", $wallet, [], $admin->id);

        return $wallet->fresh();
    }

    private function normalize(array $data): array
    {
        foreach (['qr_required', 'account_name_required', 'phone_required', 'automated_funding', 'manual_funding', 'is_active'] as $flag) {
            $data[$flag] = ! empty($data[$flag]);
        }
        $data['country_restrictions'] = ! empty($data['country_restrictions']) ? array_values(array_filter((array) $data['country_restrictions'])) : null;

        return $data;
    }

    public function setActive(ChinaWalletType $wallet, bool $active, User $admin): ChinaWalletType
    {
        $wallet->update(['is_active' => $active, 'updated_by' => $admin->id]);
        $this->audit->log('admin.china_wallet_type.'.($active ? 'activated' : 'deactivated'), "{$wallet->name} ".($active ? 'activated' : 'deactivated'), $wallet, [], $admin->id);

        return $wallet->fresh();
    }

    public function summary(): array
    {
        $wallets = ChinaWalletType::all();

        return [
            'total' => $wallets->count(),
            'active' => $wallets->where('is_active', true)->count(),
            'automated' => $wallets->where('automated_funding', true)->count(),
            'manual_only' => $wallets->where('automated_funding', false)->count(),
        ];
    }
}
