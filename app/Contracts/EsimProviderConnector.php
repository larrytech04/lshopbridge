<?php

namespace App\Contracts;

use App\Models\EsimProvisioning;
use App\Models\EsimTopup;
use App\Models\ImportSource;
use App\Models\ShopOrderItem;

/**
 * The contract every real eSIM wholesaler connector implements (Airalo,
 * eSIM Go, 1GLOBAL, ...). Deliberately separate from ProductSourceConnector:
 * that interface models one-shot catalog import + generic "supplier order"
 * fulfilment, but an eSIM has a real ongoing lifecycle (provisioning ->
 * installed -> activated -> usage -> top-up) that doesn't fit those verbs.
 * A class can NOT implement both interfaces at once (they declare a
 * same-named handleWebhook() with incompatible signatures) - eSIM provider
 * ImportSource rows are managed on their own dedicated admin screen
 * (Admin\EsimController), and ProductImportService::resolveConnector()
 * falls back to PlaceholderConnector for any connector_class that isn't
 * actually a ProductSourceConnector.
 *
 * Most connectors only implement a subset — see capabilities(). Calling an
 * undeclared method throws ConnectorCapabilityException, same pattern as
 * AbstractConnector, so the admin UI can tell "not built yet" apart from
 * "ran and found nothing."
 */
interface EsimProviderConnector
{
    /** Declares which of this connector's methods are actually implemented and callable today. */
    public function capabilities(): array;

    public function testConnection(ImportSource $source): array;

    /** @return array{currency: string, balance: float}|null */
    public function getAccountBalance(ImportSource $source): ?array;

    public function fetchDestinations(ImportSource $source): array;

    public function fetchRegions(ImportSource $source): array;

    public function fetchPlans(ImportSource $source, array $filters = []): array;

    public function fetchPlan(ImportSource $source, string $providerPackageId): array;

    /** Re-confirm a package still exists, is priced as expected, and is in stock before charging the customer. */
    public function validatePlan(ImportSource $source, string $providerPackageId): array;

    /**
     * Must be idempotent per $idempotencyKey — the connector (or the
     * provider's own API) must guarantee calling this twice with the same
     * key never creates two orders.
     */
    public function createOrder(ImportSource $source, string $providerPackageId, string $idempotencyKey): array;

    public function getOrder(ImportSource $source, string $providerOrderId): array;

    public function retrieveProvisioning(ImportSource $source, string $providerOrderId): array;

    public function getEsimStatus(ImportSource $source, string $iccid): array;

    public function getUsage(ImportSource $source, string $iccid): array;

    public function getTopupPlans(ImportSource $source, string $iccid): array;

    public function createTopup(ImportSource $source, string $iccid, string $providerTopupPackageId, string $idempotencyKey): array;

    public function cancelOrder(ImportSource $source, string $providerOrderId): array;

    public function requestRefund(ImportSource $source, string $providerOrderId, string $reason): array;

    public function suspendEsim(ImportSource $source, string $iccid): array;

    public function reactivateEsim(ImportSource $source, string $iccid): array;

    /** @return array{valid: bool, event: ?string, payload: array} */
    public function handleWebhook(ImportSource $source, array $headers, string $rawBody): array;
}
