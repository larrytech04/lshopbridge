<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AgentRegistrationRequest;
use App\Models\Agent;
use App\Models\Country;
use App\Models\User;
use App\Services\Security\TurnstileVerificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AgentRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register-agent', ['countries' => Country::active()->get()]);
    }

    public function store(AgentRegistrationRequest $request, TurnstileVerificationService $turnstile)
    {
        if (setting('agent_registration_protection', true) && ! $turnstile->verify($request, 'agent_registration')->success) {
            return back()->withInput()->with('error', 'Please complete the bot-protection challenge.');
        }

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'country_id' => $request->country_id,
                'password' => Hash::make($request->password),
                'role' => UserRole::Agent->value,
                'kyc_level' => 0,
            ]);

            $user->primaryWallet();

            Agent::create([
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'warehouse_city' => $request->warehouse_city,
                'warehouse_country_id' => $request->country_id,
                'bio' => $request->bio,
                'phone' => $request->phone,
                'status' => 'pending',
            ]);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('agent.verification')
            ->with('success', 'Agent account created. Complete your business verification to get listed.');
    }
}
