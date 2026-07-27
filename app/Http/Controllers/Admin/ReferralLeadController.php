<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralLead;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralLeadController extends Controller
{
    public function index(Request $request): View
    {
        $query = ReferralLead::with('country');
        $status = $request->query('status', 'new');
        if ($status) {
            $query->where('status', $status);
        }

        return view('admin.referral-leads.index', [
            'leads' => $query->latest()->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function update(Request $request, ReferralLead $referralLead)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,converted,declined'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $referralLead->update([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $referralLead->notes,
            'contacted_by' => $data['status'] === 'contacted' ? auth()->id() : $referralLead->contacted_by,
            'contacted_at' => $data['status'] === 'contacted' ? now() : $referralLead->contacted_at,
        ]);

        return back()->with('success', 'Lead updated.');
    }
}
