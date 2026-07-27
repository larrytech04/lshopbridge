<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CryptoWallet;
use App\Models\Deposit;
use App\Models\MomoNumber;
use App\Models\PaymentMethod;
use App\Services\Deposit\DepositService;
use App\Services\Payments\SandboxSimulator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function __construct(
        private DepositService $deposits,
        private SandboxSimulator $simulator,
    ) {}

    public function index(Request $request): View
    {
        return view('dashboard.deposit.index', [
            'methods' => PaymentMethod::active()->where('deposit_enabled', true)->get(),
            'recent' => $request->user()->deposits()->latest()->take(8)->get(),
            'momoNumbers' => MomoNumber::where('is_active', true)->get(),
            'cryptoWallets' => CryptoWallet::where('is_active', true)->get(),
            'bankAccounts' => BankAccount::where('is_active', true)->get(),
            'automationOn' => config('platform.automation.payments') && setting('payments_automation_enabled', true),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'phone' => ['nullable', 'string', 'max:30'],
            'tx_hash' => ['nullable', 'string', 'max:120'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $user = $request->user();
        $method = PaymentMethod::active()->where('deposit_enabled', true)->findOrFail($data['payment_method_id']);
        $amount = (float) $data['amount'];
        $phone = $data['phone'] ?? $user->phone;

        if ($method->max_amount && $amount > (float) $method->max_amount) {
            return back()->withErrors(['amount' => 'Amount exceeds the maximum for this method.'])->withInput();
        }
        if ($amount < (float) $method->min_amount) {
            return back()->withErrors(['amount' => 'Amount is below the minimum for this method.'])->withInput();
        }

        $automationOn = config('platform.automation.payments') && setting('payments_automation_enabled', true);

        // Automated collection: charge via provider, settle via webhook.
        if ($method->is_automated && $method->provider_code && $automationOn) {
            // tx_hash only applies to crypto methods; harmless null for the rest.
            // Stored in payer_details now so a future on-chain verifier/API can read it.
            $result = $this->deposits->createAutomated($user, $method, $amount, [
                'phone' => $phone,
                'email' => $user->email,
                'tx_hash' => $data['tx_hash'] ?? null,
            ]);

            // Live hosted-checkout providers return a redirect URL.
            if ($result['charge']->redirectUrl) {
                return redirect()->away($result['charge']->redirectUrl);
            }

            // Sandbox: replay the signed webhook through the real pipeline.
            $this->simulator->replay($method->provider_code, $result['charge']);

            $deposit = $result['deposit']->fresh();

            return redirect()->route('deposit.show', $deposit)->with(
                $deposit->status->value === 'confirmed' ? 'success' : 'info',
                $deposit->status->value === 'confirmed'
                    ? 'Payment confirmed automatically, your wallet has been credited.'
                    : 'Payment initiated. We are awaiting provider confirmation.'
            );
        }

        // Manual flow: create under review; admin confirms (proof optional).
        $deposit = $this->deposits->createManual($user, $method, $amount, $request->file('proof'), [
            'phone' => $phone,
        ]);

        return redirect()->route('deposit.show', $deposit)
            ->with('success', 'Deposit submitted. Follow the payment instructions; we will confirm shortly.');
    }

    public function show(Deposit $deposit): View
    {
        $this->authorize('view', $deposit);

        return view('dashboard.deposit.show', ['deposit' => $deposit->load('paymentMethod')]);
    }

    public function uploadProof(Request $request, Deposit $deposit)
    {
        $this->authorize('uploadProof', $deposit);

        $request->validate(['proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']]);

        $deposit->update([
            'proof_path' => $request->file('proof')->store('deposits/proofs', 'private'),
            'status' => \App\Enums\DepositStatus::UnderReview,
        ]);

        return back()->with('success', 'Proof of payment uploaded. Our team will review it.');
    }
}
