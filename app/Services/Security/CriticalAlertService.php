<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Posts operations-critical security events (audit-log tamper detection,
 * an admin account losing MFA, etc.) to Discord/Slack via a plain incoming
 * webhook URL — no SDK needed, both platforms accept a simple JSON POST.
 * A no-op per channel when its webhook URL isn't configured; never throws,
 * an alerting failure must not break the request that triggered it.
 */
class CriticalAlertService
{
    public function send(string $title, string $message): void
    {
        $this->postDiscord($title, $message);
        $this->postSlack($title, $message);
    }

    private function postDiscord(string $title, string $message): void
    {
        $url = config('services.discord.webhook_url');
        if (! $url) {
            return;
        }

        try {
            Http::post($url, ['content' => "**{$title}**\n{$message}"]);
        } catch (\Throwable $e) {
            Log::warning('Discord alert failed', ['exception' => $e->getMessage()]);
        }
    }

    private function postSlack(string $title, string $message): void
    {
        $url = config('services.slack_alerts.webhook_url');
        if (! $url) {
            return;
        }

        try {
            Http::post($url, ['text' => "*{$title}*\n{$message}"]);
        } catch (\Throwable $e) {
            Log::warning('Slack alert failed', ['exception' => $e->getMessage()]);
        }
    }

    public function isConfigured(): bool
    {
        return (bool) (config('services.discord.webhook_url') || config('services.slack_alerts.webhook_url'));
    }
}
