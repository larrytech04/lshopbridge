<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiskFlag;
use App\Models\RiskRule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiskController extends Controller
{
    public function index(Request $request): View
    {
        $query = RiskFlag::with('user', 'flaggable');
        if ($status = $request->query('status', 'open')) {
            $query->where('status', $status);
        }

        $rules = RiskRule::orderBy('code')->get();

        return view('admin.risk.index', [
            'flags' => $query->latest()->paginate(20)->withQueryString(),
            'rules' => $rules,
            'status' => $status,
            'stats' => [
                'open' => RiskFlag::where('status', 'open')->count(),
                'high_open' => RiskFlag::where('status', 'open')->where('severity', 'high')->count(),
                'rules_active' => $rules->where('is_active', true)->count(),
                'rules_total' => $rules->count(),
            ],
        ]);
    }

    public function resolveFlag(Request $request, RiskFlag $flag)
    {
        $data = $request->validate(['status' => ['required', 'in:reviewed,dismissed']]);
        $flag->update(['status' => $data['status'], 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        return back()->with('success', 'Flag '.$data['status'].'.');
    }

    public function storeRule(Request $request)
    {
        RiskRule::create($this->validatedRule($request));

        return back()->with('success', 'Risk rule added.');
    }

    public function updateRule(Request $request, RiskRule $rule)
    {
        $rule->update($this->validatedRule($request));

        return back()->with('success', 'Risk rule updated.');
    }

    private function validatedRule(Request $request): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'action' => ['required', 'in:flag,review,block'],
            'severity' => ['required', 'in:low,medium,high'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
