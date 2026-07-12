<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Country;
use App\Models\User;
use App\Services\Security\Turnstile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', ['countries' => Country::active()->get()]);
    }

    public function store(RegisterRequest $request, Turnstile $turnstile)
    {
        if (! $turnstile->verify($request)) {
            return back()->withInput()->with('error', 'Please complete the bot-protection challenge.');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'country_id' => $request->country_id,
            'password' => Hash::make($request->password),
            'role' => UserRole::User->value,
            'kyc_level' => 0,
        ]);

        $user->primaryWallet();
        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Welcome to '.config('platform.name').'! Verify your email and phone to start funding.');
    }
}
