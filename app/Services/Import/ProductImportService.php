<?php

namespace App\Services\Import;

use App\Contracts\ProductSourceConnector;
use App\Enums\ImportRunStatus;
use App\Jobs\RunProductImport;
use App\Models\ImportSource;
use App\Models\ProductImport;
use App\Models\ShopOrderItem;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Import\Connectors\PlaceholderConnector;
use Illuminate\Support\Facades\DB;

class ProductImportService
{
    public function __construct(private AuditLogger $audit) {}

    public function resolveConnector(ImportSource $source): ProductSourceConnector
    {
        $class = $source->connector_class;

        // Some slots (eSIM providers) speak EsimProviderConnector instead — a
        // real ongoing-lifecycle contract, not this one-shot catalog-import
        // interface. Falling back here avoids a TypeError; those sources are
        // managed on their own dedicated admin screen instead.
        if (! $class || ! class_exists($class) || ! is_a($class, ProductSourceConnector::class, true)) {
            return new PlaceholderConnector;
        }

        return app($class);
    }

    public function startImport(ImportSource $source, ?string $filePath, User $admin): ProductImport
    {
        $import = ProductImport::create([
            'import_source_id' => $source->id,
            'started_by' => $admin->id,
            'file_path' => $filePath,
            'status' => ImportRunStatus::Preparing,
            'started_at' => now(),
        ]);

        $this->audit->log('product_import.started', "Started import from {$source->name}", $import);

        RunProductImport::dispatch($import->id);

        return $import;
    }

    /** Executed by the queued job — kept synchronous/callable directly for tests. */
    public function run(ProductImport $import): void
    {
        $source = $import->importSource;
        $connector = $this->resolveConnector($source);

        $import->update(['status' => ImportRunStatus::CreatingProducts]);

        try {
            $result = $connector->importProducts($source, $import);

            $status = $result['failed'] > 0
                ? ($result['created'] > 0 || $result['updated'] > 0 ? ImportRunStatus::CompletedWithWarnings : ImportRunStatus::Failed)
                : ImportRunStatus::Completed;

            $import->update([
                'products_created' => $result['created'],
                'products_updated' => $result['updated'],
                'products_skipped' => $result['skipped'],
                'products_failed' => $result['failed'],
                'warning_count' => count($result['warnings']),
                'log' => $result['warnings'],
                'status' => $status,
                'completed_at' => now(),
            ]);

            $source->update([
                'last_import_at' => now(),
                'imported_count' => $source->imported_count + $result['created'] + $result['updated'],
                'error_count' => $source->error_count + $result['failed'],
            ]);

            $this->audit->log('product_import.completed', "Import from {$source->name} finished: {$result['created']} created, {$result['failed']} failed", $import);
        } catch (\Throwable $e) {
            $import->update([
                'status' => ImportRunStatus::Failed,
                'completed_at' => now(),
                'log' => [['row' => 0, 'level' => 'error', 'message' => $e->getMessage()]],
            ]);
            $this->audit->log('product_import.failed', "Import from {$source->name} failed: {$e->getMessage()}", $import);
        }
    }

    /** Safe rollback: only removes draft products from this run that were never ordered. */
    public function rollback(ProductImport $import, User $admin): array
    {
        $removed = 0;
        $kept = 0;

        DB::transaction(function () use ($import, &$removed, &$kept) {
            foreach ($import->rollbackEligibleProducts()->get() as $product) {
                $hasOrders = ShopOrderItem::where('shop_product_id', $product->id)->exists();
                if ($hasOrders) {
                    $kept++;

                    continue;
                }
                $product->delete();
                $removed++;
            }
        });

        $this->audit->log('product_import.rolled_back', "Rolled back import #{$import->id}: {$removed} removed, {$kept} kept (already ordered)", $import, [], $admin->id);

        return ['removed' => $removed, 'kept' => $kept];
    }
}
