<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/**
 * Wraps native Laravel cache-clearing commands. No fabricated "cache hit
 * rate" or similar metric is shown here — a database/file cache driver
 * doesn't expose that data, so it is left out rather than invented.
 */
class CacheController extends Controller
{
    private const ACTIONS = [
        'application' => ['label' => 'Application cache', 'command' => 'cache:clear'],
        'config' => ['label' => 'Config cache', 'command' => 'config:clear'],
        'route' => ['label' => 'Route cache', 'command' => 'route:clear'],
        'view' => ['label' => 'Compiled views', 'command' => 'view:clear'],
        'settings' => ['label' => 'Platform settings cache', 'command' => null],
    ];

    public function index(): View
    {
        return view('admin.cache.index', [
            'driver' => config('cache.default'),
            'actions' => self::ACTIONS,
        ]);
    }

    public function clear(string $key, SettingsService $settings): RedirectResponse
    {
        $action = self::ACTIONS[$key] ?? abort(404);

        if ($key === 'settings') {
            $settings->flush();
        } else {
            Artisan::call($action['command']);
        }

        return back()->with('success', $action['label'].' cleared.');
    }
}
