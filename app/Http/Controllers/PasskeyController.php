<?php

namespace App\Http\Controllers;

use App\Models\WebauthnCredential;
use App\Notifications\SecurityAlert;
use App\Services\Security\WebauthnService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webauthn\Exception\AuthenticatorResponseVerificationException;

class PasskeyController extends Controller
{
    public function index(Request $request): View
    {
        return view('security.passkeys.index', [
            'passkeys' => WebauthnCredential::where('user_id', $request->user()->id)->latest()->get(),
        ]);
    }

    public function registerOptions(Request $request, WebauthnService $webauthn)
    {
        $result = $webauthn->creationOptionsFor($request->user());
        $request->session()->put('passkey_register_challenge', $result['challenge']);

        return response()->json($result['options']);
    }

    public function store(Request $request, WebauthnService $webauthn)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'response' => ['required', 'array'],
        ]);

        $challenge = $request->session()->get('passkey_register_challenge');
        if (! $challenge) {
            return response()->json(['message' => 'Your registration session expired. Please try again.'], 422);
        }

        try {
            $webauthn->verifyRegistration($data['response'], $challenge, $request->user(), $request);
        } catch (AuthenticatorResponseVerificationException $e) {
            return response()->json(['message' => 'Could not verify that passkey: '.$e->getMessage()], 422);
        }

        $request->session()->forget('passkey_register_challenge');

        $request->user()->notify(new SecurityAlert(
            title: 'A passkey was added to your account',
            message: "A new passkey (\"{$data['name']}\") was just added to your account.\n\nIf you didn't do this, secure your account immediately.",
            actionLabel: 'Review account security',
            actionUrl: route('security.index'),
        ));

        return response()->json(['message' => 'Passkey added.']);
    }

    public function destroy(Request $request, WebauthnCredential $passkey)
    {
        abort_unless($passkey->user_id === $request->user()->id, 404);

        $passkey->delete();

        $request->user()->notify(new SecurityAlert(
            title: 'A passkey was removed from your account',
            message: "The passkey \"{$passkey->name}\" was just removed from your account.\n\nIf you didn't do this, secure your account immediately.",
            actionLabel: 'Review account security',
            actionUrl: route('security.index'),
        ));

        return back()->with('success', 'Passkey removed.');
    }
}
