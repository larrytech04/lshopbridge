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

        if ($data['status'] === 'completed') {
            $lead->markCompleted();
        } else {
            $lead->update($data);
        }

        return back()->with('success', 'Lead updated.');
    }

    public function show(Request $request, AgentLead $lead): View
    {
        abort_unless($lead->agent_id === $request->user()->agent->id, 403);

        return view('agent.leads-show', [
            'lead' => $lead->load('user', 'messages.user'),
        ]);
    }

    public function sendMessage(Request $request, AgentLead $lead)
    {
        abort_unless($lead->agent_id === $request->user()->agent->id, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1500'],
        ]);

        $lead->messages()->create([
            'user_id' => $request->user()->id,
            'is_agent' => true,
            'message' => $data['message'],
        ]);

        if ($lead->status === 'new') {
            $lead->update(['status' => 'contacted']);
        }

        return back();
    }

    public function pollMessages(Request $request, AgentLead $lead)
    {
        abort_unless($lead->agent_id === $request->user()->agent->id, 403);

        return response()->json([
            'messages' => $lead->messages()->with('user')->get()->map(fn ($m) => [
                'is_agent' => $m->is_agent,
                'name' => $m->user->name,
                'message' => $m->message,
                'time' => $m->created_at->diffForHumans(),
            ]),
            'status' => $lead->status,
            'customer_confirmed' => (bool) $lead->customer_confirmed_at,
        ]);
    }
}
