<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Continue with Google" sign-up / sign-in. Credentials are read from the
 * admin Integrations page (settings) first, then .env. If unconfigured the
 * button is hidden and this controller fails gracefully.
 */
class GoogleController extends Controller
{
    private function configure(): bool
    {
        $clientId = setting('google_client_id', config('services.google.client_id'));
        $clientSecret = setting('google_client_secret', config('services.google.client_secret'));

        if (! $clientId || ! $clientSecret) {
            return false;
        }

        config([
            'services.google.client_id' => $clientId,
            'services.google.client_secret' => $clientSecret,
            'services.google.redirect' => url('/auth/google/callback'),
        ]);

        return true;
    }

    public function redirect()
    {
        if (! $this->configure()) {
            return redirect()->route('login')->with('error', 'Google sign-in is not configured yet.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        if (! $this->configure()) {
            return redirect()->route('login')->with('error', 'Google sign-in is not configured yet.');
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'Google sign-in failed. Please try again.');
        }

        $user = User::where('google_id', $google->getId())
            ->orWhere('email', $google->getEmail())
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $google->getName() ?: Str::before($google->getEmail(), '@'),
                'email' => $google->getEmail(),
                'password' => bcrypt(Str::random(32)),
                'role' => 'user',
                'email_verified_at' => now(),
            ]);
            $user->primaryWallet();
        }

        $user->forceFill([
            'google_id' => $google->getId(),
            'avatar_url' => $google->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}
