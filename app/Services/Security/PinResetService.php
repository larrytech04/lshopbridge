<?php

namespace App\Services\Security;

use App\Models\User;
use App\Notifications\PinResetCodeMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Self-service "forgot PIN" flow: re-confirm the account password, then
 * clear an emailed code, before a new transaction PIN can be set without
 * knowing the old one. Exists so forgetting a PIN never requires server or
 * tinker access (see SecurityController::updatePin() for where the
 * resulting bypass is actually consumed).
 */
class PinResetService
{
    private const CODE_LENGTH = 6;

    private const CODE_TTL_MINUTES = 10;

    private const RESEND_COOLDOWN_SECONDS = 60;

    // How long a successfully verified code stays usable to actually save a
    // new PIN. Its own window (not reused from CODE_TTL_MINUTES) since it
    // starts counting from verification, not from when the code was sent.
    private const VERIFIED_WINDOW_MINUTES = 10;

    // Excludes 0/O and 1/I/L — easy to misread in an email, easy to mistype.
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** Generates and emails a fresh code, replacing any still-outstanding one. */
    public function sendCode(User $user): void
    {
        $code = $this->generateCode();

        $user->forceFill([
            'pin_reset_code' => $code,
            'pin_reset_code_expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'pin_reset_code_sent_at' => now(),
        ])->save();

        $user->notify(new PinResetCodeMail($code, self::CODE_TTL_MINUTES));
    }

    public function canResend(User $user): bool
    {
        return ! $user->pin_reset_code_sent_at || $user->pin_reset_code_sent_at->addSeconds(self::RESEND_COOLDOWN_SECONDS)->isPast();
    }

    public function resendWaitSeconds(User $user): int
    {
        if (! $user->pin_reset_code_sent_at) {
            return 0;
        }

        return max(0, self::RESEND_COOLDOWN_SECONDS - now()->diffInSeconds($user->pin_reset_code_sent_at));
    }

    public function verifyCode(User $user, string $code): bool
    {
        $key = "pin-reset-code:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, 6)) {
            return false;
        }

        $valid = $user->pin_reset_code
            && $user->pin_reset_code_expires_at
            && $user->pin_reset_code_expires_at->isFuture()
            && Hash::check(Str::upper($code), $user->pin_reset_code);

        if (! $valid) {
            RateLimiter::hit($key, 600);

            return false;
        }

        RateLimiter::clear($key);
        $user->forceFill(['pin_reset_code' => null, 'pin_reset_code_expires_at' => null])->save();

        // Grants a short-lived, single-use permission to set a new PIN
        // without the old one — consumed the moment it's actually used, see
        // consumeVerified() / SecurityController::updatePin().
        Cache::put($this->verifiedKey($user), true, now()->addMinutes(self::VERIFIED_WINDOW_MINUTES));

        return true;
    }

    /** True once a code has been verified and the window to actually set a
     *  new PIN hasn't expired yet. Peeking here never consumes it. */
    public function isVerified(User $user): bool
    {
        return Cache::has($this->verifiedKey($user));
    }

    /** Single-use: call only once the new PIN has actually been saved. */
    public function consumeVerified(User $user): void
    {
        Cache::forget($this->verifiedKey($user));
    }

    public function maskedEmail(User $user): string
    {
        [$name, $domain] = explode('@', $user->email, 2) + [1 => ''];
        $visible = Str::substr($name, 0, 1);

        return $visible.str_repeat('*', max(3, Str::length($name) - 1)).'@'.$domain;
    }

    private function verifiedKey(User $user): string
    {
        return "pin-reset-verified:{$user->id}";
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
