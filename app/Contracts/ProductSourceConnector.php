<?php

namespace App\Contracts;

use App\Models\ImportSource;
use App\Models\ProductImport;
use App\Models\ShopOrder;
use App\Models\ShopProduct;

/**
 * The contract every product-source connector implements — native/manual,
 * file-based (CSV/XLSX/JSON/XML), store platforms, marketplaces, dropship/POD
 * suppliers, China-sourcing partners, digital-service providers, and generic
 * supplier feeds all speak this same interface, so the Import Center and
 * ProductImportService never special-case a specific platform.
 *
 * Most connectors only implement a handful of these methods — see
 * capabilities(). Calling an unsupported method throws
 * ConnectorCapabilityException rather than silently doing nothing, so the UI
 * can tell "not built yet" apart from "ran and found nothing".
 */
interface ProductSourceConnector
{
    /** Declares which of this connector's methods are actually implemented. */
    public function capabilities(): array;

    public function connect(ImportSource $source): array;

    public function disconnect(ImportSource $source): void;

    public function testConnection(ImportSource $source): array;

    public function refreshCredentials(ImportSource $source): array;

    public function fetchCategories(ImportSource $source): array;

    public function fetchProducts(ImportSource $source, array $options = []): array;

    public function fetchProductDetails(ImportSource $source, string $externalId): array;

    public function fetchVariants(ImportSource $source, string $externalId): array;

    public function fetchInventory(ImportSource $source, string $externalId): array;

    public function fetchPrices(ImportSource $source, string $externalId): array;

    public function fetchImages(ImportSource $source, string $externalId): array;

    /** @return array{created:int, updated:int, skipped:int, failed:int, warnings:array} */
    public function importProducts(ImportSource $source, ProductImport $run): array;

    public function synchronizeProduct(ImportSource $source, ShopProduct $product): array;

    public function synchronizeInventory(ImportSource $source, ShopProduct $product): array;

    public function synchronizePrices(ImportSource $source, ShopProduct $product): array;

    public function createSupplierOrder(ImportSource $source, ShopOrder $order): array;

    public function cancelSupplierOrder(ImportSource $source, string $externalOrderId): array;

    public function fetchOrderStatus(ImportSource $source, string $externalOrderId): array;

    public function fetchTracking(ImportSource $source, string $externalOrderId): array;

    public function handleWebhook(ImportSource $source, array $payload): array;
}
