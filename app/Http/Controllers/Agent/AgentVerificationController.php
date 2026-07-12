<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentVerificationController extends Controller
{
    public function edit(Request $request): View
    {
        return view('agent.verification', ['agent' => $request->user()->agent]);
    }

    public function store(Request $request)
    {
        $agent = $request->user()->agent;

        $data = $request->validate([
            'registration_number' => ['nullable', 'string', 'max:80'],
            'business_doc' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'id_doc' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $agent->update([
            'registration_number' => $data['registration_number'] ?? $agent->registration_number,
            'business_doc_path' => $request->file('business_doc')->store('agents/docs', 'private'),
            'id_doc_path' => $request->file('id_doc')->store('agents/docs', 'private'),
            'status' => 'pending',
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Verification documents submitted. We will review your business shortly.');
    }
}
