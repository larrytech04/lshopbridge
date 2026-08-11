<?php

namespace App\Services\Security;

use App\Models\User;
use App\Notifications\ReauthCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Idle-session step-up re-authentication, two tiers:
 *
 *  - 15-30 minutes idle: session stays alive, locked in place until the user
 *    clears a PIN check (if they've set one) and an emailed code.
 *  - 30+ minutes idle: the session is destroyed outright (a real logout) —
 *    signing back in requires the actual password again, and that fresh
 *    login is itself gated on the emailed code (not the PIN, since a
 *    password was just re-proven) before it's usable.
 *
 * Session-scoped throughout for the soft lock — each device/tab locks
 * independently, exactly like the PIN itself is per-account rather than
 * per-session. The hard-logout follow-up flag is per-account (cache), since
 * by definition there's no session left to scope it to.
 */
class ReauthService
{
    public const IDLE_MINUTES = 15;

    public const HARD_LOGOUT_MINUTES = 30;

    private const PENDING_CODE_TTL_HOURS = 2;

    private const CODE_LENGTH = 6;

    private const CODE_TTL_MINUTES = 10;

    private const RESEND_COOLDOWN_SECONDS = 60;

    // Excludes 0/O and 1/I/L — easy to misread in an email, easy to mistype.
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function touch(Request $request): void
    {
        $request->session()->put('reauth.last_activity', now()->timestamp);
    }

    /** Called on every authenticated request, before anything else runs. */
    public function armIfIdle(Request $request, User $user): void
    {
        if ($request->session()->get('reauth.locked')) {
            return;
        }

        $last = $request->session()->get('reauth.last_activity');

        if ($last && abs(now()->diffInMinutes(Carbon::createFromTimestamp($last))) >= self::IDLE_MINUTES) {
            $request->session()->put('reauth.locked', true);
            $request->session()->put('reauth.stage', $user->hasTransactionPin() ? 'pin' : 'email');
            $request->session()->put('reauth.intended', $request->fullUrl());
        }
    }

    /** Checked by the middleware BEFORE armIfIdle — a full logout, not a soft lock. */
    public function shouldHardLogout(Request $request): bool
    {
        $last = $request->session()->get('reauth.last_activity');

        return $last && abs(now()->diffInMinutes(Carbon::createFromTimestamp($last))) >= self::HARD_LOGOUT_MINUTES;
    }

    /** Flags the account (not the session — there won't be one) so the next
     *  successful login is gated on the emailed code before it's usable. */
    public function markPendingCodeRequirement(User $user): void
    {
        Cache::put($this->pendingCodeKey($user), true, now()->addHours(self::PENDING_CODE_TTL_HOURS));
    }

    /** Called right after a fresh login establishes a new session. If this
     *  account was hard-idle-logged-out, arms the same in-place lock at the
     *  email stage (skipping the PIN — a password was just re-proven) so the
     *  very next request lands on that screen instead of the dashboard. */
    public function consumePendingCodeRequirement(Request $request, User $user): bool
    {
        if (! Cache::pull($this->pendingCodeKey($user))) {
            return false;
        }

        $request->session()->put('reauth.locked', true);
        $request->session()->put('reauth.stage', 'email');
        $request->session()->put('reauth.intended', route('dashboard'));
        $this->sendCode($user);

        return true;
    }

    private function pendingCodeKey(User $user): string
    {
        return "reauth-pending-code:{$user->id}";
    }

    public function isLocked(Request $request): bool
    {
        return (bool) $request->session()->get('reauth.locked');
    }

    public function stage(Request $request): ?string
    {
        return $request->session()->get('reauth.stage');
    }

    public function intendedUrl(Request $request): string
    {
        return $request->session()->get('reauth.intended') ?: route('dashboard');
    }

    public function verifyPin(Request $request, User $user, string $pin): bool
    {
        $key = "reauth-pin:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return false;
        }

        if (! $user->hasTransactionPin() || ! Hash::check($pin, $user->transaction_pin)) {
            RateLimiter::hit($key, 600);

            return false;
        }

        RateLimiter::clear($key);
        $request->session()->put('reauth.stage', 'email');

        return true;
    }

    /** Generates and emails a fresh code, replacing any still-outstanding one. */
    public function sendCode(User $user): void
    {
        $code = $this->generateCode();

        $user->forceFill([
            'reauth_code' => $code,
            'reauth_code_expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'reauth_code_sent_at' => now(),
        ])->save();

        $user->notify(new ReauthCodeMail($code, self::CODE_TTL_MINUTES));
    }

    public function canResend(User $user): bool
    {
        return ! $user->reauth_code_sent_at || $user->reauth_code_sent_at->addSeconds(self::RESEND_COOLDOWN_SECONDS)->isPast();
    }

    public function resendWaitSeconds(User $user): int
    {
        if (! $user->reauth_code_sent_at) {
            return 0;
        }

        return max(0, self::RESEND_COOLDOWN_SECONDS - now()->diffInSeconds($user->reauth_code_sent_at));
    }

    public function verifyCode(Request $request, User $user, string $code): bool
    {
        $key = "reauth-code:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, 6)) {
            return false;
        }

        $valid = $user->reauth_code
            && $user->reauth_code_expires_at
            && $user->reauth_code_expires_at->isFuture()
            && Hash::check(Str::upper($code), $user->reauth_code);

        if (! $valid) {
            RateLimiter::hit($key, 600);

            return false;
        }

        RateLimiter::clear($key);
        RateLimiter::clear("reauth-pin:{$user->id}");

        $user->forceFill(['reauth_code' => null, 'reauth_code_expires_at' => null])->save();

        // 'reauth.intended' is deliberately left for the controller to read via
        // intendedUrl() right after this returns — it's harmless to leave stale,
        // the next real lock overwrites it before it's ever read again.
        $request->session()->forget(['reauth.locked', 'reauth.stage']);
        $this->touch($request);

        return true;
    }

    public function maskedEmail(User $user): string
    {
        [$name, $domain] = explode('@', $user->email, 2) + [1 => ''];
        $visible = Str::substr($name, 0, 1);

        return $visible.str_repeat('*', max(3, Str::length($name) - 1)).'@'.$domain;
    }

    private function generateCode(): string
    {
        $chars = self::CODE_ALPHABET;
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $code;
    }
}
