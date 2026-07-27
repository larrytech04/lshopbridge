<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Re-authentication gate for sensitive Platform Configuration actions
 * (editing provider credentials, revealing masked deposit-account numbers).
 * Mirrors the contract expected by Laravel's built-in RequirePassword
 * middleware, which is applied to those specific routes.
 */
class ConfirmablePasswordController extends Controller
{
    public function show(): View
    {
        return view('admin.auth.confirm-password');
    }

    public function store(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            throw ValidationException::withMessages(['password' => 'The password you entered is incorrect.']);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('admin.dashboard'));
    }
}
