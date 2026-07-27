<?php

namespace App\Enums;

use App\Models\User;

enum BannerAudience: string
{
    case Everyone = 'everyone';
    case Guest = 'guest';
    case LoggedIn = 'logged_in';
    case Verified = 'verified';
    case Agent = 'agent';

    public function label(): string
    {
        return match ($this) {
            self::Everyone => 'Everyone',
            self::Guest => 'Guests only',
            self::LoggedIn => 'Logged-in users',
            self::Verified => 'Verified users (KYC approved)',
            self::Agent => 'Agents',
        };
    }

    /** Real evaluation against the current visitor — used wherever a banner is rendered. */
    public function matches(?User $user): bool
    {
        return match ($this) {
            self::Everyone => true,
            self::Guest => $user === null,
            self::LoggedIn => $user !== null,
            self::Verified => $user !== null && $user->isKycApproved(),
            self::Agent => $user !== null && $user->isAgent(),
        };
    }
}
