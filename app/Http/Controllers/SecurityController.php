<?php

namespace App\Http\Controllers;

use App\Notifications\SecurityAlert;
use App\Services\Security\LoginSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(Request $request, LoginSecurityService $loginSecurity): View
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
    public function updatePin(Request $request)
    {
        $user = $request->user();

        // Exactly 4, matching the reauth PIN dialer everywhere else in the
        // app (see resources/views/auth/reauth-pin.blade.php) — it only ever
        // collects 4 digits, so a longer PIN set here could never actually
        // be entered there.
        $rules = [
            'pin' => ['required', 'digits:4', 'confirmed'],
        ];
        if ($user->hasTransactionPin()) {
            $rules['current_pin'] = ['required', 'digits:4'];
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
