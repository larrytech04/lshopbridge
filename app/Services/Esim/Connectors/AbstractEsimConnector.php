<?php

namespace App\Services\Esim\Connectors;

use App\Contracts\EsimProviderConnector;
use App\Exceptions\ConnectorCapabilityException;
use App\Models\ImportSource;

/**
 * Base class giving every eSIM connector a "not supported" default for each
 * interface method, so a concrete connector only has to override what it
 * genuinely implements — declared explicitly in capabilities(). Mirrors
 * App\Services\Import\Connectors\AbstractConnector's guard pattern.
 */
abstract class AbstractEsimConnector implements EsimProviderConnector
{
    abstract public function capabilities(): array;

    public function testConnection(ImportSource $source): array
    {
        $this->guard('testConnection');
    }

    public function getAccountBalance(ImportSource $source): ?array
    {
        $this->guard('getAccountBalance');
    }

    public function fetchDestinations(ImportSource $source): array
    {
        $this->guard('fetchDestinations');
    }

    public function fetchRegions(ImportSource $source): array
    {
        $this->guard('fetchRegions');
    }

    public function fetchPlans(ImportSource $source, array $filters = []): array
    {
        $this->guard('fetchPlans');
    }

    public function fetchPlan(ImportSource $source, string $providerPackageId): array
    {
        $this->guard('fetchPlan');
    }

    public function validatePlan(ImportSource $source, string $providerPackageId): array
    {
        $this->guard('validatePlan');
    }

    public function createOrder(ImportSource $source, string $providerPackageId, string $idempotencyKey): array
    {
        $this->guard('createOrder');
    }

    public function getOrder(ImportSource $source, string $providerOrderId): array
    {
        $this->guard('getOrder');
    }

    public function retrieveProvisioning(ImportSource $source, string $providerOrderId): array
    {
        $this->guard('retrieveProvisioning');
    }

    public function getEsimStatus(ImportSource $source, string $iccid): array
    {
        $this->guard('getEsimStatus');
    }

    public function getUsage(ImportSource $source, string $iccid): array
    {
        $this->guard('getUsage');
    }

    public function getTopupPlans(ImportSource $source, string $iccid): array
    {
        $this->guard('getTopupPlans');
    }

    public function createTopup(ImportSource $source, string $iccid, string $providerTopupPackageId, string $idempotencyKey): array
    {
        $this->guard('createTopup');
    }

    public function cancelOrder(ImportSource $source, string $providerOrderId): array
    {
        $this->guard('cancelOrder');
    }

    public function requestRefund(ImportSource $source, string $providerOrderId, string $reason): array
    {
        $this->guard('requestRefund');
    }

    public function suspendEsim(ImportSource $source, string $iccid): array
    {
        $this->guard('suspendEsim');
    }

    public function reactivateEsim(ImportSource $source, string $iccid): array
    {
        $this->guard('reactivateEsim');
    }

    public function handleWebhook(ImportSource $source, array $headers, string $rawBody): array
    {
        $this->guard('handleWebhook');
    }

    private function guard(string $method): never
    {
        if (in_array($method, $this->capabilities(), true)) {
            throw new \LogicException(static::class." declares capability '{$method}' but doesn't override it.");
        }

        throw ConnectorCapabilityException::unsupported(static::class, $method);
    }
}
