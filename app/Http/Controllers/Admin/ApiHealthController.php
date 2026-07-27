<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ProviderHealthService;
use Illuminate\View\View;

/**
 * Honest reuse of the same data the dashboard's Provider Health widget
 * shows — derived from real webhook history and real infra checks. No API
 * response-time/uptime figure is fabricated; where nothing measures a thing,
 * it is left out rather than invented.
 */
class ApiHealthController extends Controller
{
    public function __construct(private ProviderHealthService $health) {}

    public function index(): View
    {
        return view('admin.api-health.index', [
            'providers' => $this->health->providerHealth(),
            'system' => $this->health->systemHealth(),
        ]);
    }
}
