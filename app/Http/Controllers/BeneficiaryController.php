<?php

namespace App\Http\Controllers;

use App\Models\BeneficiaryAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BeneficiaryController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.beneficiaries.index', [
            'accounts' => $request->user()->beneficiaryAccounts()->latest()->get(),
            'apps' => config('funding.apps'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'app_type' => ['required', 'in:alipay,wechat,other'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_id' => ['required', 'string', 'max:160'],
            'qr' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        $account = new BeneficiaryAccount([
            'app_type' => $data['app_type'],
            'account_name' => $data['account_name'],
            'account_id' => $data['account_id'],
            'status' => 'pending',
            'is_default' => false,
        ]);
        $account->user()->associate($user);

        if ($request->hasFile('qr')) {
            $account->qr_path = $request->file('qr')->store('beneficiaries/qr', 'private');
        }

        $account->save();

        if ($request->boolean('is_default')) {
            $this->setDefault($account);
        }

        return back()->with('success', 'China wallet added. It will be available once verified.');
    }

    public function update(Request $request, BeneficiaryAccount $beneficiary)
    {
        $this->authorize('update', $beneficiary);

        $data = $request->validate([
            'account_name' => ['required', 'string', 'max:120'],
            'account_id' => ['required', 'string', 'max:160'],
            'qr' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],
        ]);

        $beneficiary->fill($data);
        // Any edit re-enters the review queue.
        $beneficiary->status = 'pending';
        $beneficiary->rejection_reason = null;

        if ($request->hasFile('qr')) {
            $beneficiary->qr_path = $request->file('qr')->store('beneficiaries/qr', 'private');
        }

        $beneficiary->save();

        return back()->with('success', 'China wallet updated and re-submitted for verification.');
    }

    public function makeDefault(BeneficiaryAccount $beneficiary)
    {
        $this->authorize('update', $beneficiary);
        $this->setDefault($beneficiary);

        return back()->with('success', 'Default funding account updated.');
    }

    public function destroy(BeneficiaryAccount $beneficiary)
    {
        $this->authorize('delete', $beneficiary);

        if ($beneficiary->qr_path) {
            Storage::disk('private')->delete($beneficiary->qr_path);
        }
        $beneficiary->delete();

        return back()->with('success', 'China wallet removed.');
    }

    private function setDefault(BeneficiaryAccount $account): void
    {
        $account->user->beneficiaryAccounts()->update(['is_default' => false]);
        $account->update(['is_default' => true]);
    }
}
