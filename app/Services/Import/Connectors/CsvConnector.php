<?php

namespace App\Services\Import\Connectors;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Expected header row (case-insensitive, any column order): name, category,
 * type, sku, price, cost_price, currency, stock, description, image_url, brand.
 */
class CsvConnector extends AbstractFileConnector
{
    protected function rows(string $filePath): iterable
    {
        $handle = fopen(Storage::disk('local')->path($filePath), 'r');
        $header = null;

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => Str::of((string) $h)->trim()->lower()->toString(), $row);

                continue;
            }

            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue; // blank line
            }

            yield array_combine($header, array_pad($row, count($header), null));
        }

        fclose($handle);
    }
}
