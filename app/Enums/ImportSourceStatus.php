<?php

namespace App\Enums;

enum ImportSourceStatus: string
{
    case Connected = 'connected';
    case NotConnected = 'not_connected';
    case NeedsCredentials = 'needs_credentials';
    case Testing = 'testing';
    case ConnectionFailed = 'connection_failed';
    case PartnerApprovalRequired = 'partner_approval_required';
    case TemporarilyDisabled = 'temporarily_disabled';

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Connected',
            self::NotConnected => 'Not connected',
            self::NeedsCredentials => 'Needs credentials',
            self::Testing => 'Testing',
            self::ConnectionFailed => 'Connection failed',
            self::PartnerApprovalRequired => 'Partner approval required',
            self::TemporarilyDisabled => 'Temporarily disabled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Connected => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::NotConnected, self::TemporarilyDisabled => 'bg-gray-400/15 text-gray-600 ring-1 ring-gray-400/30',
            self::NeedsCredentials, self::PartnerApprovalRequired => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Testing => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
            self::ConnectionFailed => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
        };
    }
}
