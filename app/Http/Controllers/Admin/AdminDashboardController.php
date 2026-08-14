<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\FundingRequest;
use App\Models\ShopOrder;
use App\Services\Admin\DashboardReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request, DashboardReportService $reports): View
    {
        $period = $reports->resolvePeriod($request);
        $data = $reports->build($period);

        $hidden = auth()->user()->preferences['dashboard_hidden'] ?? [];

        return view('admin.dashboard.index', $data + ['hiddenSections' => $hidden]);
    }

    /**
     * Real-time refresh for the KPI ring cards (see admin/dashboard/_kpis.blade.php)
     * — same real query logic as the initial page load (DashboardReportService::kpis()),
     * just returned as JSON on a short poll instead of a full page render. Respects
     * whatever period the page currently has active (same query params as the page
     * itself), so refreshing doesn't silently reset an admin's chosen date range.
     */
    public function kpisLive(Request $request, DashboardReportService $reports)
    {
        $period = $reports->resolvePeriod($request);
        $kpis = $reports->kpis($period);

        $format = fn (array $row) => [
            'key' => $row['key'],
            'value_display' => $row['money'] ? number_format($row['value']).' '.config('platform.base_currency', 'XAF') : number_format($row['value']),
            'delta' => $row['delta'],
        ];

        return response()->json([
            'financial' => array_map($format, $kpis['financial']),
            'customer' => array_map($format, $kpis['customer']),
            'operational' => array_map($format, $kpis['operational']),
        ]);
    }

    public function updateWidgets(Request $request)
    {
        $data = $request->validate(['hidden' => ['array']]);
        $user = $request->user();
        $prefs = $user->preferences ?? [];
        $prefs['dashboard_hidden'] = $data['hidden'] ?? [];
        $user->update(['preferences' => $prefs]);

        return response()->json(['ok' => true]);
    }

    public function exportReport(Request $request, DashboardReportService $reports)
    {
        $period = $reports->resolvePeriod($request);
        $kpis = $reports->kpis($period);

        $filename = 'dashboard-report-'.$period['from']->format('Y-m-d').'-to-'.$period['to']->format('Y-m-d').'.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        return response()->stream(function () use ($kpis, $period) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Report period', $period['label'], $period['from']->toDateString(), $period['to']->toDateString()]);
            fputcsv($out, []);
            foreach (['financial' => 'Financial', 'customer' => 'Customer', 'operational' => 'Operational'] as $group => $title) {
                fputcsv($out, [$title]);
                fputcsv($out, ['Metric', 'Value', 'Previous period', 'Change %']);
                foreach ($kpis[$group] as $row) {
                    fputcsv($out, [$row['label'], $row['value'], $row['previous'], $row['delta']]);
                }
                fputcsv($out, []);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function transactionDetail(Request $request, string $type, int $id)
    {
        $model = match ($type) {
            'deposit' => Deposit::with('user.country', 'paymentMethod')->findOrFail($id),
            'funding' => FundingRequest::with('user.country')->findOrFail($id),
            'order' => ShopOrder::with('user.country', 'items')->findOrFail($id),
            default => abort(404),
        };

        return response()->json([
            'type' => $type,
            'reference' => $model->reference,
            'user' => $model->user->name,
            'email' => $model->user->email,
            'country' => $model->user->country->name ?? null,
            'status' => is_object($model->status) ? $model->status->value : $model->status,
            'created_at' => $model->created_at->format('M j, Y g:i A'),
            'amount' => $type === 'funding' ? $model->target_amount : ($type === 'order' ? $model->total : $model->net_amount),
            'currency' => $type === 'funding' ? $model->target_currency : ($model->currency ?? config('platform.base_currency')),
            'provider' => $model->provider_code ?? null,
            'provider_reference' => $model->provider_reference ?? null,
        ]);
    }
}
