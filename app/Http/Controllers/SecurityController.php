<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.security', [
            'user' => $request->user(),
        ]);
    }

    /** The real "forgot password" flow requires being logged OUT (it's how you prove
     *  identity via email instead of a password you don't remember), /forgot-password
     *  sits behind guest middleware, so an authenticated visitor just bounces off it.
     *  Log out first, then land them on it for real. */
    public function forgotPassword(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('password.request');
    }

    /** Set or change the transaction PIN, a 4-6 digit code required before authorizing
     *  transfers/withdrawals, separate from the login password. */
    public function updatePin(Request $request)
    {
        $user = $request->user();

        $rules = [
            'pin' => ['required', 'digits_between:4,6', 'confirmed'],
        ];
        if ($user->hasTransactionPin()) {
            $rules['current_pin'] = ['required', 'digits_between:4,6'];
        }

        $data = $request->validate($rules);

        if ($user->hasTransactionPin() && ! Hash::check($data['current_pin'], $user->transaction_pin)) {
            return back()->withErrors(['current_pin' => 'That current PIN is incorrect.']);
        }

        $user->update([
            'transaction_pin' => $data['pin'],
            'transaction_pin_set_at' => now(),
        ]);

        return back()->with('success', 'Transaction PIN saved.');
    }
}
