<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Real disk usage for the app's local filesystem disks, plus the overall
 * server free/total space (same disk_free_space()/disk_total_space() call
 * ProviderHealthService::systemHealth() already uses for the dashboard, so
 * the two never disagree). S3 is configured in filesystems.php but nothing
 * in the app currently writes to it, so it is left out rather than shown
 * with fabricated usage numbers.
 */
class StorageController extends Controller
{
    private const DISKS = ['local', 'public', 'private'];

    public function index(): View
    {
        $disks = collect(self::DISKS)->map(function ($name) {
            $root = config("filesystems.disks.$name.root");
            [$bytes, $files] = $this->folderStats($root);

            return [
                'name' => $name,
                'root' => $root,
                'bytes' => $bytes,
                'files' => $files,
            ];
        });

        // local and private currently share the same physical root — flag it
        // rather than silently showing the same bytes twice as if independent.
        $sharedRoots = $disks->groupBy('root')->filter(fn ($g) => $g->count() > 1)
            ->flatMap(fn ($g) => $g->pluck('name'))->values();

        $diskTotal = @disk_total_space(base_path());
        $diskFree = @disk_free_space(base_path());

        return view('admin.storage.index', [
            'disks' => $disks,
            'sharedRoots' => $sharedRoots,
            'serverTotalGb' => $diskTotal ? round($diskTotal / 1073741824, 1) : null,
            'serverFreeGb' => $diskFree ? round($diskFree / 1073741824, 1) : null,
            'serverUsedPct' => ($diskTotal && $diskFree) ? round((1 - $diskFree / $diskTotal) * 100, 1) : null,
        ]);
    }

    /** @return array{0: int, 1: int} [totalBytes, fileCount] */
    private function folderStats(?string $path): array
    {
        if (! $path || ! is_dir($path)) {
            return [0, 0];
        }

        $bytes = 0;
        $files = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $bytes += $file->getSize();
                $files++;
            }
        }

        return [$bytes, $files];
    }
}
