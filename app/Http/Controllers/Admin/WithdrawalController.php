<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $query = WithdrawalRequest::with(['user', 'reviewedBy'])->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.withdrawals.index', [
            'withdrawals' => $query->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function approve(Request $request, WithdrawalRequest $withdrawal, WithdrawalService $svc)
    {
        $svc->approve($withdrawal, $request->user());

        return back()->with('success', 'Withdrawal approved.');
    }

    public function reject(Request $request, WithdrawalRequest $withdrawal, WithdrawalService $svc)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $svc->reject($withdrawal, $data['reason'], $request->user());

        return back()->with('success', 'Withdrawal rejected.');
    }

    public function markPaid(Request $request, WithdrawalRequest $withdrawal, WithdrawalService $svc)
    {
        $data = $request->validate(['payout_reference' => ['required', 'string', 'max:100']]);
        $svc->markPaid($withdrawal, $data['payout_reference'], $request->user());

        return back()->with('success', 'Withdrawal marked as paid.');
    }
}
