<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Notifications\AgentVerified;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Agent::with('user', 'warehouseCountry');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.agents.index', [
            'agents' => $query->latest()->paginate(15)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Agent $agent): View
    {
        return view('admin.agents.show', [
            'agent' => $agent->load('user', 'warehouseCountry', 'countries', 'shippingRates'),
        ]);
    }

    public function approve(Agent $agent, AuditLogger $audit)
    {
        $agent->update(['status' => 'approved', 'verified_at' => now(), 'verified_by' => auth()->id()]);
        $agent->user->update(['kyc_level' => max($agent->user->kyc_level, 3)]);

        $audit->log('admin.agent.approved', "Approved agent {$agent->business_name}", $agent);
        $agent->user->notify(new AgentVerified($agent, true));

        return back()->with('success', 'Agent approved and listed.');
    }

    public function reject(Request $request, Agent $agent, AuditLogger $audit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $agent->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

        $audit->log('admin.agent.rejected', "Rejected agent {$agent->business_name}", $agent, $data);
        $agent->user->notify(new AgentVerified($agent, false, $data['reason']));

        return back()->with('success', 'Agent rejected.');
    }

    public function toggleFeature(Agent $agent)
    {
        $agent->update(['is_featured' => ! $agent->is_featured]);

        return back()->with('success', $agent->is_featured ? 'Agent featured.' : 'Agent un-featured.');
    }
}
