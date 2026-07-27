<?php

namespace App\Services\Import\Connectors;

use App\Contracts\ProductSourceConnector;
use App\Exceptions\ConnectorCapabilityException;
use App\Models\ImportSource;
use App\Models\ProductImport;
use App\Models\ShopOrder;
use App\Models\ShopProduct;

/**
 * Base class giving every real connector a "not supported" default for the
 * 19 interface methods, so a connector only has to override what it
 * genuinely implements — declared explicitly in capabilities().
 */
abstract class AbstractConnector implements ProductSourceConnector
{
    abstract public function capabilities(): array;

    public function connect(ImportSource $source): array
    {
        $this->guard('connect');
    }

    public function disconnect(ImportSource $source): void
    {
        $this->guard('disconnect');
    }

    public function testConnection(ImportSource $source): array
    {
        $this->guard('testConnection');
    }

    public function refreshCredentials(ImportSource $source): array
    {
        $this->guard('refreshCredentials');
    }

    public function fetchCategories(ImportSource $source): array
    {
        $this->guard('fetchCategories');
    }

    public function fetchProducts(ImportSource $source, array $options = []): array
    {
        $this->guard('fetchProducts');
    }

    public function fetchProductDetails(ImportSource $source, string $externalId): array
    {
        $this->guard('fetchProductDetails');
    }

    public function fetchVariants(ImportSource $source, string $externalId): array
    {
        $this->guard('fetchVariants');
    }

    public function fetchInventory(ImportSource $source, string $externalId): array
    {
        $this->guard('fetchInventory');
    }

    public function fetchPrices(ImportSource $source, string $externalId): array
    {
        $this->guard('fetchPrices');
    }

    public function fetchImages(ImportSource $source, string $externalId): array
    {
        $this->guard('fetchImages');
    }

    public function importProducts(ImportSource $source, ProductImport $run): array
    {
        $this->guard('importProducts');
    }

    public function synchronizeProduct(ImportSource $source, ShopProduct $product): array
    {
        $this->guard('synchronizeProduct');
    }

    public function synchronizeInventory(ImportSource $source, ShopProduct $product): array
    {
        $this->guard('synchronizeInventory');
    }

    public function synchronizePrices(ImportSource $source, ShopProduct $product): array
    {
        $this->guard('synchronizePrices');
    }

    public function createSupplierOrder(ImportSource $source, ShopOrder $order): array
    {
        $this->guard('createSupplierOrder');
    }

    public function cancelSupplierOrder(ImportSource $source, string $externalOrderId): array
    {
        $this->guard('cancelSupplierOrder');
    }

    public function fetchOrderStatus(ImportSource $source, string $externalOrderId): array
    {
        $this->guard('fetchOrderStatus');
    }

    public function fetchTracking(ImportSource $source, string $externalOrderId): array
    {
        $this->guard('fetchTracking');
    }

    public function handleWebhook(ImportSource $source, array $payload): array
    {
        $this->guard('handleWebhook');
    }

    private function guard(string $method): never
    {
        if (in_array($method, $this->capabilities(), true)) {
            // A capability was declared but the concrete class forgot to override it — a real bug, not an expected gap.
            throw new \LogicException(static::class." declares capability '{$method}' but doesn't override it.");
        }

        throw ConnectorCapabilityException::unsupported(static::class, $method);
    }
}
