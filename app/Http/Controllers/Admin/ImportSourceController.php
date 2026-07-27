<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportSource;
use App\Models\ProductImport;
use App\Services\Audit\AuditLogger;
use App\Services\Import\ProductImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ImportSourceController extends Controller
{
    public function index(): View
    {
        ImportSource::ensureSeeded();

        $sources = ImportSource::withCount('products')->orderBy('name')->get()->groupBy('group');

        return view('admin.shop.imports.index', [
            'grouped' => $sources,
            'groupLabels' => ImportSource::groupLabels(),
            'recentImports' => ProductImport::with(['importSource', 'startedBy'])->latest()->take(15)->get(),
        ]);
    }

    public function connect(Request $request, ImportSource $source, ProductImportService $importService, AuditLogger $audit)
    {
        $data = $request->validate([
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:500'],
        ]);

        $source->update(['credentials' => array_filter($data['credentials'] ?? [])]);

        $connector = $importService->resolveConnector($source);
        $result = $connector->testConnection($source);

        $source->update(['status' => ($result['ok'] ?? false) ? 'connected' : 'needs_credentials']);
        $audit->log('import_source.credentials_updated', "Updated credentials for {$source->name}", $source);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message'] ?? 'Credentials saved.');
    }

    public function disconnect(ImportSource $source, AuditLogger $audit)
    {
        $source->update(['credentials' => null, 'status' => 'not_connected', 'is_active' => false]);
        $audit->log('import_source.disconnected', "Disconnected {$source->name}", $source);

        return back()->with('success', "{$source->name} disconnected.");
    }

    public function testConnection(ImportSource $source, ProductImportService $importService, AuditLogger $audit)
    {
        $connector = $importService->resolveConnector($source);
        $result = $connector->testConnection($source);

        $source->update(['status' => ($result['ok'] ?? false) ? 'connected' : ($source->credentials ? 'connection_failed' : 'needs_credentials')]);
        $audit->log('import_source.tested', "Tested connection for {$source->name}: ".($result['message'] ?? ''), $source);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message'] ?? 'Test failed.');
    }

    public function updateAutoSync(Request $request, ImportSource $source)
    {
        $data = $request->validate(['auto_sync' => ['required', 'in:manual,hourly,every_few_hours,daily,weekly,webhook,disabled']]);
        $source->update(['auto_sync' => $data['auto_sync']]);

        return back()->with('success', 'Synchronization schedule updated.');
    }

    public function startImport(Request $request, ImportSource $source, ProductImportService $importService)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,json', 'max:10240'],
        ]);

        if (! $source->isUsableWithoutCredentials()) {
            return back()->with('error', "{$source->name} needs to be connected with real credentials before it can import products.");
        }

        $path = $data['file']->store('imports', 'local');
        $import = $importService->startImport($source, $path, $request->user());

        return back()->with('success', "Import #{$import->id} queued — {$data['file']->getClientOriginalName()}.");
    }

    public function importDetail(ProductImport $import)
    {
        return response()->json([
            'id' => $import->id,
            'source' => $import->importSource->name,
            'started_by' => $import->startedBy?->name,
            'status' => $import->status->value,
            'status_label' => $import->status->label(),
            'products_created' => $import->products_created,
            'products_updated' => $import->products_updated,
            'products_skipped' => $import->products_skipped,
            'products_failed' => $import->products_failed,
            'warning_count' => $import->warning_count,
            'log' => $import->log,
            'started_at' => $import->started_at?->format('M j, Y g:ia'),
            'completed_at' => $import->completed_at?->format('M j, Y g:ia'),
            'rollback_eligible' => $import->rollbackEligibleProducts()->count(),
        ]);
    }

    public function rollback(ProductImport $import, ProductImportService $importService, Request $request)
    {
        $result = $importService->rollback($import, $request->user());

        return back()->with('success', "Rolled back {$result['removed']} draft product(s). {$result['kept']} kept (already ordered).");
    }
}
