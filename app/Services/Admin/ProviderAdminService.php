<?php

namespace App\Services\Admin;

use App\Models\PaymentProvider;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Funding\FundingManager;
use App\Services\Payments\PaymentManager;
use Illuminate\Support\Facades\DB;

/**
 * Owns everything about a payment_providers row: general settings, encrypted
 * credentials (absorbed from the old Integrations page), and driving a real
 * testConnection() through whichever manager (PaymentManager for collection
 * providers, FundingManager for China-wallet funding providers) resolves it.
 */
class ProviderAdminService
{
    /** Editable credential fields per provider code — same schema the old Integrations page used. */
    public const CREDENTIAL_SCHEMA = [
        'mtn_momo' => ['base_url' => 'Base URL', 'subscription_key' => 'Subscription key', 'api_user' => 'API user', 'api_key' => 'API key', 'webhook_secret' => 'Webhook secret'],
        'orange_money' => ['base_url' => 'Base URL', 'client_id' => 'Client ID', 'client_secret' => 'Client secret', 'webhook_secret' => 'Webhook secret'],
        'flutterwave' => ['base_url' => 'Base URL', 'public_key' => 'Public key', 'secret_key' => 'Secret key', 'encryption_key' => 'Encryption key', 'webhook_secret' => 'Webhook secret'],
        'crypto' => ['base_url' => 'Base URL', 'api_key' => 'API key', 'webhook_secret' => 'Webhook secret'],
        'card' => ['base_url' => 'Base URL', 'api_key' => 'API key', 'webhook_secret' => 'Webhook secret'],
        'alipay' => ['base_url' => 'Base URL', 'partner_id' => 'Partner ID', 'api_key' => 'API key', 'api_secret' => 'API secret', 'webhook_secret' => 'Webhook secret'],
    ];

    public function __construct(
        private AuditLogger $audit,
        private PaymentManager $payments,
        private FundingManager $funding,
    ) {}

    public function update(PaymentProvider $provider, array $data, User $admin): PaymentProvider
    {
        return DB::transaction(function () use ($provider, $data, $admin) {
            $before = $provider->only(['name', 'mode', 'is_active', 'priority']);

            $update = [
                'name' => $data['name'] ?? $provider->name,
                'description' => $data['description'] ?? null,
                'mode' => $data['mode'] ?? $provider->mode,
                'is_active' => ! empty($data['is_active']),
                'priority' => (int) ($data['priority'] ?? $provider->priority ?? 0),
                'countries' => ! empty($data['countries']) ? array_values(array_filter((array) $data['countries'])) : null,
                'currencies' => ! empty($data['currencies']) ? array_values(array_filter((array) $data['currencies'])) : null,
                'updated_by' => $admin->id,
            ];

            // Credentials: blank submitted value = keep the existing encrypted secret.
            if (isset(self::CREDENTIAL_SCHEMA[$provider->code])) {
                $creds = $provider->credentials ?? [];
                foreach (array_keys(self::CREDENTIAL_SCHEMA[$provider->code]) as $field) {
                    $val = $data['credentials'][$field] ?? null;
                    if ($val !== null && $val !== '') {
                        $creds[$field] = $val;
                    }
                }
                $update['credentials'] = $creds;
            }

            $provider->update($update);
            // Credential values are never included in the audit properties payload.
            $this->audit->log('admin.provider.updated', "Updated provider {$provider->name}", $provider, [
                'before' => $before,
                'after' => array_diff_key($update, ['credentials' => null]),
            ], $admin->id);

            return $provider->fresh();
        });
    }

    /** Real, non-money-moving credential check. Result is persisted so the status shown is never stale/fabricated. */
    public function testConnection(PaymentProvider $provider, User $admin): array
    {
        $manager = $provider->kind === 'funding' ? $this->funding : $this->payments;

        try {
            $driver = $manager->driver($provider->code);
            $result = $driver->testConnection();
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => 'Could not resolve a driver for this provider: '.$e->getMessage()];
        }

        $provider->update([
            'last_tested_at' => now(),
            'last_test_ok' => $result['ok'],
            'last_test_message' => $result['message'],
        ]);
        $this->audit->log('admin.provider.test_connection', "Tested connection for {$provider->name}: ".($result['ok'] ? 'ok' : 'failed'), $provider, ['ok' => $result['ok']], $admin->id);

        return $result;
    }

    public function setActive(PaymentProvider $provider, bool $active, User $admin): PaymentProvider
    {
        $provider->update(['is_active' => $active, 'updated_by' => $admin->id]);
        $this->audit->log('admin.provider.'.($active ? 'activated' : 'deactivated'), "{$provider->name} ".($active ? 'activated' : 'deactivated'), $provider, [], $admin->id);

        return $provider->fresh();
    }

    /** Archive-not-delete: soft-deletes so providers referenced by historical transactions are never actually removed. */
    public function archive(PaymentProvider $provider, User $admin): void
    {
        $provider->update(['is_active' => false, 'updated_by' => $admin->id]);
        $this->audit->log('admin.provider.archived', "Archived provider {$provider->name}", $provider, [], $admin->id);
        $provider->delete();
    }

    public function restore(PaymentProvider $provider, User $admin): PaymentProvider
    {
        $provider->restore();
        $provider->update(['updated_by' => $admin->id]);
        $this->audit->log('admin.provider.restored', "Restored provider {$provider->name}", $provider, [], $admin->id);

        return $provider->fresh();
    }

    public function summary(): array
    {
        $providers = PaymentProvider::withTrashed()->get();

        return [
            'total' => $providers->count(),
            'active' => $providers->where('is_active', true)->count(),
            'connected' => $providers->filter(fn ($p) => $p->connectionStatus()->value === 'connected')->count(),
            'not_configured' => $providers->filter(fn ($p) => $p->connectionStatus()->value === 'not_configured')->count(),
            'failing' => $providers->filter(fn ($p) => in_array($p->connectionStatus()->value, ['authentication_failed', 'provider_offline', 'degraded'], true))->count(),
        ];
    }
}
