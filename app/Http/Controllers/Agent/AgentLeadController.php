<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentLead;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentLeadController extends Controller
{
    public function index(Request $request): View
    {
        return view('agent.leads', [
            'leads' => $request->user()->agent->leads()->with('user')->latest()->paginate(15),
        ]);
    }

    public function update(Request $request, AgentLead $lead)
    {
        abort_unless($lead->agent_id === $request->user()->agent->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,in_progress,completed,closed'],
        ]);

        $lead->update($data);

        // Completing a lead awards reputation points + increments completed orders.
        if ($data['status'] === 'completed') {
            $agent = $lead->agent;
            $agent->increment('completed_orders');
            $agent->increment('points', 10);
        }

        return back()->with('success', 'Lead updated.');
    }
}
