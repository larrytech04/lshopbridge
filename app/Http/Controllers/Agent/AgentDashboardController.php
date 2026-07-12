<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $agent = $request->user()->agent;

        abort_if($agent === null, 404);

        return view('agent.dashboard', [
            'agent' => $agent,
            'leads' => $agent->leads()->latest()->take(6)->get(),
            'reviews' => $agent->reviews()->where('status', 'approved')->latest()->take(5)->get(),
            'stats' => [
                'leads' => $agent->leads()->count(),
                'newLeads' => $agent->leads()->where('status', 'new')->count(),
                'rates' => $agent->shippingRates()->count(),
                'completed' => $agent->completed_orders,
            ],
        ]);
    }
}
