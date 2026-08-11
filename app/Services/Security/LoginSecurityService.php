<?php

namespace App\Services\Security;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Notifications\SecurityAlert;
use Illuminate\Http\Request;

/**
 * Records every login attempt and detects two signals: "have we seen this
 * exact browser/OS string succeed for this user before?" (always available,
 * no third party needed) and, when a geo-IP provider is configured (see
 * GeoIpService — off by default), "have we seen this country before?". VPN
 * detection and true impossible-travel speed/distance analysis are NOT
 * implemented — see GeoIpService's docblock for exactly why.
 */
class LoginSecurityService
{
    public function __construct(private CriticalAlertService $criticalAlerts, private GeoIpService $geoIp)
    {
    }

    public function recordSuccess(User $user, Request $request): LoginAttempt
    {
        $isNewDevice = ! LoginAttempt::query()
            ->where('user_id', $user->id)
            ->where('successful', true)
            ->where('user_agent', (string) $request->userAgent())
            ->exists();

        $geo = $this->geoIp->lookup((string) $request->ip());
        $country = $geo['country'] ?? null;

        $isNewCountry = $country !== null && ! LoginAttempt::query()
            ->where('user_id', $user->id)
            ->where('successful', true)
            ->where('country', $country)
            ->exists();

        $attempt = LoginAttempt::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'country' => $country,
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'successful' => true,
            'was_new_device' => $isNewDevice,
            'was_new_country' => $country !== null ? $isNewCountry : null,
            'created_at' => now(),
        ]);

        // Only alert from the user's second-ever login onward — the very first
        // login is trivially "a new device"/"a new country" and would just be noise.
        $hasEarlierLogin = LoginAttempt::query()
            ->where('user_id', $user->id)
            ->where('successful', true)
            ->where('id', '!=', $attempt->id)
            ->exists();

        if ($isNewDevice && $hasEarlierLogin) {
            $user->notify(new SecurityAlert(
                title: 'New login to your account',
                message: "We noticed a login from a device we haven't seen before.\nDevice: ".$this->describeDevice($attempt->user_agent)."\nIP: {$attempt->ip}\nTime: {$attempt->created_at->format('M j, Y g:i A')}\n\nIf this wasn't you, secure your account immediately.",
                actionLabel: 'Review account security',
                actionUrl: route('security.index'),
                requiresReview: true,
            ));

            if ($user->isAdmin()) {
                $this->criticalAlerts->send(
                    'New device login to an admin account',
                    "{$user->email} just logged in from a device not seen before for this account.\nDevice: ".$this->describeDevice($attempt->user_agent)."\nIP: {$attempt->ip}",
                );
            }
        } elseif ($isNewCountry && $hasEarlierLogin) {
            $user->notify(new SecurityAlert(
                title: 'Login from a new country',
                message: "We noticed a login from {$country}, a country we haven't seen on your account before.\nIP: {$attempt->ip}\nTime: {$attempt->created_at->format('M j, Y g:i A')}\n\nIf this wasn't you, secure your account immediately.",
                actionLabel: 'Review account security',
                actionUrl: route('security.index'),
                requiresReview: true,
            ));
        }

        return $attempt;
    }

    public function recordFailure(string $email, Request $request): void
    {
        LoginAttempt::create([
            'user_id' => User::where('email', $email)->value('id'),
            'email' => $email,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'successful' => false,
            'created_at' => now(),
        ]);
    }

    /** Whether this user's last successful login was from a never-seen-before device (used by the risk engine). */
    public function lastLoginWasNewDevice(User $user): bool
    {
        return (bool) LoginAttempt::query()
            ->where('user_id', $user->id)
            ->where('successful', true)
            ->latest('id')
            ->value('was_new_device');
    }

    /** A short, honest device label from the user agent — no client hints, no bot/OS-version fingerprinting library. */
    public function describeDevice(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown device';
        }

        $os = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown OS',
        };

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome/') => 'Safari',
            default => 'Unknown browser',
        };

        return "{$browser} on {$os}";
    }
}
