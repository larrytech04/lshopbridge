<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Security\CriticalAlertService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user');

        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }
        if ($module = $request->query('module')) {
            $query->where('action', 'like', "admin.{$module}.%");
        }
        if ($actor = $request->query('actor')) {
            $query->where('user_id', $actor);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Module list and actor list are derived from what's actually in the
        // table, so the filter dropdowns never offer an option with 0 results.
        $modules = AuditLog::query()->distinct()->pluck('action')
            ->map(fn ($a) => explode('.', $a)[1] ?? null)
            ->filter()->unique()->sort()->values();

        $actors = User::whereIn('id', AuditLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.audit.index', [
            'logs' => $query->latest()->paginate(40)->withQueryString(),
            'filters' => $request->only('action', 'module', 'actor', 'from', 'to'),
            'modules' => $modules,
            'actors' => $actors,
        ]);
    }

    public function show(AuditLog $log): View
    {
        return view('admin.audit.show', ['log' => $log->load('user')]);
    }

    public function verify(AuditLogger $auditLogger, CriticalAlertService $alerts)
    {
        $result = $auditLogger->verifyChain();

        if ($result['valid']) {
            return back()->with('success', "Integrity verified: all {$result['checked']} audit log entries are intact.");
        }

        $alerts->send(
            'Audit log integrity check FAILED',
            "The audit log hash chain is broken from entry #{$result['broken_at']} onward — its content or an earlier entry may have been altered.",
        );

        return back()->with('error', "Integrity check FAILED at entry #{$result['broken_at']}. The chain is broken from this entry onward, its content or an entry before it may have been altered.");
    }
}
