<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentReviewController extends Controller
{
    public function index(Request $request): View
    {
        $agent = $request->user()->agent;

        return view('agent.reviews', [
            'agent' => $agent,
            'reviews' => $agent->reviews()->with('user')->latest()->paginate(15),
        ]);
    }
}
