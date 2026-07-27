<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\ReferralLead;
use App\Services\Security\FormProtectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ReferralLeadController extends Controller
{
    public function create(): View
    {
        return view('public.referral-interest', ['countries' => Country::active()->get()]);
    }

    public function store(Request $request, FormProtectionService $formProtection)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $guard = $formProtection->guard($request, 'referral', $data, [
            'protection_setting_key' => 'referral_protection',
            'turnstile_action' => 'referral',
            'allow_authenticated_bypass' => true,
        ]);

        if ($guard->outcome === 'rate_limited') {
            return back()->withInput()->with('error', 'Please wait a moment before submitting again.');
        }

        if ($guard->outcome === 'challenge_required') {
            return back()->withInput()->with('error', 'Please complete the verification below and try again.');
        }

        if ($guard->needsFakeSuccessResponse()) {
            return back()->with('success', "Thanks for your interest! Our team will reach out soon.");
        }

        $idempotencyKey = 'referral-submit:'.sha1($request->ip().$data['email']);
        if (! Cache::add($idempotencyKey, true, 30)) {
            return back()->with('success', "Thanks for your interest! Our team will reach out soon.");
        }

        ReferralLead::create($data + ['source' => 'referral_page', 'status' => 'new']);

        return back()->with('success', "Thanks for your interest! Our team will reach out soon.");
    }
}
