<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\SavedPaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedPaymentMethodController extends Controller
{
    public function index(Request $request): View
    {
        return view('payment-methods.index', [
            'saved' => $request->user()->savedPaymentMethods()->with('paymentMethod')->latest()->get(),
            'methods' => PaymentMethod::active()->where('deposit_enabled', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'label' => ['required', 'string', 'max:60'],
            'account_ref' => ['nullable', 'string', 'max:60'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        if (! empty($data['is_default'])) {
            $user->savedPaymentMethods()->update(['is_default' => false]);
        }

        $user->savedPaymentMethods()->create([
            'payment_method_id' => $data['payment_method_id'],
            'label' => $data['label'],
            'account_ref' => $data['account_ref'] ?? null,
            'is_default' => ! empty($data['is_default']) || $user->savedPaymentMethods()->count() === 0,
        ]);

        return back()->with('success', __('Payment method saved.'));
    }

    public function update(Request $request, SavedPaymentMethod $savedPaymentMethod): RedirectResponse
    {
        abort_unless($savedPaymentMethod->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'account_ref' => ['nullable', 'string', 'max:60'],
        ]);

        $savedPaymentMethod->update($data);

        return back()->with('success', __('Payment method updated.'));
    }

    public function makeDefault(Request $request, SavedPaymentMethod $savedPaymentMethod): RedirectResponse
    {
        abort_unless($savedPaymentMethod->user_id === $request->user()->id, 403);

        $request->user()->savedPaymentMethods()->update(['is_default' => false]);
        $savedPaymentMethod->update(['is_default' => true]);

        return back()->with('success', __('Default payment method updated.'));
    }

    public function destroy(Request $request, SavedPaymentMethod $savedPaymentMethod): RedirectResponse
    {
        abort_unless($savedPaymentMethod->user_id === $request->user()->id, 403);

        $wasDefault = $savedPaymentMethod->is_default;
        $savedPaymentMethod->delete();

        if ($wasDefault) {
            $request->user()->savedPaymentMethods()->latest()->first()?->update(['is_default' => true]);
        }

        return back()->with('success', __('Payment method removed.'));
    }
}
