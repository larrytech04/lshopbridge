<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientFundsException;
use App\Models\BeneficiaryAccount;
use App\Models\FundingRequest;
use App\Models\PaymentMethod;
use App\Services\Funding\FundingService;
use App\Services\Payments\SandboxSimulator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FundingController extends Controller
{
    public function __construct(
        private FundingService $funding,
        private SandboxSimulator $simulator,
    ) {}

    public function index(Request $request): View
    {
        return view('dashboard.funding.index', [
            'requests' => $request->user()->fundingRequests()->latest()->paginate(12),
        ]);
    }

    public function create(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $beneficiaries = $user->beneficiaryAccounts()->where('status', 'approved')->get();

        if ($beneficiaries->isEmpty()) {
            return redirect()->route('beneficiaries.index')
                ->with('warning', 'Add and verify a China wallet (Alipay/WeChat) before funding.');
        }

        return view('dashboard.funding.create', [
            'user' => $user,
            'beneficiaries' => $beneficiaries,
            'wallet' => $user->primaryWallet(),
            'methods' => PaymentMethod::active()->where('is_automated', true)->where('deposit_enabled', true)->get(),
            'sampleQuote' => $this->funding->quote(50000),
        ]);
    }

    public function quote(Request $request, FundingService $funding): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'app_type' => ['nullable', 'string'],
        ]);

        return response()->json($funding->quote((float) $data['amount'], $data['app_type'] ?? null, $request->user()));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'beneficiary_account_id' => ['required', 'exists:beneficiary_accounts,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'funding_source' => ['required', 'in:wallet,direct'],
            'payment_method_id' => ['required_if:funding_source,direct', 'nullable', 'exists:payment_methods,id'],
            'pin' => ['required_if:funding_source,wallet', 'nullable', 'string'],
        ]);

        $user = $request->user();

        // Step-up confirmation: spending money already sitting in the platform
        // wallet gets the same PIN check as a withdrawal. Paying "direct" charges
        // a fresh external method instead, so there's no standing balance at risk.
        if ($data['funding_source'] === 'wallet') {
            if (! $user->hasTransactionPin()) {
                return back()->withErrors(['pin' => 'Set a transaction PIN in Security & Devices before funding from your wallet.'])->withInput();
            }
            if (! \Illuminate\Support\Facades\Hash::check($data['pin'], $user->transaction_pin)) {
                return back()->withErrors(['pin' => 'Incorrect PIN.'])->withInput();
            }
        }

        /** @var BeneficiaryAccount $beneficiary */
        $beneficiary = $user->beneficiaryAccounts()->where('status', 'approved')->findOrFail($data['beneficiary_account_id']);
        $amount = (float) $data['amount'];

        try {
            if ($data['funding_source'] === 'wallet') {
                $funding = $this->funding->createFromWallet($user, $beneficiary, $amount);
            } else {
                $method = PaymentMethod::active()->where('deposit_enabled', true)->findOrFail($data['payment_method_id']);
                $result = $this->funding->createWithDirectPayment($user, $beneficiary, $amount, $method, [
                    'phone' => $user->phone, 'email' => $user->email,
                ]);

                if ($result['charge']->redirectUrl) {
                    return redirect()->away($result['charge']->redirectUrl);
                }

                $this->simulator->replay($method->provider_code, $result['charge']);
                $funding = $result['funding']->fresh();
            }
        } catch (InsufficientFundsException $e) {
            return back()->withErrors(['amount' => 'Insufficient wallet balance. Top up your wallet or pay directly.'])->withInput();
        }

        return redirect()->route('funding.show', $funding)
            ->with('success', $this->statusMessage($funding));
    }

    public function show(FundingRequest $funding): View
    {
        $this->authorize('view', $funding);

        return view('dashboard.funding.show', ['funding' => $funding->load('beneficiary', 'deposit')]);
    }

    private function statusMessage(FundingRequest $funding): string
    {
        return match ($funding->status->value) {
            'funding_successful' => 'Done! '.money($funding->target_amount, $funding->target_currency).' was delivered automatically.',
            'funding_processing' => 'Payment received, your China wallet funding is being processed.',
            'manual_review' => 'Payment received. This request needs a quick manual review and will be completed shortly.',
            default => 'Your funding request has been created.',
        };
    }
}
