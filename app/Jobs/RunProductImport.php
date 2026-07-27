<?php

namespace App\Jobs;

use App\Models\ProductImport;
use App\Services\Import\ProductImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * The first queued job in this codebase — large imports must not block the
 * request, per the spec's performance requirements. Uses the database queue
 * connection already configured (QUEUE_CONNECTION=database).
 */
class RunProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(private int $productImportId) {}

    public function handle(ProductImportService $service): void
    {
        $import = ProductImport::find($this->productImportId);

        if (! $import) {
            return;
        }

        $service->run($import);
    }
}
