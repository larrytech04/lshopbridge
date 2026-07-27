<?php

namespace App\Services\Admin;

use App\Models\PaymentProvider;
use App\Models\WebhookEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for "is this provider healthy" — shared by the
 * admin dashboard's Provider Health widget and the standalone API Health
 * page, so the two never drift. Every figure here is derived from real
 * webhook history and real infra checks (DB round-trip, cache round-trip,
 * queue/failed-job counts, disk space). No API response-time or uptime
 * metric is fabricated: where nothing in the codebase actually measures a
 * thing (e.g. live provider latency), it is left out rather than invented.
 */
class ProviderHealthService
{
    public function providerHealth(): Collection
    {
        $providers = PaymentProvider::orderBy('name')->get();
        $since = now()->subHours(24);

        return $providers->map(function ($provider) use ($since) {
            $recent = WebhookEvent::where('provider_code', $provider->code)->where('created_at', '>=', $since)->get();
            $total = $recent->count();
            $failed = $recent->whereIn('status', ['failed', 'invalid_signature'])->count();
            $lastOk = WebhookEvent::where('provider_code', $provider->code)->where('status', 'processed')->latest()->value('created_at');
            $lastFail = WebhookEvent::where('provider_code', $provider->code)->whereIn('status', ['failed', 'invalid_signature'])->latest()->value('created_at');

            $status = ! $provider->is_active ? 'Offline'
                : ($total === 0 ? 'No recent activity'
                : ($failed / max(1, $total) > 0.5 ? 'Partial outage'
                : ($failed / max(1, $total) > 0.15 ? 'Degraded' : 'Operational')));

            return [
                'provider' => $provider, 'status' => $status, 'total24h' => $total, 'failed24h' => $failed,
                'successRate' => $total > 0 ? round((1 - $failed / $total) * 100, 1) : null,
                'lastOk' => $lastOk, 'lastFail' => $lastFail,
            ];
        })->values();
    }

    public function systemHealth(): array
    {
        $dbOk = true;
        try { DB::select('select 1'); } catch (\Throwable $e) { $dbOk = false; }

        $cacheOk = true;
        try { Cache::put('health-check', 1, 5); $cacheOk = Cache::get('health-check') === 1; } catch (\Throwable $e) { $cacheOk = false; }

        $diskTotal = @disk_total_space(base_path());
        $diskFree = @disk_free_space(base_path());
        $diskUsedPct = ($diskTotal && $diskFree) ? round((1 - $diskFree / $diskTotal) * 100, 1) : null;

        return [
            'database' => $dbOk, 'cache' => $cacheOk,
            'queueBacklog' => DB::table('jobs')->count(),
            'failedJobs' => DB::table('failed_jobs')->count(),
            'activeSessions' => DB::table('sessions')->where('last_activity', '>', now()->subMinutes(15)->timestamp)->count(),
            'diskUsedPct' => $diskUsedPct,
            'diskFreeGb' => $diskFree ? round($diskFree / 1073741824, 1) : null,
        ];
    }
}
