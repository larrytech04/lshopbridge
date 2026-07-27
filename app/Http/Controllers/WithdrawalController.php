<?php

namespace App\Http\Controllers;

use App\Models\SavedPaymentMethod;
use App\Models\WithdrawalRequest;
use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $wallet = $user->primaryWallet();

        return view('withdrawals.index', [
            'user' => $user,
            'wallet' => $wallet,
            'destinations' => $user->savedPaymentMethods()->with('paymentMethod')->get(),
            'withdrawals' => $user->withdrawalRequests()->latest()->paginate(10),
        ]);
    }

    public function quote(Request $request, WithdrawalService $svc): JsonResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']]);

        return response()->json($svc->quote($request->user(), (float) $data['amount']));
    }

    public function store(Request $request, WithdrawalService $svc): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'saved_payment_method_id' => ['required', 'exists:saved_payment_methods,id'],
            'pin' => ['required', 'string'],
        ]);

        $destination = SavedPaymentMethod::findOrFail($data['saved_payment_method_id']);

        try {
            $svc->create($request->user(), (float) $data['amount'], $destination, $data['pin']);
        } catch (\App\Exceptions\InsufficientFundsException) {
            return back()->withErrors(['amount' => __('Your available balance is not enough for this withdrawal.')])->withInput();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', __('Withdrawal requested. We will review it shortly.'));
    }

    public function cancel(Request $request, WithdrawalRequest $withdrawal, WithdrawalService $svc): RedirectResponse
    {
        abort_unless($withdrawal->user_id === $request->user()->id, 403);

        $svc->cancel($withdrawal, $request->user());

        return back()->with('success', __('Withdrawal request cancelled.'));
    }
}
