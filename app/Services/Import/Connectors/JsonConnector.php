<?php

namespace App\Services\Import\Connectors;

use Illuminate\Support\Facades\Storage;

/**
 * Expects a JSON array of objects with keys: name, category, type, sku,
 * price, cost_price, currency, stock, description, image_url, brand.
 */
class JsonConnector extends AbstractFileConnector
{
    protected function rows(string $filePath): iterable
    {
        $contents = Storage::disk('local')->get($filePath);
        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            return;
        }

        foreach ($decoded as $row) {
            if (is_array($row)) {
                yield array_change_key_case($row, CASE_LOWER);
            }
        }
    }
}
