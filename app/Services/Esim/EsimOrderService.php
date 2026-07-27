<?php

namespace App\Services\Esim;

use App\Enums\ShopOrderItemStatus;
use App\Models\EsimProvisioning;
use App\Models\ImportSource;
use App\Models\ShopOrderItem;
use App\Models\User;
use App\Notifications\EsimReadyToInstall;
use App\Services\Audit\AuditLogger;
use App\Services\Esim\Connectors\AiraloConnector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Real eSIM fulfilment, replacing ShopService's old generateSecret() fake-LPA
 * fallback for type=esim items. Idempotent per order item (safe to call
 * twice — a webhook retry or an admin re-clicking "fulfil" never creates a
 * second provisioning attempt).
 *
 * Today this always falls through to manual review: no eSIM provider has
 * real, verified credentials yet (Airalo Partner API access is in progress —
 * see AiraloConnector's own doc comment). The moment a connector's
 * testConnection() succeeds, provision() will attempt real automatic
 * ordering first and only fall back to manual review if that call itself
 * fails — no code change needed here when credentials arrive.
 */
class EsimOrderService
{
    public function __construct(private AuditLogger $audit) {}

    public function provision(ShopOrderItem $item): void
    {
        $provisioning = EsimProvisioning::firstOrCreate(
            ['shop_order_item_id' => $item->id],
            [
                'provider' => 'manual',
                'status' => 'pending_provisioning',
                'activation_policy' => $item->variant?->activation_policy,
                'installation_deadline_at' => $item->variant?->installation_deadline_days
                    ? now()->addDays($item->variant->installation_deadline_days)
                    : null,
            ]
        );

        if ($provisioning->status !== 'pending_provisioning') {
            return; // already progressed past this point — never re-provision.
        }

        $source = $this->connectedProvider();

        if ($source && $this->attemptAutomaticProvisioning($source, $item, $provisioning)) {
            $item->update(['status' => ShopOrderItemStatus::Fulfilled]);

            return;
        }

        // No live provider (the expected case today) or the live attempt
        // failed — leave the order genuinely pending rather than fabricating
        // a code, and tell staff there's real work to do.
        $item->update(['status' => ShopOrderItemStatus::PendingProvisioning]);
        $this->audit->log('esim.order.pending_manual_provisioning', "eSIM order item #{$item->id} awaiting manual fulfilment", $item);
        $this->notifyStaffOfPendingProvisioning($item);
    }

    /** Staff-entered fulfilment once a real activation code has been obtained (from the provider dashboard, or manually issued). */
    public function completeManualProvisioning(EsimProvisioning $provisioning, array $data, User $admin): EsimProvisioning
    {
        return DB::transaction(function () use ($provisioning, $data, $admin) {
            $provisioning->update([
                'provider' => $data['provider'] ?? 'manual',
                'iccid' => $data['iccid'] ?? null,
                'activation_code' => $data['activation_code'] ?? null,
                'sm_dp_address' => $data['sm_dp_address'] ?? null,
                'confirmation_code' => $data['confirmation_code'] ?? null,
                'lpa_string' => $data['lpa_string'] ?? $this->buildLpaString($data),
                'direct_install_url' => $data['direct_install_url'] ?? null,
                'status' => 'ready',
                'admin_notes' => $data['admin_notes'] ?? $provisioning->admin_notes,
            ]);

            $item = $provisioning->orderItem;
            $item->update(['status' => ShopOrderItemStatus::Fulfilled]);

            $order = $item->order;
            $order->loadMissing('items');
            if ($order->items->every(fn ($i) => $i->status === ShopOrderItemStatus::Fulfilled)) {
                $order->update(['status' => \App\Enums\ShopOrderStatus::Fulfilled]);
            }

            $this->audit->log('esim.order.manually_provisioned', "eSIM order item #{$item->id} manually fulfilled", $provisioning, [], $admin->id);
            $order->user->notify(new EsimReadyToInstall($provisioning));

            return $provisioning->fresh();
        });
    }

    private function attemptAutomaticProvisioning(ImportSource $source, ShopOrderItem $item, EsimProvisioning $provisioning): bool
    {
        try {
            $connector = app($source->connector_class);
            if (! in_array('createOrder', $connector->capabilities(), true)) {
                return false;
            }

            $packageId = $item->variant?->external_id;
            if (! $packageId) {
                return false; // this variant was never mapped to a real provider package.
            }

            $idempotencyKey = 'esim-order-'.$item->id;
            $order = $connector->createOrder($source, $packageId, $idempotencyKey);
            $data = $connector->retrieveProvisioning($source, (string) ($order['id'] ?? ''));

            if (empty($data['lpa_string']) && empty($data['sm_dp_address'])) {
                return false;
            }

            $provisioning->update([
                'provider' => $source->code,
                'provider_order_id' => $order['id'] ?? null,
                'provider_package_id' => $packageId,
                'iccid' => $data['iccid'] ?? null,
                'lpa_string' => $data['lpa_string'] ?? null,
                'sm_dp_address' => $data['sm_dp_address'] ?? null,
                'activation_code' => $data['activation_code'] ?? null,
                'confirmation_code' => $data['confirmation_code'] ?? null,
                'direct_install_url' => $data['direct_install_url'] ?? null,
                'status' => 'ready',
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('eSIM automatic provisioning failed, falling back to manual review', [
                'order_item_id' => $item->id, 'error' => $e->getMessage(),
            ]);
            $provisioning->update(['provider_error' => $e->getMessage()]);

            return false;
        }
    }

    /** The one eSIM provider slot that's genuinely connected and capable right now, if any. */
    private function connectedProvider(): ?ImportSource
    {
        return ImportSource::where('code', 'esim_providers')
            ->where('is_active', true)
            ->where('status', 'connected')
            ->whereNotNull('connector_class')
            ->first();
    }

    private function buildLpaString(array $data): ?string
    {
        if (empty($data['sm_dp_address']) || empty($data['activation_code'])) {
            return null;
        }

        return 'LPA:1$'.$data['sm_dp_address'].'$'.$data['activation_code'];
    }

    private function notifyStaffOfPendingProvisioning(ShopOrderItem $item): void
    {
        // Admin ops notification — reuses the same database+mail notification
        // channel pattern as SecurityAlert, addressed to admins instead of
        // the customer. Kept intentionally simple: a link into the new eSIM
        // Operations queue is enough for staff to act.
        User::whereIn('role', [\App\Enums\UserRole::Admin, \App\Enums\UserRole::SuperAdmin])
            ->get()
            ->each(fn (User $admin) => $admin->notify(new \App\Notifications\EsimPendingProvisioning($item)));
    }
}
