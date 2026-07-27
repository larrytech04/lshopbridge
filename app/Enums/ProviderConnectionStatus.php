<?php

namespace App\Enums;

/**
 * Computed display status for a provider — never stored (except the
 * last-test outcome fields, which feed into this). Derived from is_active,
 * whether credentials are present, the last test result, and real recent
 * webhook success/failure history — never fabricated.
 */
enum ProviderConnectionStatus: string
{
    case Connected = 'connected';
    case NotConfigured = 'not_configured';
    case Testing = 'testing';
    case Degraded = 'degraded';
    case AuthenticationFailed = 'authentication_failed';
    case ProviderOffline = 'provider_offline';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Connected',
            self::NotConfigured => 'Not configured',
            self::Testing => 'Testing',
            self::Degraded => 'Degraded',
            self::AuthenticationFailed => 'Authentication failed',
            self::ProviderOffline => 'Provider offline',
            self::Disabled => 'Disabled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Connected => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::NotConfigured, self::Disabled => 'bg-gray-400/15 text-gray-600 ring-1 ring-gray-400/30',
            self::Testing => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::Degraded => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::AuthenticationFailed, self::ProviderOffline => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
        };
    }
}
