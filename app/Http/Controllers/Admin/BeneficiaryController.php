<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BeneficiaryAccount;
use App\Notifications\BeneficiaryReviewed;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BeneficiaryController extends Controller
{
    public function index(Request $request): View
    {
        $query = BeneficiaryAccount::with('user');
        if ($status = $request->query('status', 'pending')) {
            $query->where('status', $status);
        }

        return view('admin.beneficiaries.index', [
            'accounts' => $query->latest()->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function approve(BeneficiaryAccount $beneficiary, AuditLogger $audit)
    {
        $beneficiary->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        $audit->log('admin.beneficiary.approved', "Approved China wallet {$beneficiary->account_id}", $beneficiary);
        $beneficiary->user->notify(new BeneficiaryReviewed($beneficiary, true));

        return back()->with('success', 'China wallet approved.');
    }

    public function reject(Request $request, BeneficiaryAccount $beneficiary, AuditLogger $audit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $beneficiary->update(['status' => 'rejected', 'rejection_reason' => $data['reason'], 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        $audit->log('admin.beneficiary.rejected', "Rejected China wallet {$beneficiary->account_id}", $beneficiary, $data);
        $beneficiary->user->notify(new BeneficiaryReviewed($beneficiary, false, $data['reason']));

        return back()->with('success', 'China wallet rejected.');
    }
}
