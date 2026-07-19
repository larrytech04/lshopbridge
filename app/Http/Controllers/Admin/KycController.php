<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Notifications\KycReviewed;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KycController extends Controller
{
    public function index(Request $request): View
    {
        $query = KycVerification::with('user', 'country');
        if ($status = $request->query('status', 'pending')) {
            $query->where('status', $status);
        }

        return view('admin.kyc.index', [
            'items' => $query->latest()->paginate(15)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(KycVerification $kyc): View
    {
        return view('admin.kyc.show', ['kyc' => $kyc->load('user', 'country')]);
    }

    public function approve(KycVerification $kyc, AuditLogger $audit)
    {
        $user = $kyc->user;
        $wasVerified = $user->kyc_level >= 2;

        $kyc->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        $user->update([
            'kyc_status' => 'approved',
            'kyc_level' => max($user->kyc_level, $kyc->target_level),
        ]);

        // Referral payout, once, the first time this user reaches full (L2) verification.
        if (! $wasVerified && $user->kyc_level >= 2 && $user->referred_by) {
            $user->increment('points', config('platform.referrals.referred_points'));
            \App\Models\User::whereKey($user->referred_by)->increment('points', config('platform.referrals.referrer_points'));
        }

        $audit->log('admin.kyc.approved', "Approved KYC for {$kyc->user->email}", $kyc);
        $kyc->user->notify(new KycReviewed($kyc, true));

        return redirect()->route('admin.kyc.index')->with('success', 'KYC approved.');
    }

    public function reject(Request $request, KycVerification $kyc, AuditLogger $audit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $kyc->update(['status' => 'rejected', 'rejection_reason' => $data['reason'], 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        $kyc->user->update(['kyc_status' => 'rejected']);

        $audit->log('admin.kyc.rejected', "Rejected KYC for {$kyc->user->email}", $kyc, $data);
        $kyc->user->notify(new KycReviewed($kyc, false, $data['reason']));

        return redirect()->route('admin.kyc.index')->with('success', 'KYC rejected.');
    }
}
