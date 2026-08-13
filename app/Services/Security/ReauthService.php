<?php

namespace App\Services\Security;

use App\Models\User;
use App\Notifications\ReauthCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Idle-session step-up re-authentication, one tier: idle on an authenticated
 * session locks it in place — right where it is, session and login intact —
 * until an emailed code is entered. No password, no transaction PIN, nothing
 * destroyed; the account was already recognized as logged in on this
 * device, this only re-confirms the inbox is still reachable before handing
 * back anything sensitive.
 *
 * The threshold is role-dependent: 24 hours for a customer or agent device
 * that's expected to stay signed in for long stretches, but only 30 minutes
 * for an admin/super_admin account — an unattended open admin panel is a
 * much bigger blast radius than an unattended customer dashboard.
 *
 * The transaction PIN is deliberately NOT part of this at all, for any
 * role — its only job in this app is authorizing an actual
 * transfer/withdrawal at the point of the transaction (see
 * FundingController, WithdrawalService), never as a login/reauth gate.
 *
 * A real, deliberate logout always goes through the normal password (+
 * Turnstile/MFA as configured) login on the next visit — there is no
 * passwordless shortcut for that case, only for staying-signed-in-but-idle.
 *
 * Session-scoped: each device/tab locks independently.
 */
class ReauthService
{
    // A stayed-logged-in device only ever needs to re-prove it still
    // controls the inbox once a full day of inactivity has passed, not on
    // every short break.
    public const IDLE_MINUTES = 60 * 24;

    // Admins get a much tighter leash — 30 minutes, not 24 hours — since an
    // unattended, still-open admin panel is a far bigger risk than an
    // unattended customer dashboard.
    public const ADMIN_IDLE_MINUTES = 30;

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
        $threshold = $user->isAdmin() ? self::ADMIN_IDLE_MINUTES : self::IDLE_MINUTES;

        if ($last && abs(now()->diffInMinutes(Carbon::createFromTimestamp($last))) >= $threshold) {
            $request->session()->put('reauth.locked', true);
            $request->session()->put('reauth.intended', $request->fullUrl());
        }
    }

    public function isLocked(Request $request): bool
    {
        return (bool) $request->session()->get('reauth.locked');
    }

    public function intendedUrl(Request $request): string
    {
        return $request->session()->get('reauth.intended') ?: route('dashboard');
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

        $user->forceFill(['reauth_code' => null, 'reauth_code_expires_at' => null])->save();

        // 'reauth.intended' is deliberately left for the controller to read via
        // intendedUrl() right after this returns — it's harmless to leave stale,
        // the next real lock overwrites it before it's ever read again.
        $request->session()->forget('reauth.locked');
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
