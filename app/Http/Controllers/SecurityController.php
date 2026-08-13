<?php

namespace App\Http\Controllers;

use App\Notifications\SecurityAlert;
use App\Services\Security\LoginSecurityService;
use App\Services\Security\PinResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(Request $request, LoginSecurityService $loginSecurity, PinResetService $pinReset): View
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        // Landing here is the acknowledgment: every "Review" link and every
        // review-worthy SecurityAlert notification points here, so visiting
        // is what clears the dashboard's "needs attention" banner.
        $user->unreadNotifications()->where('type', SecurityAlert::class)->where('data->requires_review', true)->get()->each->markAsRead();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($s) => (object) [
                'id' => $s->id,
                'is_current' => $s->id === $currentSessionId,
                'ip' => $s->ip_address,
                'device' => $loginSecurity->describeDevice($s->user_agent),
                'last_active' => \Carbon\Carbon::createFromTimestamp($s->last_activity),
            ]);

        $recentLogins = $user->loginAttempts()->latest('id')->take(10)->get();

        return view('dashboard.security', [
            'user' => $user,
            'sessions' => $sessions,
            'recentLogins' => $recentLogins,
            // Stays true for the whole reset window (not just the one
            // redirect after verifying), so a refresh doesn't suddenly
            // re-demand the old PIN they came here specifically to skip.
            'pinResetVerified' => $user->hasTransactionPin() && $pinReset->isVerified($user),
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

    /** Set or change the transaction PIN, a 4-digit code required before authorizing
     *  transfers/withdrawals, separate from the login password. */
    public function updatePin(Request $request, PinResetService $pinReset)
    {
        $user = $request->user();
        $hadPin = $user->hasTransactionPin();

        // Cleared the forgot-PIN flow (password + emailed code, see
        // ForgotPinController) in the last few minutes — skip the normal
        // current_pin requirement exactly this once instead of it.
        $viaReset = $hadPin && $pinReset->isVerified($user);

        // Exactly 4, matching the PIN input at the point of an actual
        // transaction (see resources/views/dashboard/funding/create.blade.php)
        // — the transaction PIN's only job in this app, it is never part of
        // login/reauth. (The withdrawal flow that used to have its own such
        // input was removed 2026-08-12; WithdrawalService still checks the
        // PIN the same way if that ever comes back.)
        $rules = [
            'pin' => ['required', 'digits:4', 'confirmed'],
        ];
        if ($hadPin && ! $viaReset) {
            $rules['current_pin'] = ['required', 'digits:4'];
        }

        $data = $request->validate($rules);

        if ($hadPin && ! $viaReset && ! Hash::check($data['current_pin'], $user->transaction_pin)) {
            return back()->withErrors(['current_pin' => 'That current PIN is incorrect.']);
        }

        $user->update([
            'transaction_pin' => $data['pin'],
            'transaction_pin_set_at' => now(),
        ]);

        if ($viaReset) {
            $pinReset->consumeVerified($user);
        }

        // Every save gets an alert, not just the reset path — the PIN
        // authorizes real money movement, so the account owner should always
        // hear about it changing, and be reminded to keep it to themselves.
        // Notification content is plain English throughout this codebase
        // (see the other 16 classes in app/Notifications) — matching that,
        // not introducing translation in just this one.
        $user->notify(new SecurityAlert(
            title: match (true) {
                $viaReset => 'Your transaction PIN was reset',
                $hadPin => 'Your transaction PIN was changed',
                default => 'Transaction PIN created',
            },
            message: 'Your transaction PIN authorizes transfers and withdrawals from your wallet. Keep it safe, never share it with anyone, including our support team, who will never ask you for it. If you did not make this change, secure your account and contact support immediately.',
            requiresReview: $viaReset,
        ));

        return back()->with('success', 'Transaction PIN saved.');
    }

    /** Revoke a single session — the customer-facing equivalent of the admin-only action that already existed. */
    public function revokeSession(Request $request, string $sessionId)
    {
        $user = $request->user();

        $deleted = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        abort_unless($deleted, 404);

        return back()->with('success', 'That session has been signed out.');
    }

    /** Sign out every session except the one making this request. */
    public function revokeOtherSessions(Request $request)
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        $user->notify(new SecurityAlert(
            title: 'You signed out of all other sessions',
            message: 'Every session except the one you\'re using now has been signed out.',
        ));

        return back()->with('success', 'Signed out of all other sessions.');
    }
}
