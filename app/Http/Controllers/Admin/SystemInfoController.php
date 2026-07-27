<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Real environment/version/driver data, all pulled from app()/config() at
 * request time. Nothing here is cached or hardcoded separately from the
 * running application, so it can never drift from reality.
 */
class SystemInfoController extends Controller
{
    public function index(): View
    {
        return view('admin.system-info.index', [
            'appVersion' => config('platform.version'),
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
            'environment' => app()->environment(),
            'debugMode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'dbConnection' => config('database.default'),
            'dbDriver' => config('database.connections.'.config('database.default').'.driver'),
            'cacheDriver' => config('cache.default'),
            'queueDriver' => config('queue.default'),
            'sessionDriver' => config('session.driver'),
            'sessionLifetime' => config('session.lifetime'),
            'mailDriver' => config('mail.default'),
            'filesystemDriver' => config('filesystems.default'),
            'serverSoftware' => request()->server('SERVER_SOFTWARE', 'Unknown'),
            'opcacheEnabled' => function_exists('opcache_get_status') && opcache_get_status(false) !== false,
        ]);
    }
}
