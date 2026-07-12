<?php

namespace App\Http\Controllers;

use App\Models\KycLevel;
use App\Models\KycVerification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.verification', [
            'user' => $user,
            'levels' => KycLevel::orderBy('level')->get(),
            'latest' => $user->kycVerifications()->latest()->first(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:national_id,passport,drivers_license'],
            'document_number' => ['required', 'string', 'max:60'],
            'full_name' => ['required', 'string', 'max:160'],
            'date_of_birth' => ['required', 'date', 'before:-16 years'],
            'country_id' => ['required', 'exists:countries,id'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'id_front' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'id_back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'selfie' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'proof_of_address' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $user = $request->user();

        $kyc = new KycVerification([
            'document_type' => $data['document_type'],
            'document_number' => $data['document_number'],
            'full_name' => $data['full_name'],
            'date_of_birth' => $data['date_of_birth'],
            'country_id' => $data['country_id'],
            'city' => $data['city'],
            'address' => $data['address'],
            'status' => 'pending',
            'target_level' => 2,
        ]);
        $kyc->user()->associate($user);

        $kyc->id_front_path = $request->file('id_front')->store('kyc', 'private');
        if ($request->hasFile('id_back')) {
            $kyc->id_back_path = $request->file('id_back')->store('kyc', 'private');
        }
        $kyc->selfie_path = $request->file('selfie')->store('kyc', 'private');
        if ($request->hasFile('proof_of_address')) {
            $kyc->proof_of_address_path = $request->file('proof_of_address')->store('kyc', 'private');
        }

        $kyc->save();

        $user->update([
            'kyc_status' => 'pending',
            'city' => $data['city'],
            'address' => $data['address'],
            'country_id' => $data['country_id'],
            'date_of_birth' => $data['date_of_birth'],
        ]);

        return back()->with('success', 'Documents submitted. Verification usually takes a few hours.');
    }
}
