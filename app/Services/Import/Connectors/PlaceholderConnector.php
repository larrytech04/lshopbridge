<?php

namespace App\Services\Import\Connectors;

use App\Models\ImportSource;

/**
 * Backs every store-platform, marketplace, dropshipping, China-sourcing, and
 * digital-service connector slot that has no real API credentials configured
 * yet (which, honestly, is all of them right now — this app has never had a
 * real Shopify/Amazon/AliExpress/CJ/etc. integration). It declares zero
 * capabilities on purpose: calling any real operation throws a clear
 * "not connected" error instead of silently pretending to work.
 *
 * When a specific platform gets real, authorized API access configured, it
 * should get its own connector class extending AbstractConnector (or
 * AbstractFileConnector for feed-based ones) that genuinely implements the
 * methods it supports — never by flipping this class to return fake data.
 */
class PlaceholderConnector extends AbstractConnector
{
    public function capabilities(): array
    {
        return [];
    }

    public function testConnection(ImportSource $source): array
    {
        return [
            'ok' => false,
            'message' => "{$source->name} has no configured API credentials — connect this source before importing.",
        ];
    }
}
