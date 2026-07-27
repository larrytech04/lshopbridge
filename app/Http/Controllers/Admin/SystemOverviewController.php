<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTaskRun;
use App\Models\WebhookEvent;
use App\Services\Admin\ProviderHealthService;
use Illuminate\View\View;

/**
 * The single "is the platform healthy" landing page. Reuses
 * ProviderHealthService so this never drifts from the main admin dashboard's
 * Provider Health widget or the standalone API Health page. Anything the
 * codebase genuinely cannot measure (email delivery rate, backup status,
 * open error count) is shown as "Not configured" / "Data unavailable"
 * rather than a fabricated number — see the System & Operations scope
 * decision this page implements.
 */
class SystemOverviewController extends Controller
{
    public function index(ProviderHealthService $health): View
    {
        $system = $health->systemHealth();
        $providers = $health->providerHealth();

        $webhookFailures24h = WebhookEvent::whereIn('status', ['failed', 'invalid_signature'])
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $lastSchedulerRun = ScheduledTaskRun::latest('started_at')->first();

        return view('admin.system.index', [
            'system' => $system,
            'providersOnline' => $providers->where('status', 'Operational')->count(),
            'providersTotal' => $providers->count(),
            'webhookFailures24h' => $webhookFailures24h,
            'lastSchedulerRun' => $lastSchedulerRun,
        ]);
    }
}
